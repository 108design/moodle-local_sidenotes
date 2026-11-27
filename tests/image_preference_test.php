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

namespace local_sidenotes;

use local_sidenotes\external\set_image_preference;
use local_sidenotes\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Owner-private preference, including preference-only Privacy API records.
 *
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_sidenotes\external\set_image_preference
 * @covers \local_sidenotes\privacy\provider
 */
final class image_preference_test extends \advanced_testcase {
    /** Save only the caller's preference and discover/export it without any notes. */
    public function test_preference_is_personal_and_exportable(): void {
        $this->resetAfterTest();
        set_config('sitewideenabled', 0, 'local_sidenotes');
        set_config('courseenabled', 1, 'local_sidenotes');
        set_config('coursedefault', 1, 'local_sidenotes');
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($owner->id, $course->id, 'student');
        $this->setUser($owner);
        $this->assertSame(0, get_user_preferences('local_sidenotes_reduceimages', 0));
        $this->assertTrue(set_image_preference::execute(true));
        $this->assertEquals(1, get_user_preferences('local_sidenotes_reduceimages', 0, $owner->id));
        $this->assertSame(0, get_user_preferences('local_sidenotes_reduceimages', 0, $other->id));
        $context = \context_system::instance();
        $this->assertContains((int)$context->id,
            array_map('intval', provider::get_contexts_for_userid($owner->id)->get_contextids()));
        $users = new userlist($context, 'local_sidenotes');
        provider::get_users_in_context($users);
        $this->assertContains((int)$owner->id, array_map('intval', $users->get_userids()));
        $this->assertNotContains((int)$other->id, array_map('intval', $users->get_userids()));
        provider::export_user_preferences($owner->id);
        $export = writer::with_context($context)->get_user_preferences('local_sidenotes');
        $this->assertEquals(1, $export->local_sidenotes_reduceimages->value);
        $this->assertFalse(set_image_preference::execute(false));
        $this->assertEquals(0, get_user_preferences('local_sidenotes_reduceimages', null, $owner->id));
    }

    /** Each native erasure entry point covers preference-only users and its own approved scope. */
    public function test_preference_erasure_respects_approved_users(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        foreach ([$owner, $other] as $user) {
            set_user_preference('local_sidenotes_reduceimages', 1, $user->id);
        }
        provider::delete_data_for_user(new approved_contextlist($owner, 'local_sidenotes', [$context->id]));
        $this->assertNull(get_user_preferences('local_sidenotes_reduceimages', null, $owner->id));
        $this->assertEquals(1, get_user_preferences('local_sidenotes_reduceimages', null, $other->id));
        set_user_preference('local_sidenotes_reduceimages', 1, $owner->id);
        provider::delete_data_for_users(new approved_userlist($context, 'local_sidenotes', [$owner->id]));
        $this->assertNull(get_user_preferences('local_sidenotes_reduceimages', null, $owner->id));
        $this->assertEquals(1, get_user_preferences('local_sidenotes_reduceimages', null, $other->id));
        provider::delete_data_for_all_users_in_context($context);
        $this->assertNull(get_user_preferences('local_sidenotes_reduceimages', null, $other->id));
    }

    /** Guests must not write a preference. */
    public function test_guest_cannot_set_preference(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('access:denied', 'local_sidenotes'));
        set_image_preference::execute(true);
    }
}
