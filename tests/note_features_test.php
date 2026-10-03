<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes;

use local_sidenotes\external\delete_note;
use local_sidenotes\external\save_note;
use local_sidenotes\local\tag_manager;

/**
 * Tests Markdown migration and private tags.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class note_features_test extends \advanced_testcase {
    public function test_plain_note_only_becomes_markdown_after_content_change(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sitewideenabled', 1, 'local_sidenotes');
        $CFG->usetags = true;
        // PHPUnit's installed baseline already contains our tag area. Re-registering definitions twice
        // in one PHP process is not safe: core loads db/tag.php with require_once.
        $this->assertTrue(tag_manager::is_enabled());

        $course = $this->getDataGenerator()->create_course();
        $url = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        $created = save_note::execute(
            0,
            (int) $course->id,
            'Legacy text',
            $url,
            'Test course',
            false,
            '',
            '',
            ['Review'],
            true
        );

        $DB->set_field('local_sidenotes_notes', 'contentformat', FORMAT_PLAIN, ['id' => $created['id']]);
        $toggled = save_note::execute(
            $created['id'],
            (int) $course->id,
            'Legacy text',
            $url,
            'Test course',
            true
        );
        $this->assertSame((int) FORMAT_PLAIN, $toggled['contentformat']);

        $edited = save_note::execute(
            $created['id'],
            (int) $course->id,
            "Legacy text\n\n**Bold**",
            $url,
            'Test course',
            true
        );
        $this->assertSame((int) FORMAT_MARKDOWN, $edited['contentformat']);
        $this->assertStringContainsString('<strong>Bold</strong>', $edited['contenthtml']);
        $this->assertSame('Review', $edited['tags'][0]['name']);

        $task = \local_sidenotes\external\edit_note::execute($created['id'], 'content',
            "- [ ] First\r\n- [x] Done", $edited['content']);
        $task = \local_sidenotes\external\edit_note::execute($created['id'], 'task', '', $task['content'], [], false, 0, true);
        $this->assertSame("- [x] First\r\n- [x] Done", $task['content']);
        $this->assertStringContainsString('data-taskline="0"', $task['contenthtml']);
        $this->assertSame('Review', $task['tags'][0]['name']);

        delete_note::execute($created['id']);
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $created['id']]));
        $this->assertFalse($DB->record_exists('tag_instance', [
            'component' => tag_manager::COMPONENT,
            'itemtype' => tag_manager::ITEMTYPE,
            'itemid' => $created['id'],
        ]));
    }

    public function test_tag_normalisation_is_bounded_and_deduplicated(): void {
        $tags = tag_manager::normalise([' Review ', 'review', '', 'Accessibility']);
        $this->assertSame(['Review', 'Accessibility'], $tags);
    }
}
