<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes;

use local_sidenotes\local\archive_manager;
use local_sidenotes\local\note_filters;

/**
 * Private archive membership and guarded permanent deletion.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_sidenotes\local\archive_manager
 * @covers \local_sidenotes\local\note_filters
 */
final class archive_manager_test extends \advanced_testcase {
    /** Create a plain private record without requiring a page/editor. */
    private function note(int $userid): \stdClass {
        global $DB;
        $note = (object) ['userid' => $userid, 'courseid' => 0, 'content' => 'literal **plain**',
            'contentformat' => FORMAT_PLAIN, 'quote' => 'Original quote', 'quoteurl' => '',
            'url' => '', 'pagehash' => \local_sidenotes\local\access_policy::unbound_hash(),
            'pagetitle' => 'Original title', 'isglobal' => 1, 'archived' => 0, 'timearchived' => 0,
            'timecreated' => 123, 'timemodified' => 456];
        $note->id = $DB->insert_record('local_sidenotes_notes', $note);
        return $DB->get_record('local_sidenotes_notes', ['id' => $note->id], '*', MUST_EXIST);
    }

    public function test_archive_and_restore_preserve_original_fields(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $note = $this->note($user->id);
        archive_manager::set_state($note->id, $user->id, true);
        $archived = $DB->get_record('local_sidenotes_notes', ['id' => $note->id]);
        $this->assertEquals(1, $archived->archived);
        $this->assertGreaterThan(0, $archived->timearchived);
        foreach ((array) $note as $key => $value) {
            if (!in_array($key, ['archived', 'timearchived'], true)) {$this->assertSame($value, $archived->$key);}
        }
        archive_manager::set_state($note->id, $user->id, false);
        $this->assertEquals($note, $DB->get_record('local_sidenotes_notes', ['id' => $note->id]));
    }

    public function test_foreign_note_cannot_be_restored(): void {
        $this->resetAfterTest();
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();
        $note = $this->note($one->id);
        archive_manager::set_state($note->id, $one->id, true);
        $this->expectException(\dml_missing_record_exception::class);
        archive_manager::set_state($note->id, $two->id, false);
    }

    public function test_changed_empty_snapshot_deletes_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $one = $this->note($user->id); $two = $this->note($user->id);
        archive_manager::set_state($one->id, $user->id, true);
        $revision = archive_manager::status($user->id)['revision'];
        archive_manager::set_state($two->id, $user->id, true);
        try {
            archive_manager::empty_owned($user->id, $revision);
            $this->fail('A changed archive must require a new confirmation.');
        } catch (\moodle_exception $e) {$this->assertSame('archive:changed', $e->errorcode);}
        $this->assertEquals(2, $DB->count_records('local_sidenotes_notes', ['userid' => $user->id]));
    }

    public function test_empty_keeps_active_foreign_notes_and_import_receipts(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(); $other = $this->getDataGenerator()->create_user();
        $archived = $this->note($user->id); $active = $this->note($user->id); $foreign = $this->note($other->id);
        archive_manager::set_state($archived->id, $user->id, true);
        archive_manager::set_state($foreign->id, $other->id, true);
        $DB->insert_record('local_sidenotes_import', (object) ['userid' => $user->id, 'courseid' => 0,
            'sourceid' => 99, 'sourcecreated' => 1, 'noteid' => $archived->id, 'timeimported' => 1]);
        $this->assertSame(1, archive_manager::empty_owned($user->id, archive_manager::status($user->id)['revision']));
        $this->assertFalse($DB->record_exists('local_sidenotes_notes', ['id' => $archived->id]));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $active->id]));
        $this->assertTrue($DB->record_exists('local_sidenotes_notes', ['id' => $foreign->id]));
        $this->assertTrue($DB->record_exists('local_sidenotes_import', ['noteid' => $archived->id]));
    }

    public function test_tag_filter_is_normalised_and_bound_for_every_tag(): void {
        $this->assertSame([3, 7], note_filters::normalise_tags([7, 3, 7, 0, -1]));
        [$sql, $params] = note_filters::tag_conditions([3, 7], 42);
        $this->assertSame(2, substr_count($sql, 'EXISTS'));
        $this->assertSame(3, $params['filtertag0id']);
        $this->assertSame(7, $params['filtertag1id']);
        $this->assertSame(42, $params['filtertag0owner']);
        $this->assertSame(42, $params['filtertag1owner']);
    }
}
