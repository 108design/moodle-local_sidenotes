<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed without any warranty. See <https://www.gnu.org/licenses/>.

namespace local_sidenotes;

use local_sidenotes\local\screenshot_manager;
use local_sidenotes\local\tag_manager;
use local_sidenotes\task\delete_course_notes;

/**
 * Course-deletion cleanup uses native events, tasks, files and private tags.
 *
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(observers::class)]
final class course_cleanup_test extends \advanced_testcase {
    /** Insert a synthetic note without requiring enrolment in its course. */
    private function note(int $userid, int $courseid): int {
        global $DB;
        return (int)$DB->insert_record('local_sidenotes_notes', (object)[
            'userid' => $userid, 'courseid' => $courseid, 'content' => 'Synthetic private note',
            'contentformat' => FORMAT_MARKDOWN, 'url' => '/course/view.php?id=' . $courseid,
            'pagehash' => hash('sha256', 'course:' . $courseid), 'isglobal' => $courseid ? 0 : 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    /** A native course deletion queues work; each execution handles at most one batch. */
    public function test_course_delete_queues_bounded_cleanup_and_preserves_other_scopes(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $deleted = $this->getDataGenerator()->create_course();
        $kept = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $firstid = $this->note($owner->id, $deleted->id);
        for ($i = 1; $i < 201; $i++) {
            $this->note($owner->id, $deleted->id);
        }
        $this->note($other->id, $deleted->id);
        $keptid = $this->note($other->id, $kept->id);
        $globalid = $this->note($owner->id, 0);
        $DB->insert_record('local_sidenotes_course', ['courseid' => $deleted->id, 'enabled' => 1]);
        foreach ([$deleted->id => $firstid, $kept->id => $keptid] as $courseid => $noteid) {
            $DB->insert_record('local_sidenotes_import', (object)[
                'userid' => $owner->id, 'courseid' => $courseid, 'sourceid' => $courseid,
                'sourcecreated' => 1, 'noteid' => $noteid, 'timeimported' => time(),
            ]);
        }
        $this->assertTrue(tag_manager::is_enabled());
        tag_manager::set_for_note($firstid, $owner->id, ['Cleanup sample']);
        tag_manager::set_for_note($keptid, $other->id, ['Kept sample']);
        $files = [];
        foreach ([$firstid, $keptid] as $noteid) {
            $files[$noteid] = get_file_storage()->create_file_from_string([
                'contextid' => \context_system::instance()->id, 'component' => 'local_sidenotes',
                'filearea' => screenshot_manager::FILEAREA, 'itemid' => $noteid, 'filepath' => '/',
                'filename' => 'synthetic.png', 'mimetype' => 'image/png',
            ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6G9sAAAAASUVORK5CYII='));
        }
        $event = \core\event\course_deleted::create([
            'objectid' => $deleted->id, 'context' => \context_course::instance($deleted->id),
            'other' => ['shortname' => $deleted->shortname, 'fullname' => $deleted->fullname],
        ]);
        delete_course($deleted, false);
        $this->assertSame(202, $DB->count_records('local_sidenotes_notes', ['courseid' => $deleted->id]));
        $this->assertFalse($DB->record_exists('local_sidenotes_course', ['courseid' => $deleted->id]));
        $this->assertNotFalse(get_file_storage()->get_file_by_id($files[$firstid]->get_id()));
        observers::course_deleted($event); // Duplicate notifications must not duplicate the initial job.
        $tasks = $DB->get_records('task_adhoc', ['component' => 'local_sidenotes']);
        $this->assertCount(1, $tasks);
        $row = reset($tasks);
        $task = \core\task\manager::adhoc_task_from_record($row);
        $task->execute(); // Keep the current task row present, as the real task runner does.
        $DB->delete_records('task_adhoc', ['id' => $row->id]);
        $this->assertSame(2, $DB->count_records('local_sidenotes_notes', ['courseid' => $deleted->id]));
        $this->assertTrue($DB->record_exists('local_sidenotes_import', ['courseid' => $deleted->id]));
        $this->assertFalse(get_file_storage()->get_file_by_id($files[$firstid]->get_id()));
        $this->assertSame([], tag_manager::get_for_note($firstid, $owner->id));
        $tasks = $DB->get_records('task_adhoc', ['component' => 'local_sidenotes']);
        $this->assertCount(1, $tasks);
        $row = reset($tasks);
        $task = \core\task\manager::adhoc_task_from_record($row);
        $task->execute();
        $DB->delete_records('task_adhoc', ['id' => $row->id]);
        $task->execute(); // Retries and duplicate execution remain harmless after completion.
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['courseid' => $deleted->id]));
        $this->assertFalse($DB->record_exists('local_sidenotes_import', ['courseid' => $deleted->id]));
        $this->assertFalse($DB->record_exists('task_adhoc', ['component' => 'local_sidenotes']));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $keptid]));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $globalid]));
        $this->assertTrue($DB->record_exists('local_sidenotes_import', ['courseid' => $kept->id]));
        $this->assertNotFalse(get_file_storage()->get_file_by_id($files[$keptid]->get_id()));
        $this->assertCount(1, tag_manager::get_for_note($keptid, $other->id));
    }

    /** An invalid or accidentally queued active-course ID can never erase its notes. */
    public function test_cleanup_ignores_existing_courses_and_invalid_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $noteid = $this->note($owner->id, $course->id);
        $task = new delete_course_notes();
        foreach ([[], ['courseid' => -1], ['courseid' => $course->id]] as $data) {
            $task->set_custom_data($data);
            $task->execute();
            $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $noteid]));
        }
    }

    /** Ordinary unenrolment still erases that user's course data synchronously. */
    public function test_user_unenrolment_retains_immediate_private_cleanup(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($owner->id, $course->id, 'student');
        $noteid = $this->note($owner->id, $course->id);
        $otherid = $this->note($owner->id, $othercourse->id);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id, 'component' => 'local_sidenotes',
            'filearea' => screenshot_manager::FILEAREA, 'itemid' => $noteid, 'filepath' => '/',
            'filename' => 'synthetic.png', 'mimetype' => 'image/png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6G9sAAAAASUVORK5CYII='));
        tag_manager::set_for_note($noteid, $owner->id, ['Unenrolment sample']);
        $enrolment = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        enrol_get_plugin('manual')->unenrol_user($enrolment, $owner->id);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $noteid]));
        $this->assertFalse(get_file_storage()->get_file_by_id($file->get_id()));
        $this->assertSame([], tag_manager::get_for_note($noteid, $owner->id));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $otherid]));
        $this->assertFalse($DB->record_exists('task_adhoc', ['component' => 'local_sidenotes']));
    }
}
