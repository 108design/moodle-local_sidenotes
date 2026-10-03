<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes;

use local_sidenotes\local\note_formatter;

/** Tests the bounded SideNotes task-list enhancement around Moodle Markdown. */
final class note_formatter_test extends \advanced_testcase {
    public function test_tasks_at_start_are_interactive_and_toggle_only_the_marker(): void {
        $this->resetAfterTest();
        $content = "- [ ] First\r\n- [x] Done";
        $html = \local_sidenotes\local\note_formatter::format($content, FORMAT_MARKDOWN, \context_system::instance(), true);
        $this->assertStringContainsString('data-taskline="0"', $html);
        $this->assertStringContainsString('data-taskline="1"', $html);
        $this->assertStringNotContainsString('disabled=', $html);
        $changed = \local_sidenotes\local\note_formatter::toggle_task($content, 0, true, \context_system::instance());
        $this->assertSame("- [x] First\r\n- [x] Done", $changed);
        $pdf = \local_sidenotes\local\note_formatter::format($changed, FORMAT_MARKDOWN, \context_system::instance());
        $this->assertStringContainsString('disabled=', $pdf);
        $this->expectException(\invalid_parameter_exception::class);
        \local_sidenotes\local\note_formatter::toggle_task('ordinary text', 0, true, \context_system::instance());
    }
    public function test_markdown_task_markers_become_read_only_checkboxes(): void {
        $html = note_formatter::format(
            "- [ ] Open item\n- [x] Completed item\n- [X] Also completed",
            FORMAT_MARKDOWN,
            \context_system::instance()
        );

        $this->assertSame(3, substr_count($html, 'class="local-sidenotes-task-item"'));
        $this->assertSame(3, substr_count($html, 'class="local-sidenotes-task-checkbox"'));
        $this->assertSame(3, substr_count($html, 'disabled="disabled"'));
        $this->assertSame(2, substr_count($html, 'checked="checked"'));
        $this->assertStringNotContainsString('[ ] Open item', $html);
        $this->assertStringNotContainsString('[x] Completed item', $html);
        $this->assertStringContainsString('Open item', $html);
        $this->assertStringContainsString('Completed item', $html);
    }

    public function test_non_task_markers_and_plain_notes_remain_literal(): void {
        $markdown = note_formatter::format(
            "Paragraph [ ] text\n\n- [maybe] Not a task",
            FORMAT_MARKDOWN,
            \context_system::instance()
        );
        $plain = note_formatter::format('- [ ] Legacy plain note', FORMAT_PLAIN, \context_system::instance());

        $this->assertStringNotContainsString('local-sidenotes-task-checkbox', $markdown);
        $this->assertStringContainsString('[ ] text', $markdown);
        $this->assertStringContainsString('[maybe] Not a task', $markdown);
        $this->assertStringNotContainsString('local-sidenotes-task-checkbox', $plain);
        $this->assertStringContainsString('[ ] Legacy plain note', $plain);
    }
}
