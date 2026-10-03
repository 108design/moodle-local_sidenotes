<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen <andreas@108design.com>.
namespace local_sidenotes\local;

/** Private full-cutover parity checks; exceptions never contain source content or identities. */
final class migration_verifier {
    /** Verify the empty-target migration without publishing any private contents. */
    public static function verify(): array {
        global $DB;
        $sourcecount = $DB->count_records('local_quicknote_notes');
        if ($sourcecount !== $DB->count_records('local_sidenotes_notes')
                || $sourcecount !== $DB->count_records('local_sidenotes_import')) {
            throw new \coding_exception('Migration note/receipt count mismatch.');
        }
        $counts = ['notes' => 0, 'tags' => 0, 'screenshots' => 0, 'colours' => 0];
        foreach ($DB->get_records('local_sidenotes_import', [], 'id') as $receipt) {
            $source = $DB->get_record('local_quicknote_notes', ['id' => $receipt->sourceid,
                'userid' => $receipt->userid, 'timecreated' => $receipt->sourcecreated], '*', MUST_EXIST);
            $target = $DB->get_record('local_sidenotes_notes', ['id' => $receipt->noteid, 'userid' => $receipt->userid], '*', MUST_EXIST);
            foreach (quicknote_importer::convert($source) as $key => $value) {
                if (($value === null) !== ($target->$key === null) || (string) $value !== (string) $target->$key) {
                    throw new \coding_exception('Migration note-field mismatch: ' . $key);
                }
            }
            $counts['notes']++;
            $oldtags = $DB->get_records_sql('SELECT t.id,t.name,t.rawname FROM {tag} t JOIN {tag_instance} ti ON ti.tagid=t.id
                WHERE ti.component=:component AND ti.itemtype=:itemtype AND ti.itemid=:noteid AND ti.tiuserid=:userid',
                ['component' => 'local_quicknote', 'itemtype' => 'local_quicknote_notes',
                    'noteid' => $source->id, 'userid' => $source->userid]);
            // Compare stored spelling, not get_display_name(): Moodle may title-case tags for display.
            $newrecords = $DB->get_records_sql('SELECT t.id,t.name,t.rawname FROM {tag} t JOIN {tag_instance} ti ON ti.tagid=t.id
                WHERE ti.component=:component AND ti.itemtype=:itemtype AND ti.itemid=:noteid AND ti.tiuserid=:userid',
                ['component' => 'local_sidenotes', 'itemtype' => 'local_sidenotes_notes',
                    'noteid' => $target->id, 'userid' => $target->userid]);
            $newtags = array_map(static fn($tag) => ['id' => (int) $tag->id, 'name' => $tag->rawname ?: $tag->name], $newrecords);
            $oldnames = array_map(static fn($tag) => $tag->rawname ?: $tag->name, $oldtags);
            $newnames = array_column($newtags, 'name');
            sort($oldnames); sort($newnames);
            if ($oldnames !== $newnames) {throw new \coding_exception('Migration private-tag mismatch.');}
            $counts['tags'] += count($newtags);
            foreach ($oldtags as $tag) {
                $colour = get_user_preferences('local_quicknote_tagcolour_' . $tag->id, '', $source->userid);
                foreach ($newtags as $newtag) {
                    if ($newtag['name'] === ($tag->rawname ?: $tag->name)
                            && $colour !== get_user_preferences(tag_manager::COLOUR_PREFIX . $newtag['id'], '', $source->userid)) {
                        throw new \coding_exception('Migration private-colour mismatch.');
                    }
                }
                if ($colour !== '') {$counts['colours']++;}
            }
            $files = static function(string $component, int $noteid): array {
                $result = [];
                foreach (get_file_storage()->get_area_files(\context_system::instance()->id,
                        $component, 'screenshot', $noteid, 'filepath,filename', false) as $file) {
                    $result[] = array_map(static fn($key) => (string) $file->{'get_' . $key}(),
                        ['contenthash', 'filesize', 'filepath', 'filename', 'mimetype', 'userid', 'timecreated', 'timemodified']);
                }
                return $result;
            };
            $oldfiles = $files('local_quicknote', (int) $source->id);
            if ($oldfiles !== $files('local_sidenotes', (int) $target->id)) {
                throw new \coding_exception('Migration screenshot metadata/content mismatch.');
            }
            $counts['screenshots'] += count($oldfiles);
        }
        foreach (['courseenabled', 'coursedefault', 'sitewideenabled', 'position', 'perpage'] as $key) {
            $value = get_config('local_quicknote', $key);
            if ($value !== false && $value !== get_config('local_sidenotes', $key)) {
                throw new \coding_exception('Migration setting mismatch: ' . $key);
            }
        }
        $courses = static function(string $table): array {
            global $DB;
            return array_values($DB->get_records($table, [], 'courseid', 'courseid,enabled,module_settings'));
        };
        if ($courses('local_quicknote_course') != $courses('local_sidenotes_course')) {
            throw new \coding_exception('Migration course settings mismatch.');
        }
        foreach (['use', 'usecourse', 'managecourse'] as $capability) {
            // Numeric-keyed get_records would collapse context overrides; get_recordset keeps every row.
            $permissions = static function(string $component) use ($capability): array {
                global $DB;
                $result = [];
                $records = $DB->get_recordset('role_capabilities', ['capability' => 'local/' . $component . ':' . $capability],
                    'contextid,roleid', 'roleid,contextid,permission,timemodified,modifierid');
                foreach ($records as $record) {$result[] = $record;}
                $records->close();
                return $result;
            };
            if ($permissions('quicknote') != $permissions('sidenotes')) {
                throw new \coding_exception('Migration capability permission mismatch.');
            }
        }
        $prefix = 'local_quicknote_tagcolour_';
        $preferences = $DB->get_records_select('user_preferences', $DB->sql_like('name', ':prefix'),
            ['prefix' => $DB->sql_like_escape($prefix) . '%']);
        $collection = \core_tag_area::get_collection('local_sidenotes', 'local_sidenotes_notes');
        foreach ($preferences as $preference) {
            $tag = $DB->get_record('tag', ['id' => (int) substr($preference->name, strlen($prefix))]);
            if (!$tag) {continue;}
            $target = \core_tag_tag::get_by_name($collection, $tag->rawname ?: $tag->name);
            if (!$target || get_user_preferences(tag_manager::COLOUR_PREFIX . $target->id, '', $preference->userid)
                    !== $preference->value) {
                throw new \coding_exception('Migration category-colour preference mismatch.');
            }
        }
        return $counts;
    }
}
