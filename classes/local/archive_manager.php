<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\local;

/**
 * Owner-private archive operations with guarded, exact-snapshot deletion.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class archive_manager {
    /** Serialize archive membership changes and deletion for one owner. */
    private static function owner_lock(int $userid): \core\lock\lock {
        $lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('archive-owner-' . $userid, 10);
        if (!$lock) {throw new \moodle_exception('error:busy', 'local_sidenotes');}
        return $lock;
    }

    /** Public status contains counts and a revision, not any other owner's records. */
    public static function status(int $userid): array {
        global $DB;
        $records = $DB->get_records('local_sidenotes_notes', ['userid' => $userid, 'archived' => 1],
            'id ASC', 'id,timearchived,timemodified');
        return ['activecount' => $DB->count_records('local_sidenotes_notes', ['userid' => $userid, 'archived' => 0]),
            'archivecount' => count($records), 'revision' => hash('sha256', $userid . ':' . json_encode(array_values($records)))];
    }

    /** Set one owned note's state; preserve all original content, timestamps and source metadata. */
    public static function set_state(int $noteid, int $userid, bool $archived): void {
        global $DB;
        $ownerlock = self::owner_lock($userid);
        $notelock = null;
        try {
            $notelock = access_policy::note_lock($noteid);
            $note = $DB->get_record('local_sidenotes_notes', ['id' => $noteid, 'userid' => $userid], '*', MUST_EXIST);
            if ((bool) $note->archived !== $archived) {
                $DB->update_record('local_sidenotes_notes', (object) ['id' => $noteid,
                    'archived' => (int) $archived, 'timearchived' => $archived ? time() : 0]);
            }
        } finally {
            if ($notelock) {$notelock->release();}
            $ownerlock->release();
        }
    }

    /** Delete one owned note, optionally requiring the archive state displayed by the client. */
    public static function delete_owned(int $noteid, int $userid, int $expectedarchived = -1): void {
        global $DB;
        $ownerlock = self::owner_lock($userid);
        $notelock = null;
        try {
            $notelock = access_policy::note_lock($noteid);
            $note = $DB->get_record('local_sidenotes_notes', ['id' => $noteid, 'userid' => $userid], '*', MUST_EXIST);
            if ($expectedarchived >= 0 && (int) $note->archived !== $expectedarchived) {
                throw new \moodle_exception('archive:changed', 'local_sidenotes');
            }
            self::purge([$note], $userid);
        } finally {
            if ($notelock) {$notelock->release();}
            $ownerlock->release();
        }
    }

    /** Empty only the same owner's exact archive snapshot confirmed in the UI, ignoring search filters. */
    public static function empty_owned(int $userid, string $revision): int {
        global $DB;
        $ownerlock = self::owner_lock($userid);
        $locks = [];
        try {
            $notes = $DB->get_records('local_sidenotes_notes', ['userid' => $userid, 'archived' => 1], 'id ASC');
            foreach ($notes as $note) {$locks[] = access_policy::note_lock((int) $note->id);}
            if ($revision === '' || !hash_equals(self::status($userid)['revision'], $revision)) {
                throw new \moodle_exception('archive:changed', 'local_sidenotes');
            }
            self::purge($notes, $userid);
            return count($notes);
        } finally {
            foreach (array_reverse($locks) as $lock) {$lock->release();}
            $ownerlock->release();
        }
    }

    /** Caller already holds the membership and note locks. Receipts remain to prevent resurrection by import. */
    private static function purge(array $notes, int $userid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach ($notes as $note) {
                screenshot_manager::delete_for_note((int) $note->id);
                tag_manager::remove_for_note((int) $note->id, $userid);
                $DB->delete_records('local_sidenotes_notes', ['id' => $note->id, 'userid' => $userid]);
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }
}
