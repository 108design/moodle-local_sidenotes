<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for tests/quicknote_import_test.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen.
namespace local_sidenotes;
use local_sidenotes\local\access_policy;
use local_sidenotes\local\quicknote_importer;
use local_sidenotes\local\page_identity;

/** Conversion requires no QuickNote code, tables, renderer or installed plugin. */
final class quicknote_import_test extends \advanced_testcase {
    private function original(): \stdClass {
        return (object) ['id' => 7, 'userid' => 2, 'courseid' => 0, 'content' => '**Literal**\n- [ ] Literal',
            'quote' => 'Quote', 'quoteurl' => null,
            'url' => (new \moodle_url('/course/view.php', ['id' => 4]))->out(false),
            'timecreated' => 123456, 'timemodified' => 234567];
    }

    public function test_original_text_and_metadata_remain_literal(): void {
        $this->resetAfterTest();
        $source = $this->original();
        $target = quicknote_importer::convert($source);
        $this->assertSame((int) FORMAT_PLAIN, $target->contentformat);
        foreach (['userid', 'courseid', 'content', 'quote', 'quoteurl', 'url', 'timecreated', 'timemodified'] as $key) {
            $this->assertSame($source->$key, $target->$key);
        }
        $this->assertSame(page_identity::legacy_hash($source->url), $target->pagehash);
        $this->assertSame(0, $target->isglobal);
    }

    public function test_enhanced_unbound_and_own_routes_follow_the_rename(): void {
        global $CFG;
        $this->resetAfterTest();
        $source = $this->original();
        $source->contentformat = FORMAT_MARKDOWN;
        $source->pagehash = hash('sha256', 'quicknote:unbound');
        $source->url = '';
        $source->isglobal = 1;
        $source->pagetitle = 'Retained title';
        $target = quicknote_importer::convert($source);
        $this->assertSame(access_policy::unbound_hash(), $target->pagehash);
        $this->assertSame('', $target->url);
        $this->assertSame((int) FORMAT_MARKDOWN, $target->contentformat);
        $source->url = $CFG->wwwroot . '/local/quicknote/view.php';
        $source->quoteurl = $source->url . '#:~:text=Quote';
        $source->pagehash = page_identity::legacy_hash($source->url);
        $target = quicknote_importer::convert($source);
        $this->assertSame($CFG->wwwroot . '/local/sidenotes/view.php', $target->url);
        $this->assertSame($target->url . '#:~:text=Quote', $target->quoteurl);
        $this->assertSame(page_identity::hash($target->url), $target->pagehash);
    }

    public function test_product_name_is_never_translated(): void {
        $this->assertSame('Side Notes', get_string_manager()->get_string('pluginname', 'local_sidenotes', null, 'en'));
        $this->assertSame('Side Notes', get_string_manager()->get_string('pluginname', 'local_sidenotes', null, 'de'));
    }
}
