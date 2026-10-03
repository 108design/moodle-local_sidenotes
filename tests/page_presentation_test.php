<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.

namespace local_sidenotes;

use local_sidenotes\local\page_presentation;

/**
 * Display labels must not damage page names or their source metadata.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_presentation_test extends \advanced_testcase {
    public function test_only_exact_site_suffix_is_removed(): void {
        $names = ['Example Site', 'Site'];
        $this->assertSame('Edit page', page_presentation::title('Edit page | Site', $names));
        $this->assertSame('Edit page', page_presentation::title('Edit page | Example Site ', $names));
        $this->assertSame('Page | Course', page_presentation::title('Page | Course | Site', $names));
        $this->assertSame('Page | Other', page_presentation::title('Page | Other', $names));
        $this->assertSame('Site', page_presentation::title('Site', $names));
        $this->assertSame(' | Site', page_presentation::title(' | Site', $names));
        $this->assertSame('Page | Site', page_presentation::title('Page | Site', ['']));
        $this->assertSame('Überblick', page_presentation::title('Überblick | Moodle (QA)+', ['Moodle (QA)+']));
    }
}
