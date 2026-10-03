<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\local;

/**
 * Explicit, additive import without loading any QuickNote runtime code.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quicknote_importer {
    private const SOURCE = 'local_quicknote_notes';

    /** No dependency: absent/incompatible source tables mean no import offer. */
    public static function available(): bool {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table(self::SOURCE))) {
            return false;
        }
        return !array_diff(['id', 'userid', 'courseid', 'content', 'quote', 'quoteurl', 'url',
            'timecreated', 'timemodified'], array_keys($DB->get_columns(self::SOURCE)));
    }

    /** Count this owner's new records; deleted imports remain acknowledged. */
    public static function pending(int $userid): int {
        global $DB;
        if (!self::available()) {return 0;}
        return (int) $DB->count_records_sql('SELECT COUNT(1) FROM {local_quicknote_notes} q
            WHERE q.userid=:userid AND NOT EXISTS (SELECT 1 FROM {local_sidenotes_import} i
                WHERE i.userid=q.userid AND i.sourceid=q.id AND i.sourcecreated=q.timecreated)', ['userid' => $userid]);
    }

    /** Web callers can import only their own records. import.php enforces POST + sesskey. */
    public static function import_own(): array {
        global $USER;
        require_login();
        access_policy::require_overview();
        return self::copy_user((int) $USER->id);
    }

    /** CLI-only full cutover, not the permanent owner import. */
    public static function migrate_fork(): array {
        global $DB;
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT || !self::available()
                || (string) get_config('local_quicknote', 'version') !== '2026100300') {
            throw new \coding_exception('Cutover requires the verified 0.12.0 enhanced fork.');
        }
        if ($DB->count_records('local_sidenotes_notes') || $DB->count_records('local_sidenotes_import')
                || get_config('local_sidenotes', 'forkmigrated')) {
            throw new \coding_exception('Cutover requires an empty target; never overwrite an archive.');
        }
        $result = ['notes' => 0, 'skipped' => 0, 'screenshots' => 0, 'tags' => 0, 'users' => 0];
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($DB->get_fieldset_sql('SELECT DISTINCT userid FROM {local_quicknote_notes} ORDER BY userid') as $userid) {
                $copied = self::copy_user((int) $userid);
                foreach (['notes', 'skipped', 'screenshots', 'tags'] as $key) {$result[$key] += $copied[$key];}
                $result['users']++;
            }
            foreach (['courseenabled', 'coursedefault', 'sitewideenabled', 'position', 'perpage'] as $key) {
                $value = get_config('local_quicknote', $key);
                if ($value !== false) {set_config($key, $value, 'local_sidenotes');}
            }
            foreach ($DB->get_records('local_quicknote_course') as $course) {
                unset($course->id);
                $DB->insert_record('local_sidenotes_course', $course);
            }
            self::copy_colour_preferences();
            // Exact permissions, including prohibits/context overrides, replace fresh archetype defaults.
            foreach (['use', 'usecourse', 'managecourse'] as $capability) {
                $target = 'local/sidenotes:' . $capability;
                $DB->delete_records('role_capabilities', ['capability' => $target]);
                foreach ($DB->get_records('role_capabilities', ['capability' => 'local/quicknote:' . $capability]) as $permission) {
                    unset($permission->id);
                    $permission->capability = $target;
                    $DB->insert_record('role_capabilities', $permission);
                }
            }
            set_config('forkmigrated', time(), 'local_sidenotes');
            \context_system::instance()->mark_dirty();
            $transaction->allow_commit();
            \context_helper::reset_caches();
            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }

    /** Literal originals remain plain; enhanced formats, dates and metadata survive. */
    public static function convert(\stdClass $source): \stdClass {
        global $DB, $CFG;
        $url = (string) $source->url;
        $unbound = ($source->pagehash ?? '') === hash('sha256', 'quicknote:unbound');
        $prefix = rtrim($CFG->wwwroot, '/') . '/local/quicknote/';
        $remapped = strpos($url, $prefix) === 0
            && preg_match('~^(?:view|tags|course)\.php(?:[?#]|$)~', substr($url, strlen($prefix)));
        if ($remapped) {
            $url = rtrim($CFG->wwwroot, '/') . '/local/sidenotes/' . substr($url, strlen($prefix));
        }
        $format = (int) ($source->contentformat ?? FORMAT_PLAIN);
        if (!in_array($format, [(int) FORMAT_PLAIN, (int) FORMAT_MARKDOWN], true)) {
            throw new \coding_exception('Unsupported source format; refusing a lossy import.');
        }
        $title = property_exists($source, 'pagetitle') ? $source->pagetitle : null;
        if (!property_exists($source, 'pagetitle')) {
            $title = $DB->get_field('course', 'fullname', ['id' => $source->courseid]) ?: get_string('unknownpage', 'local_sidenotes');
        }
        return (object) [
            'userid' => (int) $source->userid, 'courseid' => (int) $source->courseid,
            'content' => $source->content, 'contentformat' => $format,
            'quote' => $source->quote, 'quoteurl' => self::remap_source_url($source->quoteurl), 'url' => $url,
            'pagehash' => $unbound ? access_policy::unbound_hash()
                : ($remapped ? page_identity::legacy_hash($url) : ($source->pagehash ?? page_identity::legacy_hash($url))),
            'pagetitle' => $title, 'isglobal' => (int) ($source->isglobal ?? 0),
            'timecreated' => (int) $source->timecreated, 'timemodified' => (int) $source->timemodified,
        ];
    }

    private static function remap_source_url(?string $url): ?string {
        global $CFG;
        if ($url === null) {return null;}
        $prefix = rtrim($CFG->wwwroot, '/') . '/local/quicknote/';
        if (strpos($url, $prefix) === 0
                && preg_match('~^(?:view|tags|course)\.php(?:[?#]|$)~', substr($url, strlen($prefix)))) {
            return rtrim($CFG->wwwroot, '/') . '/local/sidenotes/' . substr($url, strlen($prefix));
        }
        return $url;
    }

    /** Serialize per owner and copy, without updating or deleting any source record. */
    private static function copy_user(int $userid): array {
        global $DB;
        $result = ['notes' => 0, 'skipped' => 0, 'screenshots' => 0, 'tags' => 0];
        if (!self::available()) {return $result;}
        $lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('quicknote-import-' . $userid, 10);
        if (!$lock) {throw new \moodle_exception('error:busy', 'local_sidenotes');}
        $transaction = $DB->start_delegated_transaction();
        try {
            $sources = $DB->get_recordset(self::SOURCE, ['userid' => $userid], 'id');
            try {
                foreach ($sources as $source) {
                    $receipt = ['userid' => $userid, 'sourceid' => (int) $source->id,
                        'sourcecreated' => (int) $source->timecreated];
                    if ($DB->record_exists('local_sidenotes_import', $receipt)) {
                        $result['skipped']++;
                        continue;
                    }
                    $note = self::convert($source);
                    $noteid = (int) $DB->insert_record('local_sidenotes_notes', $note);
                    $result['tags'] += self::copy_tags((int) $source->id, $noteid, $userid);
                    $result['screenshots'] += self::copy_files((int) $source->id, $noteid);
                    $DB->insert_record('local_sidenotes_import', $receipt + ['courseid' => $note->courseid,
                        'noteid' => $noteid, 'timeimported' => time()]);
                    $result['notes']++;
                }
            } finally {$sources->close();}
            $transaction->allow_commit();
            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        } finally {$lock->release();}
    }

    private static function copy_tags(int $sourceid, int $noteid, int $userid): int {
        global $DB;
        $tags = $DB->get_records_sql('SELECT t.id,t.name,t.rawname FROM {tag} t JOIN {tag_instance} ti ON ti.tagid=t.id
            WHERE ti.component=:component AND ti.itemtype=:itemtype AND ti.itemid=:noteid AND ti.tiuserid=:userid
            ORDER BY ti.ordering,t.id', ['component' => 'local_quicknote', 'itemtype' => 'local_quicknote_notes',
                'noteid' => $sourceid, 'userid' => $userid]);
        if (!$tags) {return 0;}
        if (!tag_manager::is_enabled()) {
            throw new \coding_exception('Enable the private Side Notes tag area before importing tagged notes.');
        }
        tag_manager::set_for_note($noteid, $userid, array_map(static fn($tag) => $tag->rawname ?: $tag->name, $tags));
        $targets = tag_manager::get_for_note($noteid, $userid);
        if (count($targets) !== count($tags)) {throw new \coding_exception('Tag count mismatch.');}
        foreach ($tags as $tag) {
            $colour = get_user_preferences('local_quicknote_tagcolour_' . $tag->id, '', $userid);
            if ($colour === '') {continue;}
            foreach ($targets as $target) {
                if (\core_text::strtolower($target['name']) === \core_text::strtolower($tag->rawname ?: $tag->name)
                        && get_user_preferences(tag_manager::COLOUR_PREFIX . $target['id'], '', $userid) === '') {
                    set_user_preference(tag_manager::COLOUR_PREFIX . $target['id'], $colour, $userid);
                }
            }
        }
        return count($targets);
    }

    private static function copy_files(int $sourceid, int $noteid): int {
        $storage = get_file_storage();
        $files = $storage->get_area_files(\context_system::instance()->id, 'local_quicknote', 'screenshot', $sourceid, 'id', false);
        foreach ($files as $file) {
            $created = $storage->create_file_from_storedfile(['component' => 'local_sidenotes', 'itemid' => $noteid], $file);
            if ($created->get_contenthash() !== $file->get_contenthash()) {throw new \coding_exception('Screenshot hash mismatch.');}
        }
        return count($files);
    }

    /** Preserve even currently unused category colours in the one-time full cutover. */
    private static function copy_colour_preferences(): void {
        global $DB;
        $prefix = 'local_quicknote_tagcolour_';
        $preferences = $DB->get_records_select('user_preferences', $DB->sql_like('name', ':prefix'),
            ['prefix' => $DB->sql_like_escape($prefix) . '%']);
        if (!$preferences) {return;}
        if (!tag_manager::is_enabled()) {throw new \coding_exception('Enable Side Notes tags before colour migration.');}
        $collection = \core_tag_area::get_collection('local_sidenotes', 'local_sidenotes_notes');
        foreach ($preferences as $preference) {
            $id = substr($preference->name, strlen($prefix));
            if (!ctype_digit($id)) {throw new \coding_exception('Unexpected category-colour preference.');}
            $source = $DB->get_record('tag', ['id' => (int) $id]);
            // A deleted native tag has no category to migrate. Keep its preference only in the untouched source.
            if (!$source) {continue;}
            $tags = \core_tag_tag::create_if_missing($collection, [$source->rawname ?: $source->name]);
            $target = reset($tags);
            set_user_preference(tag_manager::COLOUR_PREFIX . $target->id, $preference->value, $preference->userid);
        }
    }
}
