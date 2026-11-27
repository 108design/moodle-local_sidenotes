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
 * Real file storage and upload boundary regressions for personal image quotas.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sidenotes;

use local_sidenotes\external\upload_screenshot;
use local_sidenotes\external\save_note;
use local_sidenotes\local\screenshot_manager;

#[\PHPUnit\Framework\Attributes\CoversClass(screenshot_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(upload_screenshot::class)]
final class image_quota_test extends \advanced_testcase {
    /** Configure only synthetic admin-owned notes and real File API content. */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sitewideenabled', 1, 'local_sidenotes');
        set_config('imagequota', 1, 'local_sidenotes');
    }

    /** New notes use the same public service as the editor. */
    private function note(): int {
        return save_note::execute(0, 0, 'Synthetic quota note', (new \moodle_url('/'))->out(false), 'Test')['id'];
    }

    /** A supported PNG with deterministic padding exercises byte limits without a large bitmap. */
    private function image(int $bytes = 600000): string {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6G9sAAAAASUVORK5CYII=');
        return base64_encode(str_pad($png, $bytes, "\0"));
    }

    /** Multiple notes share one limit and deletion frees capacity. */
    public function test_uploads_share_owner_quota_and_deletion_frees_space(): void {
        global $USER;
        $first = $this->note();
        $second = $this->note();
        $file = upload_screenshot::execute($first, 'a.png', 'image/png', $this->image());
        $this->assertSame(600000, screenshot_manager::usage((int)$USER->id));
        try {
            upload_screenshot::execute($second, 'b.png', 'image/png', $this->image());
            $this->fail('Second note must share the quota.');
        } catch (\moodle_exception $error) {
            $this->assertSame('screenshot:quotaexceeded', $error->errorcode);
        }
        $this->assertCount(0, screenshot_manager::get_for_note($second));
        screenshot_manager::delete_file($file['id'], $first);
        upload_screenshot::execute($second, 'b.png', 'image/png', $this->image());
        $this->assertSame(600000, screenshot_manager::usage((int)$USER->id));
    }

    /** Archived/kept images still count and reducing the quota never deletes them. */
    public function test_existing_archived_and_kept_images_remain_and_count(): void {
        global $DB, $USER;
        set_config('imagequota', 0, 'local_sidenotes');
        $first = $this->note();
        $second = $this->note();
        upload_screenshot::execute($first, 'archive.png', 'image/png', $this->image());
        upload_screenshot::execute($second, 'kept.png', 'image/png', $this->image());
        $DB->set_field('local_sidenotes_notes', 'archived', 1, ['id' => $first]);
        $DB->set_field('local_sidenotes_notes', 'retainedcourseid', 123, ['id' => $second]);
        set_config('imagequota', 1, 'local_sidenotes');
        $this->assertSame(1200000, screenshot_manager::usage((int)$USER->id));
        $this->assertCount(1, screenshot_manager::get_for_note($first));
        $this->assertCount(1, screenshot_manager::get_for_note($second));
        $third = $this->note();
        screenshot_manager::require_capacity((int)$USER->id, 0);
        $this->expectException(\moodle_exception::class);
        upload_screenshot::execute($third, 'new.png', 'image/png', $this->image(100));
    }

    /** Foreign images do not consume the current owner's limit or become writable. */
    public function test_owner_isolation_and_upload_permissions(): void {
        global $DB, $USER;
        $first = $this->note();
        upload_screenshot::execute($first, 'a.png', 'image/png', $this->image());
        $other = $this->getDataGenerator()->create_user();
        $DB->set_field('local_sidenotes_notes', 'userid', $other->id, ['id' => $first]);
        $this->assertSame(0, screenshot_manager::usage((int)$USER->id));
        $this->assertSame(600000, screenshot_manager::usage((int)$other->id));
        $this->expectException(\dml_missing_record_exception::class);
        upload_screenshot::execute($first, 'forged.png', 'image/png', $this->image());
    }

    /** Keeping the quota unlimited does not waive existing per-note image limits. */
    public function test_per_note_limit_still_applies(): void {
        set_config('imagequota', 0, 'local_sidenotes');
        $id = $this->note();
        for ($i = 0; $i < 10; $i++) {
            upload_screenshot::execute($id, 'a.png', 'image/png', $this->image(100));
        }
        $this->assertCount(10, screenshot_manager::get_for_note($id));
        $this->expectException(\invalid_parameter_exception::class);
        upload_screenshot::execute($id, 'eleventh.png', 'image/png', $this->image(100));
    }

    /** Import cannot bypass quotas, leaves no partial receipts and remains repeatable. */
    public function test_import_quota_rollback_and_repeat_import(): void {
        global $DB, $USER;
        $manager = $DB->get_manager();
        $table = new \xmldb_table('local_quicknote_notes');
        $created = !$manager->table_exists($table);
        if ($created) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            foreach (['userid', 'courseid', 'timecreated', 'timemodified'] as $name) {
                $table->add_field($name, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            }
            foreach (['content', 'quote', 'quoteurl', 'url'] as $name) {
                $table->add_field($name, XMLDB_TYPE_TEXT);
            }
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $manager->create_table($table);
        }
        try {
            foreach ([1, 2] as $index) {
                $sourceid = $DB->insert_record('local_quicknote_notes', (object)[
                    'userid' => $USER->id, 'courseid' => 0, 'timecreated' => $index, 'timemodified' => $index,
                    'content' => 'Synthetic original', 'quote' => '', 'quoteurl' => '',
                    'url' => (new \moodle_url('/'))->out(false),
                ]);
                get_file_storage()->create_file_from_string([
                    'contextid' => \context_system::instance()->id, 'component' => 'local_quicknote',
                    'filearea' => 'screenshot', 'itemid' => $sourceid, 'filepath' => '/',
                    'filename' => 'source.png', 'userid' => $USER->id,
                ], base64_decode($this->image()));
            }
            try {
                \local_sidenotes\local\quicknote_importer::import_own();
                $this->fail('The imported images must share the quota.');
            } catch (\moodle_exception $error) {
                $this->assertSame('screenshot:quotaexceeded', $error->errorcode);
            }
            $this->assertSame(0, $DB->count_records('local_sidenotes_notes'));
            $this->assertSame(0, $DB->count_records('local_sidenotes_import'));
            $this->assertSame(0, screenshot_manager::usage((int)$USER->id));
            $this->assertSame(2, $DB->count_records('local_quicknote_notes', ['userid' => $USER->id]));
            set_config('imagequota', 2, 'local_sidenotes');
            $result = \local_sidenotes\local\quicknote_importer::import_own();
            $this->assertSame(2, $result['notes']);
            $this->assertSame(2, $result['screenshots']);
            $this->assertSame(1200000, screenshot_manager::usage((int)$USER->id));
            set_config('imagequota', 1, 'local_sidenotes');
            $repeat = \local_sidenotes\local\quicknote_importer::import_own();
            $this->assertSame(0, $repeat['notes']);
            $this->assertSame(2, $repeat['skipped']);
        } finally {
            if ($created) {
                $manager->drop_table($table);
            }
        }
    }
}
