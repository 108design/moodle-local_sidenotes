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
 * File documentation for db/upgrade.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen.
defined('MOODLE_INTERNAL') || die();

/** Independent SideNotes upgrade series. Imports are never upgrade side effects. */
function xmldb_local_sidenotes_upgrade(int $oldversion): bool {
    global $DB, $CFG;
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
    if ($oldversion < 2026101000) {
        // The panel now always opens on the right, clear only the retired placement option.
        unset_config('position', 'local_sidenotes');
        upgrade_plugin_savepoint(true, 2026101000, 'local', 'sidenotes');
    }
    if ($oldversion < 2026102400) {
        // Course deletion now queues bounded cleanup; existing notes require no migration.
        upgrade_plugin_savepoint(true, 2026102400, 'local', 'sidenotes');
    }
    if ($oldversion < 2026102401) {
        // Rebuild only indexed associations; original note/source/ownership/history remain.
        \local_sidenotes\local\source_move_manager::repair_legacy_hashes(static function(): void {
            upgrade_set_timeout(120);
        });
        if (get_config('local_sidenotes', 'sourcebase') === false) {
            set_config('sourcebase', $CFG->wwwroot, 'local_sidenotes');
        }
        upgrade_plugin_savepoint(true, 2026102401, 'local', 'sidenotes');
    }
    return true;
}
