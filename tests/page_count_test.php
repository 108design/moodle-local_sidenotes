<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes;

use local_sidenotes\external\get_page_count;
use local_sidenotes\external\save_note;
use local_sidenotes\external\edit_note;
use local_sidenotes\local\access_policy;

/** Count-only endpoint privacy, page identity and availability regressions.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_count_test extends \advanced_testcase {
    private function learner(): array {
        $this->resetAfterTest();
        set_config('sitewideenabled', 0, 'local_sidenotes');
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $generator->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        return [$user, $course, (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false)];
    }

    public function test_count_excludes_global_archive_other_page_and_other_owner(): void {
        global $DB;
        [$user, $course, $url] = $this->learner();
        $this->assertSame(0, get_page_count::execute($course->id, $url));
        save_note::execute(0, $course->id, 'active', $url);
        save_note::execute(0, $course->id, 'global', $url, '', true);
        $archived = save_note::execute(0, $course->id, 'archived', $url);
        $DB->set_field('local_sidenotes_notes', 'archived', 1, ['id' => $archived['id']]);
        save_note::execute(0, $course->id, 'other page',
            (new \moodle_url('/course/resources.php', ['id' => $course->id]))->out(false));
        edit_note::execute(0, 'create', 'unbound');
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $this->setUser($other);
        save_note::execute(0, $course->id, 'another owner', $url);
        $this->setUser($user);
        $this->assertSame(1, get_page_count::execute($course->id, $url . '&sesskey=discard#section'));
        $DB->set_field('local_sidenotes_notes', 'archived', 0, ['id' => $archived['id']]);
        $this->assertSame(2, get_page_count::execute($course->id, $url));
        $this->assertSame(PARAM_INT, get_page_count::execute_returns()->type);
    }

    public function test_sitewide_count_uses_the_same_private_bound_scope(): void {
        global $DB;
        $this->resetAfterTest(); $this->setAdminUser(); set_config('sitewideenabled', 1, 'local_sidenotes');
        $url = (new \moodle_url('/admin/search.php'))->out(false);
        $note = save_note::execute(0, 0, 'active', $url);
        save_note::execute(0, 0, 'global', $url, '', true);
        edit_note::execute(0, 'create', 'unbound');
        $this->assertSame(1, get_page_count::execute(0, $url));
        $DB->set_field('local_sidenotes_notes', 'archived', 1, ['id' => $note['id']]);
        $this->assertSame(0, get_page_count::execute(0, $url));
    }

    public function test_student_cannot_count_site_or_forged_course_pages(): void {
        [$user, $course, $url] = $this->learner();
        $this->expectException(\moodle_exception::class);
        get_page_count::execute($course->id, (new \moodle_url('/admin/search.php'))->out(false));
    }

    public function test_disabled_course_is_not_countable(): void {
        global $DB;
        [$user, $course, $url] = $this->learner();
        save_note::execute(0, $course->id, 'active', $url);
        $DB->insert_record('local_sidenotes_course', (object) ['courseid' => $course->id, 'enabled' => 0]);
        $this->expectException(\moodle_exception::class);
        get_page_count::execute($course->id, $url);
    }

    public function test_foreign_host_is_rejected_in_sitewide_mode(): void {
        $this->resetAfterTest(); $this->setAdminUser(); set_config('sitewideenabled', 1, 'local_sidenotes');
        $this->expectException(\invalid_parameter_exception::class);
        get_page_count::execute(0, 'https://foreign.invalid/course/view.php?id=2');
    }
}
