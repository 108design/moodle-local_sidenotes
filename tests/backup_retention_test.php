<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Native backup/restore regression cases for three-state availability.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sidenotes;

global $CFG;
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');
require_once(__DIR__ . '/../backup/moodle2/backup_local_sidenotes_plugin.class.php');
require_once(__DIR__ . '/../backup/moodle2/restore_local_sidenotes_plugin.class.php');

/** Exercises actual backup XML and course/module restore mapping. */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_local_sidenotes_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_local_sidenotes_plugin::class)]
final class backup_retention_test extends \core_backup_backup_restore_base_testcase {
    /** Distinguish absence, inherited null, explicit values, and legacy empty XML. */
    public static function settings(): array {
        return ['absent/on' => [false, null, 1], 'absent/off' => [false, null, 0],
            'null/on' => [true, null, 1], 'null/off' => [true, null, 0],
            'explicit off' => [true, 0, 1], 'explicit on' => [true, 1, 0],
            'legacy empty' => [false, '', 1]];
    }

    /**
     * Test inheritance, module mapping and exclusion of personal notes together.
     * @dataProvider settings
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('settings')]
    public function test_native_backup_restore(bool $recordexists, int|string|null $enabled, int $default): void {
        global $DB, $CFG, $USER;
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', $default, 'local_sidenotes');
        set_config('sitewideenabled', 0, 'local_sidenotes');
        $gen = $this->getDataGenerator();
        $source = $gen->create_course();
        $module = $gen->create_module('page', ['course' => $source->id]);
        $owner = $gen->create_user();
        $gen->enrol_user($owner->id, $source->id, 'student');
        if ($recordexists) {
            $DB->insert_record('local_sidenotes_course', (object)[
                'courseid' => $source->id, 'enabled' => $enabled,
                'module_settings' => json_encode([$module->cmid => 0]),
            ]);
        }
        $DB->insert_record('local_sidenotes_notes', (object)[
            'userid' => $owner->id, 'courseid' => $source->id, 'content' => 'Private synthetic content',
            'url' => '', 'pagehash' => hash('sha256', 'private'), 'timecreated' => 1, 'timemodified' => 2,
        ]);
        $backupid = $this->perform_backup($source);
        $path = $CFG->tempdir . '/backup/' . $backupid . '/course/course.xml';
        $xml = file_get_contents($path);
        $this->assertStringContainsString('plugin_local_sidenotes_course', $xml);
        preg_match('~<plugin_local_sidenotes_course>.*?</plugin_local_sidenotes_course>~s', $xml, $fragment);
        if ($enabled === '') {
            // Reproduce the old export's empty element in an otherwise real Moodle backup.
            $this->assertStringContainsString('$@NULL@$', $xml);
            file_put_contents($path, str_replace($fragment[0], str_replace('$@NULL@$', '', $fragment[0]), $xml));
        }
        // Adding activities to an existing course deliberately skips its course.xml unless overwrite_conf is set.
        $targetid = \restore_dbops::create_new_course($source->fullname . ' Restored',
            $source->shortname . '_restored', $source->category);
        $controller = new \restore_controller($backupid, $targetid, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, $USER->id, \backup::TARGET_NEW_COURSE);
        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();
        $target = $DB->get_record('course', ['id' => $targetid], '*', MUST_EXIST);
        $record = $DB->get_record('local_sidenotes_course', ['courseid' => $target->id]);
        if ($recordexists) {
            $this->assertNotFalse($record, 'Restored settings missing. Export fragment: ' . ($fragment[0] ?? 'none'));
        }
        $actual = $record ? $record->enabled : null;
        if ($enabled === null || $enabled === '') {
            $this->assertNull($actual);
        } else {
            $this->assertSame((int)$enabled, (int)$actual);
        }
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['courseid' => $target->id]));
        if ($recordexists) {
            $targetmodule = $DB->get_record('page', ['course' => $target->id]);
            $cm = get_coursemodule_from_instance('page', $targetmodule->id, $target->id);
            $this->assertEquals([(string)$cm->id => 0], json_decode($record->module_settings, true));
        }
        $gen->enrol_user($owner->id, $target->id, 'student');
        $this->setUser($owner);
        $expected = ($enabled === null || $enabled === '') ? (bool)$default : (bool)$enabled;
        $this->assertSame($expected, \local_sidenotes\local\access_policy::course_allowed($target->id));
        set_config('coursedefault', 1 - $default, 'local_sidenotes');
        $this->assertSame(($enabled === null || $enabled === '') ? !$expected : $expected,
            \local_sidenotes\local\access_policy::course_allowed($target->id));
    }
}
