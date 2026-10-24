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
 * File documentation for classes/local/bulk_manager.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\local;

/**
 * Snapshot-guarded owner-private batch operations.
 * Inspired by QuickNote 0.12/0.13 (Matheus Mathias); independently implemented for
 * Side Notes archive, tags, global scope, locks and optimistic concurrency.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class bulk_manager {
    public const MAX_NOTES = 500;

    public static function ids(array $ids): array {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids || min($ids) <= 0 || count($ids) > self::MAX_NOTES) {
            throw new \invalid_parameter_exception('Select between 1 and 500 notes.');
        }
        sort($ids); return $ids;
    }

    /** All filtered notes, never a silent first page or a silently truncated selection. */
    public static function select(int $userid, bool $archive, int $course, array $tags, string $search): array {
        global $DB;
        [$sql, $params] = note_filters::query($userid, $archive, $course, $tags, $search);
        $notes = $DB->get_records_sql('SELECT qn.id ' . $sql . ' ORDER BY qn.id', $params, 0, self::MAX_NOTES + 1);
        if (count($notes) > self::MAX_NOTES) {throw new \moodle_exception('bulk:limit', 'local_sidenotes');}
        return array_map('intval', array_keys($notes));
    }

    /** Reject foreign/missing ids and changed archive state as a whole, never silently skip them. */
    public static function records(array $ids, int $userid, bool $archive): array {
        global $DB;
        $ids = self::ids($ids);
        $records = $DB->get_records_list('local_sidenotes_notes', 'id', $ids, 'id ASC');
        if (count($records) !== count($ids)) {throw new \moodle_exception('bulk:changed', 'local_sidenotes');}
        foreach ($records as $note) {
            if ((int) $note->userid !== $userid || (bool) $note->archived !== $archive) {
                throw new \moodle_exception('bulk:changed', 'local_sidenotes');
            }
        }
        return $records;
    }

    private static function revision(array $records, int $userid, string $action, array $tags): string {
        $notetags = tag_manager::get_for_notes(array_keys($records), $userid);
        $files = [];
        foreach ($records as $note) {
            $files[$note->id] = array_map(static fn(array $file): int => (int) $file['id'],
                screenshot_manager::get_for_note((int) $note->id));
        }
        return hash('sha256', json_encode([$userid, $action, $tags, array_values($records), $notetags, $files]));
    }

    /** Validate first, then apply under the same owner/note locks in one transaction. */
    public static function execute(int $userid, array $ids, bool $archive, string $action,
        array $tags = [], string $revision = '', bool $preview = false): array {
        global $DB;
        if (!in_array($action, ['archive', 'restore', 'delete', 'addtags', 'removetags', 'globalon', 'globaloff'], true)
                || ($archive ? !in_array($action, ['restore', 'delete'], true) : in_array($action, ['restore', 'delete'], true))) {
            throw new \invalid_parameter_exception('Invalid batch action for this view.');
        }
        $tags = tag_manager::normalise($tags);
        if (in_array($action, ['addtags', 'removetags'], true) && (!$tags || !tag_manager::is_enabled())) {
            throw new \invalid_parameter_exception('Private tags must be enabled and nonempty.');
        }
        $ownerlock = archive_manager::owner_lock($userid); $locks = []; $transaction = null;
        try {
            foreach (self::ids($ids) as $id) {$locks[] = access_policy::note_lock($id);}
            $records = self::records($ids, $userid, $archive);
            foreach ($records as $note) {
                if (!$archive) {access_policy::require_edit($note);}
            }
            $current = self::revision($records, $userid, $action, $tags);
            if (!$preview) {
                if (!$revision || !hash_equals($current, $revision)) {
                    throw new \moodle_exception('bulk:changed', 'local_sidenotes');
                }
                $transaction = $DB->start_delegated_transaction();
                foreach ($records as $note) {
                    $update = (object) ['id' => $note->id];
                    if ($action === 'delete') {
                        screenshot_manager::delete_for_note((int) $note->id);
                        tag_manager::remove_for_note((int) $note->id, $userid);
                        $DB->delete_records('local_sidenotes_notes', ['id' => $note->id, 'userid' => $userid]);
                        continue; // Import receipts deliberately survive deletion.
                    }
                    if ($action === 'archive' || $action === 'restore') {
                        $update->archived = (int) ($action === 'archive');
                        $update->timearchived = $update->archived ? time() : 0;
                    } else {
                        $update->timemodified = time();
                        if ($action === 'globalon' || $action === 'globaloff') {
                            $update->isglobal = (int) ($action === 'globalon');
                        } else {
                            $names = array_column(tag_manager::get_for_note((int) $note->id, $userid), 'name');
                            if ($action === 'addtags') {
                                $combined = array_unique(array_map([\core_text::class, 'strtolower'], array_merge($names, $tags)));
                                if (count($combined) > tag_manager::MAX_TAGS) {
                                    throw new \moodle_exception('bulk:taglimit', 'local_sidenotes');
                                }
                                $names = array_merge($names, $tags);
                            } else {
                                $remove = array_map([\core_text::class, 'strtolower'], $tags);
                                $names = array_filter($names, static fn(string $name): bool =>
                                    !in_array(\core_text::strtolower($name), $remove, true));
                            }
                            tag_manager::set_for_note((int) $note->id, $userid, $names);
                        }
                    }
                    $DB->update_record('local_sidenotes_notes', $update);
                }
                $transaction->allow_commit();
            }
            return ['ids' => array_map('intval', array_keys($records)), 'count' => count($records), 'revision' => $current]
                + archive_manager::status($userid);
        } catch (\Throwable $exception) {
            if ($transaction) {$transaction->rollback($exception);}
            throw $exception;
        } finally {
            foreach (array_reverse($locks) as $lock) {$lock->release();}
            $ownerlock->release();
        }
    }
}
