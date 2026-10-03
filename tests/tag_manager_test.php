<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes;

use local_sidenotes\external\edit_note;
use local_sidenotes\external\manage_tags;
use local_sidenotes\local\tag_manager;

/** Private category lifecycle, even when Moodle shares a tag definition between users.
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tag_manager_test extends \advanced_testcase {
    public function test_private_rename_colour_delete_and_contrast(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->usetags = true;
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
        set_config('sitewideenabled', 0, 'local_sidenotes');
        $this->assertTrue(tag_manager::is_enabled(), 'Use the installed private area, not repeated require_once registration.');
        $course = $this->getDataGenerator()->create_course();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        foreach ([$first, $second] as $user) {$this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');}
        $this->setUser($first);
        $one = edit_note::execute(0, 'create', 'First private note');
        $one = edit_note::execute($one['id'], 'tags', '', '', ['Shared label']);
        $tagid = $one['tags'][0]['id'];
        $this->setUser($second);
        $two = edit_note::execute(0, 'create', 'Second private note');
        $two = edit_note::execute($two['id'], 'tags', '', '', ['Shared label']);
        $this->assertSame($tagid, $two['tags'][0]['id']);
        $this->setUser($first);
        $result = manage_tags::execute('update', $tagid, 'Renamed privately', '#112233');
        $this->assertSame('Renamed privately', $result['tags'][0]['name']);
        $newid = $result['tags'][0]['id'];
        $this->assertSame('#112233', $result['tags'][0]['background']);
        $this->assertSame('#ffffff', $result['tags'][0]['foreground']);
        $this->assertTrue($result['tags'][0]['customcolour']);
        $this->assertSame('Shared label', tag_manager::get_for_note($two['id'], $second->id)[0]['name']);
        $this->assertFalse(tag_manager::get_for_note($two['id'], $second->id)[0]['customcolour']);
        $result = manage_tags::execute('update', $newid, 'Renamed privately', '');
        $this->assertFalse($result['tags'][0]['customcolour']);
        $this->assertSame($result['tags'][0]['automatic'], $result['tags'][0]['background']);
        $one = edit_note::execute($one['id'], 'tags', '', '', ['Renamed privately', 'Merge target']);
        $tags = array_column($one['tags'], null, 'name');
        manage_tags::execute('update', $tags['Merge target']['id'], 'Merge target', '#abcdef');
        manage_tags::execute('update', $newid, 'Merge target', '');
        $merged = tag_manager::get_for_note($one['id'], $first->id);
        $this->assertCount(1, $merged);
        $this->assertSame('#abcdef', $merged[0]['background']);
        $newid = $merged[0]['id'];
        manage_tags::execute('delete', $newid);
        $this->assertSame([], tag_manager::get_for_note($one['id'], $first->id));
        $this->assertCount(1, tag_manager::get_for_note($two['id'], $second->id));
        $this->assertSame('First private note', $DB->get_field('local_sidenotes_notes', 'content', ['id' => $one['id']]));
        $this->assertSame('#000000', tag_manager::contrast('#ffffff'));
        $this->assertSame('#ffffff', tag_manager::contrast('#000000'));
        $this->expectException(\invalid_parameter_exception::class);
        manage_tags::execute('delete', $tagid);
    }
}
