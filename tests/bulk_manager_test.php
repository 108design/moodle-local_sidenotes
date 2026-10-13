<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes;

use local_sidenotes\external\edit_note;
use local_sidenotes\local\bulk_manager;
use local_sidenotes\local\tag_manager;

/**
 * Owner boundaries, snapshot changes and all-or-nothing mutations.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_sidenotes\local\bulk_manager
 * @covers \local_sidenotes\local\note_filters
 */
final class bulk_manager_test extends \advanced_testcase {
    private function setup_owner(): int {
        global $USER, $CFG;
        $this->resetAfterTest(); $this->setAdminUser(); $CFG->usetags = true;
        set_config('sitewideenabled', 1, 'local_sidenotes');
        return (int) $USER->id;
    }
    private function apply(int $userid, array $ids, bool $archive, string $action, array $tags = []): array {
        $preview = bulk_manager::execute($userid, $ids, $archive, $action, $tags, '', true);
        return bulk_manager::execute($userid, $ids, $archive, $action, $tags, $preview['revision']);
    }
    public function test_archive_restore_and_delete_preserve_scope_and_import_receipts(): void {
        global $DB;
        $userid = $this->setup_owner();
        $one = edit_note::execute(0, 'create', 'One'); $two = edit_note::execute(0, 'create', 'Two');
        $image = \local_sidenotes\local\screenshot_manager::create(
            $DB->get_record('local_sidenotes_notes', ['id' => $one['id']]), 'synthetic.png', 'image/png',
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lU0AAAAASUVORK5CYII=');
        edit_note::execute($one['id'], 'tags', '', '', ['Preserve']);
        $DB->insert_record('local_sidenotes_import', (object) ['userid' => $userid, 'courseid' => 0,
            'sourceid' => 77, 'sourcecreated' => 1, 'noteid' => $one['id'], 'timeimported' => 1]);
        $before = $DB->get_record('local_sidenotes_notes', ['id' => $one['id']]);
        $this->apply($userid, [$one['id'], $two['id']], false, 'archive');
        $this->assertSame(2, $DB->count_records('local_sidenotes_notes', ['userid' => $userid, 'archived' => 1]));
        $this->apply($userid, [$one['id']], true, 'restore');
        $this->assertEquals($before, $DB->get_record('local_sidenotes_notes', ['id' => $one['id']]));
        $this->apply($userid, [$one['id']], false, 'archive');
        $this->apply($userid, [$one['id']], true, 'delete');
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $one['id']]));
        $this->assertTrue($DB->record_exists('local_sidenotes_import', ['noteid' => $one['id']]));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $two['id']]));
        $this->assertFalse($DB->record_exists('files', ['id' => $image['id']]));
        $this->assertSame([], tag_manager::get_for_note($one['id'], $userid));
    }
    public function test_foreign_id_rejects_entire_selection(): void {
        global $DB;
        $userid = $this->setup_owner(); $one = edit_note::execute(0, 'create', 'Owned');
        $foreign = edit_note::execute(0, 'create', 'Foreign');
        $other = $this->getDataGenerator()->create_user();
        $DB->set_field('local_sidenotes_notes', 'userid', $other->id, ['id' => $foreign['id']]);
        try {bulk_manager::execute($userid, [$one['id'], $foreign['id']], false, 'archive', [], '', true);
            $this->fail('Foreign selection accepted');
        } catch (\moodle_exception $e) {$this->assertSame('bulk:changed', $e->errorcode);}
        $this->assertEquals(0, $DB->get_field('local_sidenotes_notes', 'archived', ['id' => $one['id']]));
    }
    public function test_changed_snapshot_rejects_whole_batch(): void {
        global $DB;
        $userid = $this->setup_owner(); $one = edit_note::execute(0, 'create', 'One'); $two = edit_note::execute(0, 'create', 'Two');
        $ids = [$one['id'], $two['id']]; $preview = bulk_manager::execute($userid, $ids, false, 'archive', [], '', true);
        // Same-second content/tag changes must not be hidden by a timestamp-only revision.
        edit_note::execute($two['id'], 'tags', '', '', ['Changed']);
        try {bulk_manager::execute($userid, $ids, false, 'archive', [], $preview['revision']); $this->fail('Stale snapshot accepted');}
        catch (\moodle_exception $e) {$this->assertSame('bulk:changed', $e->errorcode);}
        $this->assertSame(0, $DB->count_records('local_sidenotes_notes', ['userid' => $userid, 'archived' => 1]));
    }
    public function test_add_remove_tags_global_and_filtered_selection(): void {
        global $DB;
        $userid = $this->setup_owner(); $one = edit_note::execute(0, 'create', 'Find this'); $two = edit_note::execute(0, 'create', 'Other');
        edit_note::execute($one['id'], 'tags', '', '', ['Keep']);
        $ids = [$one['id'], $two['id']]; $this->apply($userid, $ids, false, 'addtags', ['Demo']);
        $this->apply($userid, $ids, false, 'globalon');
        $this->assertSame(2, $DB->count_records('local_sidenotes_notes', ['userid' => $userid, 'isglobal' => 1]));
        $tags = array_column(tag_manager::get_for_note($one['id'], $userid), null, 'name');
        $this->assertSame([$one['id']], bulk_manager::select($userid, false, -1, [$tags['Keep']['id'], $tags['Demo']['id']], 'Find'));
        $this->assertSame([], bulk_manager::select($userid, false, 0, [], '%_literal'));
        $this->apply($userid, $ids, false, 'removetags', ['demo']);
        $this->assertSame(['Keep'], array_column(tag_manager::get_for_note($one['id'], $userid), 'name'));
        $this->apply($userid, $ids, false, 'globaloff');
        $this->assertSame(0, $DB->count_records('local_sidenotes_notes', ['userid' => $userid, 'isglobal' => 1]));
    }
    public function test_tag_limit_rolls_back_earlier_note_changes(): void {
        $userid = $this->setup_owner(); $one = edit_note::execute(0, 'create', 'One'); $two = edit_note::execute(0, 'create', 'Two');
        edit_note::execute($two['id'], 'tags', '', '', array_map(static fn(int $id): string => 'Tag' . $id, range(1, 20)));
        try {$this->apply($userid, [$one['id'], $two['id']], false, 'addtags', ['Excess']); $this->fail('Overflow accepted');}
        catch (\moodle_exception $e) {$this->assertSame('bulk:taglimit', $e->errorcode);}
        $this->assertSame([], tag_manager::get_for_note($one['id'], $userid));
        $this->assertCount(20, tag_manager::get_for_note($two['id'], $userid));
    }
    public function test_archived_tags_cannot_be_changed_and_shortcut_is_personal(): void {
        $userid = $this->setup_owner(); $one = edit_note::execute(0, 'create', 'One');
        $this->apply($userid, [$one['id']], false, 'archive');
        try {bulk_manager::execute($userid, [$one['id']], true, 'addtags', ['Forbidden'], '', true); $this->fail('Archive edited');}
        catch (\invalid_parameter_exception $e) {$this->assertNotEmpty($e->getMessage());}
        $this->assertFalse(\local_sidenotes\external\set_shortcut::execute(false));
        $this->assertEquals(0, get_user_preferences('local_sidenotes_shortcut', 1, $userid));
        $other = $this->getDataGenerator()->create_user();
        $this->assertEquals(1, get_user_preferences('local_sidenotes_shortcut', 1, $other->id));
        // Preference-only owners must also appear, without relying on an existing note.
        set_user_preference('local_sidenotes_shortcut', 0, $other->id);
        $contexts = \local_sidenotes\privacy\provider::get_contexts_for_userid((int) $other->id);
        $this->assertContains((int) \context_system::instance()->id, array_map('intval', $contexts->get_contextids()));
        \local_sidenotes\privacy\provider::delete_data_for_user(new \core_privacy\local\request\approved_contextlist(
            $other, 'local_sidenotes', [\context_system::instance()->id]));
        $this->assertEquals(1, get_user_preferences('local_sidenotes_shortcut', 1, $other->id));
    }
    public function test_learner_global_does_not_enable_out_of_course_notes(): void {
        $this->resetAfterTest();
        set_config('sitewideenabled', 0, 'local_sidenotes'); set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
        $course = $this->getDataGenerator()->create_course(); $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student'); $this->setUser($user);
        $note = edit_note::execute(0, 'create', 'Private overview');
        $this->apply((int) $user->id, [$note['id']], false, 'globalon');
        $this->assertFalse(\local_sidenotes\local\access_policy::page_allowed(0, (new \moodle_url('/admin/index.php'))->out(false)));
        $this->assertTrue(\local_sidenotes\local\access_policy::page_allowed($course->id, (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false)));
    }
    public function test_unavailable_owned_note_rejects_the_entire_batch(): void {
        global $DB;
        $this->resetAfterTest(); set_config('sitewideenabled', 0, 'local_sidenotes');
        set_config('courseenabled', 1, 'local_sidenotes'); set_config('coursedefault', 1, 'local_sidenotes');
        $course = $this->getDataGenerator()->create_course(); $othercourse = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student'); $this->setUser($user);
        $one = edit_note::execute(0, 'create', 'Allowed'); $two = edit_note::execute(0, 'create', 'Unavailable');
        $url = (new \moodle_url('/course/view.php', ['id' => $othercourse->id]))->out(false);
        $DB->update_record('local_sidenotes_notes', (object) ['id' => $two['id'], 'courseid' => $othercourse->id,
            'url' => $url, 'pagehash' => \local_sidenotes\local\page_identity::hash($url)]);
        try {bulk_manager::execute((int) $user->id, [$one['id'], $two['id']], false, 'archive', [], '', true);
            $this->fail('Unavailable owned source accepted');
        } catch (\moodle_exception $e) {$this->assertSame('access:denied', $e->errorcode);}
        $this->assertSame(0, $DB->count_records('local_sidenotes_notes', ['userid' => $user->id, 'archived' => 1]));
    }
    public function test_selection_ids_are_bounded_and_normalised(): void {
        $this->assertSame([2, 5], bulk_manager::ids([5, 2, 5]));
        $this->expectException(\invalid_parameter_exception::class);
        bulk_manager::ids(range(1, 501));
    }
}
