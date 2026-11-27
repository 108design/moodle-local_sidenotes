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
 * Native enrolment, deletion, ownership and Privacy API retention regressions.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sidenotes;

use local_sidenotes\local\access_policy;
use local_sidenotes\local\retention_manager;
use local_sidenotes\local\screenshot_manager;
use local_sidenotes\local\tag_manager;
use local_sidenotes\privacy\provider;

/** Real Moodle lifecycle events, not mocked observer results. */
#[\PHPUnit\Framework\Attributes\CoversClass(retention_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observers::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(access_policy::class)]
final class retention_test extends \advanced_testcase {
    /** Configure synthetic course-mode users for each test. */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->usetags = true;
        set_config('sitewideenabled', 0, 'local_sidenotes');
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
    }

    /** A note includes every piece of personal data that must survive retention. */
    private function note(int $userid, int $courseid, bool $archived = false): \stdClass {
        global $DB;
        $note = (object)[
            'userid' => $userid, 'courseid' => $courseid, 'content' => 'Synthetic **personal** note',
            'contentformat' => FORMAT_MARKDOWN, 'url' => '/course/view.php?id=' . $courseid,
            'quoteurl' => '/course/view.php?id=' . $courseid . '#:~:text=sample', 'quote' => 'sample',
            'pagetitle' => 'Synthetic course page', 'pagehash' => hash('sha256', 'course:' . $courseid),
            'isglobal' => 1, 'archived' => (int)$archived, 'timearchived' => $archived ? 200 : 0,
            'timecreated' => 100, 'timemodified' => 150,
        ];
        $note->id = $DB->insert_record('local_sidenotes_notes', $note);
        tag_manager::set_for_note((int)$note->id, $userid, ['Private sample']);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id, 'component' => 'local_sidenotes',
            'filearea' => screenshot_manager::FILEAREA, 'itemid' => $note->id, 'filepath' => '/',
            'filename' => 'sample.png', 'mimetype' => 'image/png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6G9sAAAAASUVORK5CYII='));
        $DB->insert_record('local_sidenotes_import', (object)[
            'userid' => $userid, 'courseid' => $courseid, 'sourceid' => $note->id,
            'sourcecreated' => 100, 'noteid' => $note->id, 'timeimported' => 120,
        ]);
        return $note;
    }

    /** Verify unchanged fields, attachment IDs, and receipt discovery. */
    private function assert_retained(\stdClass $before, string $name, int $reason): \stdClass {
        global $DB;
        $after = $DB->get_record('local_sidenotes_notes', ['id' => $before->id], '*', MUST_EXIST);
        foreach (['userid','content','contentformat','quote','quoteurl','url','pagetitle',
                'timecreated','timemodified','archived','timearchived'] as $field) {
            $this->assertEquals($before->$field, $after->$field, $field);
        }
        $this->assertEquals(0, $after->courseid);
        $this->assertEquals(0, $after->isglobal);
        $this->assertSame(access_policy::unbound_hash(), $after->pagehash);
        $this->assertEquals($before->courseid, $after->retainedcourseid);
        $this->assertSame($name, $after->retainedcoursename);
        $this->assertEquals($reason, $after->retentionreason);
        $this->assertCount(1, screenshot_manager::get_stored_files_for_note((int)$after->id));
        $this->assertCount(1, tag_manager::get_for_note((int)$after->id, (int)$after->userid));
        $this->assertEquals(0, $DB->get_field('local_sidenotes_import', 'courseid', ['noteid' => $after->id]));
        return $after;
    }

    /** Removing one of two real enrolments never removes any private data. */
    public function test_multiple_enrolments_and_missing_last_flag(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $owner = $gen->create_user();
        $gen->enrol_user($owner->id, $course->id, 'student');
        $first = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        $plugin = enrol_get_plugin('manual');
        $secondplugin = enrol_get_plugin('self');
        $secondid = $secondplugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
        $second = $DB->get_record('enrol', ['id' => $secondid]);
        $secondplugin->enrol_user($second, $owner->id);
        $note = $this->note($owner->id, $course->id);
        $plugin->unenrol_user($first, $owner->id);
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $note->id]));
        $fallback = \core\event\user_enrolment_deleted::create([
            'context' => \context_course::instance($course->id), 'objectid' => 999,
            'relateduserid' => $owner->id, 'other' => ['enrol' => 'manual', 'userenrolment' => []],
        ]);
        observers::user_enrolment_deleted($fallback);
        $this->assertTrue($DB->record_exists('local_sidenotes_import', ['noteid' => $note->id]));
        $this->assertCount(1, screenshot_manager::get_stored_files_for_note((int)$note->id));
        $this->assertCount(1, tag_manager::get_for_note((int)$note->id, (int)$owner->id));
        $secondplugin->unenrol_user($second, $owner->id);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $note->id]));
        observers::user_enrolment_deleted($fallback);
        $this->assertFalse($DB->record_exists('local_sidenotes_import', ['noteid' => $note->id]));
    }

    /** A former learner can read/delete their own retained records but cannot create or change notes. */
    public function test_retained_notes_do_not_grant_authoring_or_foreign_access(): void {
        global $DB;
        set_config('preserve_on_unenrol', 1, 'local_sidenotes');
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $owner = $gen->create_user();
        $other = $gen->create_user();
        $gen->enrol_user($owner->id, $course->id, 'student');
        $note = $this->note($owner->id, $course->id);
        $archived = $this->note($owner->id, $course->id, true);
        $manual = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        enrol_get_plugin('manual')->unenrol_user($manual, $owner->id);
        $after = $this->assert_retained($note, $course->fullname, 1);
        $this->assert_retained($archived, $course->fullname, 1);
        retention_manager::unenrol($owner->id, $course->id); // Duplicate transition is harmless.
        $this->setUser($owner);
        $this->assertTrue(access_policy::overview_allowed());
        $this->assertFalse(access_policy::can_create_overview());
        $this->assertFalse(access_policy::can_edit($after));
        $this->assertFalse(access_policy::page_allowed($course->id, $note->url));
        try {
            \local_sidenotes\local\quicknote_importer::import_own();
            $this->fail('Reading retained notes must not grant import permission.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('access:denied', $exception->errorcode);
        }
        foreach ([['create', 0], ['content', $note->id], ['global', $note->id]] as [$operation, $id]) {
            try {
                \local_sidenotes\external\edit_note::execute($id, $operation);
                $this->fail('Retained data must not grant authoring: ' . $operation);
            } catch (\moodle_exception $exception) {
                $this->assertSame('access:denied', $exception->errorcode);
            }
        }
        $ctx = \context_system::instance();
        $this->assertSame([$ctx->id], array_map('intval', provider::get_contexts_for_userid($owner->id)->get_contextids()));
        $approved = new \core_privacy\local\request\approved_contextlist($owner, 'local_sidenotes', [$ctx->id]);
        provider::export_user_data($approved);
        $export = \core_privacy\local\request\writer::with_context($ctx)->get_data([
            get_string('pluginname', 'local_sidenotes'), $note->id,
        ]);
        $this->assertEquals($course->id, $export->retainedcourseid);
        $this->assertSame($course->fullname, $export->retainedcoursename);
        $this->setUser($other);
        $this->assertFalse(access_policy::overview_allowed());
        $this->setGuestUser();
        $this->assertFalse(access_policy::overview_allowed());
        $this->setUser($owner);
        \local_sidenotes\external\delete_note::execute((int)$note->id);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $note->id]));
        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['userid' => $owner->id]));
        $this->assertFalse($DB->record_exists('local_sidenotes_import', ['userid' => $owner->id]));
        $this->assertCount(0, screenshot_manager::get_stored_files_for_note((int)$archived->id));
        $this->assertCount(0, tag_manager::get_for_note((int)$archived->id, (int)$owner->id));
    }

    /** Both retention policies are independent, including Moodle's internal unenrolment during deletion. */
    public static function deletion_policies(): array {
        return ['delete both' => [false, false], 'keep only unenrolment' => [true, false],
            'keep only course deletion' => [false, true], 'keep both' => [true, true]];
    }

    /**
     * Execute native deletion followed by its captured background action.
     * @dataProvider deletion_policies
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('deletion_policies')]
    public function test_course_deletion_uses_its_own_captured_policy(bool $unenrol, bool $preserve): void {
        global $DB;
        set_config('preserve_on_unenrol', (int)$unenrol, 'local_sidenotes');
        set_config('preserve_on_course_delete', (int)$preserve, 'local_sidenotes');
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $owner = $gen->create_user();
        $gen->enrol_user($owner->id, $course->id, 'student');
        $note = $this->note($owner->id, $course->id, true);
        delete_course($course, false);
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $note->id, 'courseid' => $course->id]));
        $row = $DB->get_record('task_adhoc', ['component' => 'local_sidenotes'], '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($row);
        set_config('preserve_on_course_delete', (int)!$preserve, 'local_sidenotes');
        $task->execute();
        $task->execute();
        if ($preserve) {
            $this->assert_retained($note, $course->fullname, 2);
            delete_user($owner);
            $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $note->id]));
            $this->assertFalse($DB->record_exists('local_sidenotes_import', ['userid' => $owner->id]));
        } else {
            $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $note->id]));
            $this->assertCount(0, screenshot_manager::get_stored_files_for_note((int)$note->id));
        }
    }

    /** Every continuation batch retains the original action even after configuration changes. */
    public function test_preserve_course_job_is_bounded_and_idempotent(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $owner = $gen->create_user();
        set_config('preserve_on_course_delete', 1, 'local_sidenotes');
        $first = $this->note($owner->id, $course->id);
        for ($i = 0; $i < 200; $i++) {
            $record = clone $first;
            unset($record->id);
            $DB->insert_record('local_sidenotes_notes', $record);
        }
        delete_course($course, false);
        $row = $DB->get_record('task_adhoc', ['component' => 'local_sidenotes'], '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($row);
        $task->execute();
        $DB->delete_records('task_adhoc', ['id' => $row->id]);
        $this->assertSame(1, $DB->count_records('local_sidenotes_notes', ['courseid' => $course->id]));
        $next = $DB->get_record('task_adhoc', ['component' => 'local_sidenotes'], '*', MUST_EXIST);
        $task = \core\task\manager::adhoc_task_from_record($next);
        $this->assertTrue((bool)$task->get_custom_data()->preserve);
        $task->execute();
        $task->execute();
        $this->assertSame(201, $DB->count_records('local_sidenotes_notes', ['retainedcourseid' => $course->id]));
        $this->assertSame(0, $DB->count_records('local_sidenotes_notes', ['courseid' => $course->id]));
    }
}
