<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen.
defined('MOODLE_INTERNAL') || die();

/** Independent SideNotes upgrade series. Imports are never upgrade side effects. */
function xmldb_local_sidenotes_upgrade(int $oldversion): bool {
    global $DB;
    if ($oldversion < 2026100401) {
        $manager = $DB->get_manager();
        $table = new xmldb_table('local_sidenotes_notes');
        foreach ([new xmldb_field('archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isglobal'),
                new xmldb_field('timearchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'archived')] as $field) {
            if (!$manager->field_exists($table, $field)) {
                $manager->add_field($table, $field);
            }
        }
        $index = new xmldb_index('userarchive_ix', XMLDB_INDEX_NOTUNIQUE, ['userid', 'archived', 'timearchived']);
        if (!$manager->index_exists($table, $index)) {
            $manager->add_index($table, $index);
        }
        upgrade_plugin_savepoint(true, 2026100401, 'local', 'sidenotes');
    }
    if ($oldversion < 2026100402) {
        // UI/session-state changes only. Stored notes and archive membership stay untouched.
        upgrade_plugin_savepoint(true, 2026100402, 'local', 'sidenotes');
    }
    return true;
}
