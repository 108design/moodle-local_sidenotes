<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes;

use local_sidenotes\external\edit_note;
use local_sidenotes\external\get_notes;
use local_sidenotes\external\save_note;
use local_sidenotes\external\upload_screenshot;
use local_sidenotes\external\delete_screenshot;
use local_sidenotes\external\delete_note;
use local_sidenotes\local\access_policy;

/** Private inline editing and student-mode access regressions.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class center_access_test extends \advanced_testcase {
    private function learner(): array {
        global $CFG;
        $this->resetAfterTest();
        $CFG->usetags = true;
        set_config('sitewideenabled', 0, 'local_sidenotes');
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $url = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        return [$user, $course, $url];
    }

    public function test_course_mode_allows_unbound_and_scoped_globals(): void {
        [$user, $course, $url] = $this->learner();
        $this->assertTrue(access_policy::overview_allowed());
        $this->assertFalse(access_policy::sitewide());
        $this->assertFalse(access_policy::page_allowed($course->id,
            (new \moodle_url('/course/index.php', ['id' => $course->id]))->out(false)));
        $this->assertFalse(access_policy::page_allowed($course->id,
            (new \moodle_url('/course/search.php', ['id' => $course->id]))->out(false)));
        $note = edit_note::execute(0, 'create', '**Private overview**');
        $this->assertTrue($note['unbound']);
        $this->assertSame('', $note['url']);
        $this->assertCount(0, get_notes::execute($course->id, $url));
        edit_note::execute($note['id'], 'global', '', '', [], true);
        $this->assertCount(1, get_notes::execute($course->id, $url));
        edit_note::execute($note['id'], 'global', '', '', [], false);
        $this->assertCount(0, get_notes::execute($course->id, $url));
        $this->assertFalse(access_policy::page_allowed($course->id, (new \moodle_url('/admin/index.php'))->out(false)));
        $this->expectException(\moodle_exception::class);
        get_notes::execute(0, (new \moodle_url('/'))->out(false));
    }

    public function test_forged_course_url_cannot_create_student_admin_note(): void {
        [$user, $course, $url] = $this->learner();
        $this->expectException(\moodle_exception::class);
        save_note::execute(0, $course->id, 'Not permitted', (new \moodle_url('/admin/index.php'))->out(false));
    }

    public function test_unenrolled_courses_are_not_available(): void {
        [$user, $course, $url] = $this->learner();
        $other = $this->getDataGenerator()->create_course();
        $this->assertFalse(access_policy::course_allowed($other->id));
        $this->expectException(\moodle_exception::class);
        get_notes::execute($other->id);
    }

    public function test_partial_updates_preserve_source_and_plain_format(): void {
        global $DB;
        [$user, $course, $url] = $this->learner();
        $note = save_note::execute(0, $course->id, 'old text', $url, 'Source title', false, 'quote', $url . '#section');
        $DB->set_field('local_sidenotes_notes', 'contentformat', FORMAT_PLAIN, ['id' => $note['id']]);
        $updated = edit_note::execute($note['id'], 'tags', 'THIS MUST NOT REPLACE CONTENT', '', ['Private tag']);
        $this->assertSame('old text', $updated['content']);
        $this->assertSame((int) FORMAT_PLAIN, $updated['contentformat']);
        $this->assertSame('Source title', $updated['pagetitle']);
        $this->assertSame($url, $updated['url']);
        $this->assertSame('quote', $updated['quote']);
        $this->assertSame('Private tag', $updated['tags'][0]['name']);
        $edited = edit_note::execute($note['id'], 'content', '**new text**', 'old text');
        $this->assertSame((int) FORMAT_MARKDOWN, $edited['contentformat']);
        $this->assertStringContainsString('<strong>new text</strong>', $edited['contenthtml']);
        $this->assertSame('Private tag', $edited['tags'][0]['name']);
        $this->expectException(\moodle_exception::class);
        edit_note::execute($note['id'], 'content', 'stale overwrite', 'old text');
    }

    public function test_owner_boundary_and_private_screenshots(): void {
        [$user, $course, $url] = $this->learner();
        $note = edit_note::execute(0, 'create', 'Image note');
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
        $file = upload_screenshot::execute($note['id'], 'test.png', 'image/png', $png);
        $this->assertGreaterThan(0, $file['id']);
        $result = delete_screenshot::execute($note['id'], $file['id']);
        $this->assertTrue($result['deleted']);
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $this->setUser($other);
        $this->expectException(\dml_missing_record_exception::class);
        edit_note::execute($note['id'], 'tags', '', '', ['Stolen']);
    }

    public function test_course_disable_keeps_archive_but_blocks_edit(): void {
        global $DB;
        [$user, $course, $url] = $this->learner();
        $note = save_note::execute(0, $course->id, 'archive', $url);
        $DB->insert_record('local_sidenotes_course', (object) ['courseid' => $course->id, 'enabled' => 0]);
        $this->assertTrue(access_policy::overview_allowed());
        $record = $DB->get_record('local_sidenotes_notes', ['id' => $note['id']]);
        $this->assertFalse(access_policy::can_edit($record));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $note['id']]));
        delete_note::execute($note['id']);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $note['id']]));
    }

    public function test_course_activity_exclusion_cannot_be_bypassed(): void {
        global $DB;
        [$user, $course, $url] = $this->learner();
        $module = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $pageurl = (new \moodle_url('/mod/page/view.php', ['id' => $module->cmid]))->out(false);
        $this->assertTrue(access_policy::page_allowed($course->id, $pageurl));
        $DB->insert_record('local_sidenotes_course', (object) ['courseid' => $course->id, 'enabled' => 1,
            'module_settings' => json_encode([$module->cmid => 0])]);
        $this->assertFalse(access_policy::page_allowed($course->id, $pageurl));
        $this->assertFalse(access_policy::page_allowed($course->id,
            (new \moodle_url('/mod/forum/view.php', ['id' => $module->cmid]))->out(false)));
    }
}
