<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. See LICENSE.
namespace local_sidenotes\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Bounded administrator workflow for private-note source metadata.
 *
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_move_manager {
    public const BATCH_SIZE = 200;
    private const JOB = 'sourcemovejob';
    private const MAPS = 'sourcemappings';
    private static ?array $mappingcache = null;

    /** Only system administrators with the native configuration capability can operate a move. */
    private static function require_admin(): void {
        require_login();
        require_capability('moodle/site:config', \context_system::instance());
    }

    /** Read operational state directly, including after another request advanced the job. */
    private static function read_json(string $key): ?array {
        global $DB;
        $raw = $DB->get_field('config_plugins', 'value', ['plugin' => 'local_sidenotes', 'name' => $key]);
        if ($raw === false || $raw === '') {
            return null;
        }
        try {
            $value = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? $value : null;
        } catch (\JsonException $exception) {
            return null;
        }
    }

    private static function store_job(array $job): array {
        $job['updated'] = time();
        set_config(self::JOB, json_encode($job, JSON_THROW_ON_ERROR), 'local_sidenotes');
        return $job;
    }

    /** Serialise all workflow state; note edits additionally use their existing per-note locks. */
    private static function locked(callable $operation) {
        $lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('source-move', 10);
        if (!$lock) {
            throw new \moodle_exception('error:busy', 'local_sidenotes');
        }
        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    /** One site-wide job contains counts and bases only, never note bodies or owner identities. */
    public static function current_job(): ?array {
        self::require_admin();
        return self::read_json(self::JOB);
    }

    private static function job(string $id, bool $requiretarget = true): array {
        global $CFG;
        $job = self::read_json(self::JOB);
        if (!$job || !hash_equals((string) ($job['id'] ?? ''), $id)) {
            throw new \invalid_parameter_exception('The source move has changed; reload its status.');
        }
        if ($requiretarget && source_relocation::normalise_base($CFG->wwwroot) !== $job['target']) {
            throw new \moodle_exception('sourcemovetargetchanged', 'local_sidenotes');
        }
        return $job;
    }

    /** Begin a read-only preview bounded by the current highest note id. */
    public static function start_preview(string $oldbase): array {
        global $DB, $CFG;
        self::require_admin();
        $oldbase = source_relocation::normalise_base($oldbase);
        $target = source_relocation::normalise_base($CFG->wwwroot);
        return self::locked(static function() use ($oldbase, $target, $DB): array {
            $previous = self::read_json(self::JOB);
            if ($previous && in_array($previous['status'] ?? '', ['preview', 'apply', 'paused', 'failed'], true)) {
                throw new \moodle_exception('sourcemovebusy', 'local_sidenotes');
            }
            $through = (int) $DB->get_field_sql('SELECT MAX(id) FROM {local_sidenotes_notes}');
            $job = ['id' => bin2hex(random_bytes(16)), 'old' => $oldbase, 'target' => $target,
                'status' => $through ? 'preview' : 'ready', 'phase' => 'preview', 'after' => 0, 'through' => $through,
                'total' => (int) $DB->count_records_select('local_sidenotes_notes', 'id <= :lastid', ['lastid' => $through]),
                'scanned' => 0, 'changed' => 0, 'url' => 0, 'quoteurl' => 0, 'pagehash' => 0,
                'unaffected' => 0, 'skipped' => 0, 'created' => time()];
            return self::store_job($job);
        });
    }

    /** Confirm exactly the previewed mapping. Records created after that preview are outside its snapshot. */
    public static function approve(string $id): array {
        global $DB;
        self::require_admin();
        return self::locked(static function() use ($id, $DB): array {
            $job = self::job($id);
            if ($job['status'] !== 'ready') {
                throw new \invalid_parameter_exception('A completed preview is required.');
            }
            $transaction = $DB->start_delegated_transaction();
            self::remember_mapping($job['old'], $job['target']);
            $job['prediction'] = array_intersect_key($job, array_flip(['changed', 'url', 'quoteurl', 'pagehash', 'skipped']));
            foreach (['after', 'scanned', 'changed', 'url', 'quoteurl', 'pagehash', 'unaffected', 'skipped'] as $key) {
                $job[$key] = 0;
            }
            $job['phase'] = 'apply';
            $job['status'] = $job['through'] ? 'apply' : 'done';
            if ($job['status'] === 'done') {
                set_config('sourcebase', $job['target'], 'local_sidenotes');
            }
            $job = self::store_job($job);
            $transaction->allow_commit();
            return $job;
        });
    }

    /** Examine or apply at most one batch, with fresh reads after every batch lock has been acquired. */
    public static function advance(string $id): array {
        global $DB;
        self::require_admin();
        return self::locked(static function() use ($id, $DB): array {
            $job = self::job($id);
            if (!in_array($job['status'], ['preview', 'apply'], true)) {
                return $job;
            }
            $records = $DB->get_records_select('local_sidenotes_notes', 'id > :afterid AND id <= :lastid',
                ['afterid' => $job['after'], 'lastid' => $job['through']], 'id ASC', 'id, url, quoteurl, pagehash',
                0, self::BATCH_SIZE);
            $locks = [];
            $transaction = null;
            $phase = $job['status'];
            try {
                if ($phase === 'apply') {
                    // Acquire before the transaction snapshot, so a completed concurrent edit is visible.
                    foreach ($records as $record) {
                        $locks[] = access_policy::note_lock((int) $record->id);
                    }
                }
                $transaction = $DB->start_delegated_transaction();
                foreach ($records as $record) {
                    $job['after'] = (int) $record->id;
                    $job['scanned']++;
                    if ($phase === 'apply') {
                        $record = $DB->get_record('local_sidenotes_notes', ['id' => $record->id],
                            'id, url, quoteurl, pagehash');
                        if (!$record) {
                            $job['unaffected']++;
                            continue;
                        }
                    }
                    try {
                        $patch = source_relocation::changes_for_note($record, $job['old'], $job['target']);
                    } catch (\invalid_parameter_exception $exception) {
                        $job['skipped']++;
                        continue;
                    }
                    if (!$patch) {
                        $job['unaffected']++;
                        continue;
                    }
                    $job['changed']++;
                    foreach (array_keys($patch) as $field) {
                        $job[$field]++;
                    }
                    if ($phase === 'apply') {
                        $DB->update_record('local_sidenotes_notes', (object) (['id' => (int) $record->id] + $patch));
                    }
                }
                if (count($records) < self::BATCH_SIZE || $job['after'] >= $job['through']) {
                    $job['status'] = $phase === 'preview' ? 'ready' : 'done';
                    if ($job['status'] === 'done') {
                        set_config('sourcebase', $job['target'], 'local_sidenotes');
                    }
                }
                $job = self::store_job($job);
                $transaction->allow_commit();
                return $job;
            } catch (\Throwable $exception) {
                if ($transaction) {
                    try {
                        $transaction->rollback($exception);
                    } catch (\Throwable $rolledback) {
                        // The original error is reported below; a failed batch never advances its checkpoint.
                    }
                }
                $job = self::job($id);
                $job['status'] = 'failed';
                $job['phase'] = $phase;
                self::store_job($job);
                throw $exception;
            } finally {
                foreach (array_reverse($locks) as $lock) {
                    $lock->release();
                }
            }
        });
    }

    public static function pause(string $id): array {
        self::require_admin();
        return self::locked(static function() use ($id): array {
            $job = self::job($id);
            if (in_array($job['status'], ['preview', 'apply'], true)) {
                $job['phase'] = $job['status'];
                $job['status'] = 'paused';
            }
            return self::store_job($job);
        });
    }

    public static function resume(string $id): array {
        self::require_admin();
        return self::locked(static function() use ($id): array {
            $job = self::job($id);
            if (in_array($job['status'], ['paused', 'failed'], true)) {
                $job['status'] = $job['phase'];
            }
            return self::store_job($job);
        });
    }

    /** Stop further work. Successfully committed batches remain applied. */
    public static function stop(string $id): array {
        self::require_admin();
        return self::locked(static function() use ($id): array {
            // An abandoned job can also be stopped after a second installation move.
            $job = self::job($id, false);
            $job['status'] = 'stopped';
            return self::store_job($job);
        });
    }

    /** Accepted mappings are for stored sources/imports only, never arbitrary new write inputs. */
    private static function remember_mapping(string $old, string $target): void {
        $maps = self::read_json(self::MAPS) ?? [];
        foreach ($maps as &$map) {
            if (($map['to'] ?? '') === $old) {
                $map['to'] = $target;
            }
        }
        unset($map);
        if ($old !== $target) {
            $maps[hash('sha256', $old)] = ['from' => $old, 'to' => $target];
        }
        set_config(self::MAPS, json_encode($maps, JSON_THROW_ON_ERROR), 'local_sidenotes');
        self::$mappingcache = null;
    }

    /** Cache only immutable accepted mappings for this request, never the advancing job state. */
    private static function mappings(): array {
        if (self::$mappingcache === null) {
            self::$mappingcache = self::read_json(self::MAPS) ?? [];
        }
        return self::$mappingcache;
    }

    public static function resolve_stored_url(string $url): string {
        global $CFG;
        $target = source_relocation::normalise_base($CFG->wwwroot);
        foreach (self::mappings() as $map) {
            if (!is_array($map) || ($map['to'] ?? '') !== $target || empty($map['from'])) {
                continue;
            }
            try {
                $resolved = source_relocation::map_url($url, $map['from'], $target);
                if ($resolved !== null) {
                    return page_identity::sanitise($resolved, true);
                }
            } catch (\invalid_parameter_exception $exception) {
                // Invalid operational metadata must not make a user's otherwise valid notes inaccessible.
            }
        }
        return $url;
    }

    /** Keep both old/new indexed identities readable while an approved relocation is being applied. */
    public static function lookup_hashes(string $url): array {
        global $CFG;
        $hashes = [page_identity::hash($url)];
        $target = source_relocation::normalise_base($CFG->wwwroot);
        foreach (self::mappings() as $map) {
            if (!is_array($map) || ($map['to'] ?? '') !== $target || empty($map['from'])) {
                continue;
            }
            try {
                $legacy = source_relocation::map_url($url, $target, $map['from'], false);
                if ($legacy !== null) {
                    $hashes[] = page_identity::legacy_hash($legacy);
                }
            } catch (\invalid_parameter_exception $exception) {
                // See resolve_stored_url: untrusted inputs do not acquire a relaxed validation path.
            }
        }
        return array_values(array_unique($hashes));
    }

    /** One-time upgrade: rekey bound legacy records without touching their URLs or personal data. */
    public static function repair_legacy_hashes(?callable $progress = null): array {
        global $DB;
        $last = (int) $DB->get_field_sql('SELECT MAX(id) FROM {local_sidenotes_notes}');
        $after = 0;
        $result = ['scanned' => 0, 'changed' => 0, 'batches' => 0];
        do {
            $records = $DB->get_records_select('local_sidenotes_notes', 'id > :afterid AND id <= :lastid',
                ['afterid' => $after, 'lastid' => $last], 'id ASC', 'id, url, pagehash', 0, self::BATCH_SIZE);
            if (!$records) {
                break;
            }
            $result['batches']++;
            $transaction = $DB->start_delegated_transaction();
            foreach ($records as $record) {
                $after = (int) $record->id;
                $result['scanned']++;
                $parts = parse_url((string) $record->url);
                if (access_policy::unbound($record) || !$parts || empty($parts['host'])
                        || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                    continue;
                }
                $hash = page_identity::legacy_hash((string) $record->url);
                if ($hash !== (string) $record->pagehash) {
                    $DB->set_field('local_sidenotes_notes', 'pagehash', $hash, ['id' => $record->id]);
                    $result['changed']++;
                }
            }
            $transaction->allow_commit();
            if ($progress) {
                $progress($result);
            }
        } while ($after < $last);
        return $result;
    }
}
