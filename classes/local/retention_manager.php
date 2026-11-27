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
 * Personal retention of course notes without extending course access.
 *
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sidenotes\local;

/** Owns the transition from course-bound notes to personal read-only records. */
final class retention_manager {
    /** @var array In-request course deletion policies, captured before enrolments are removed. */
    private static array $deleting = [];

    /** Capture deletion intent before Moodle removes the course's enrolments. */
    public static function before_course_delete(\stdClass $course): void {
        self::$deleting[(int)$course->id] = [
            'preserve' => (bool)get_config('local_sidenotes', 'preserve_on_course_delete'),
            'coursename' => (string)$course->fullname,
        ];
    }

    /** Return the captured policy; an ordinary unenrolment has no such policy. */
    public static function deleting(int $courseid): ?array {
        return self::$deleting[$courseid] ?? null;
    }

    /** The native course-deleted event completes the request-local handover. */
    public static function forget(int $courseid): void {
        unset(self::$deleting[$courseid]);
    }

    /** Retained course notes are viewable personal records, never new sitewide notes. */
    public static function retained(\stdClass $note): bool {
        return !empty($note->retainedcourseid);
    }

    /** Return plain display text for one later escaping by Mustache or the export renderer. */
    public static function course_label(string $name): string {
        return html_entity_decode(strip_tags(format_string($name)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Handle one current record under the same owner/note locks as interactive changes.
     * Rechecking the course after locking makes repeated jobs harmless.
     */
    public static function process(int $noteid, int $courseid, bool $preserve, string $coursename, int $reason): void {
        global $DB;
        $userid = $DB->get_field('local_sidenotes_notes', 'userid', ['id' => $noteid, 'courseid' => $courseid]);
        if ($userid === false) {
            return;
        }
        $ownerlock = archive_manager::owner_lock((int)$userid);
        $notelock = null;
        $transaction = null;
        try {
            $notelock = access_policy::note_lock($noteid);
            $note = $DB->get_record('local_sidenotes_notes', ['id' => $noteid, 'courseid' => $courseid]);
            if (!$note) {
                return;
            }
            $transaction = $DB->start_delegated_transaction();
            if ($preserve) {
                $DB->update_record('local_sidenotes_notes', (object)[
                    'id' => $noteid, 'courseid' => 0, 'isglobal' => 0,
                    'pagehash' => access_policy::unbound_hash(),
                    'retainedcourseid' => $courseid,
                    'retainedcoursename' => \core_text::substr($coursename, 0, 255),
                    'retentionreason' => $reason,
                ]);
                // Receipts remain idempotent and discoverable by Privacy API in system context.
                $DB->set_field('local_sidenotes_import', 'courseid', 0, ['noteid' => $noteid, 'userid' => $userid]);
            } else {
                screenshot_manager::delete_for_note($noteid);
                tag_manager::remove_for_note($noteid, (int)$userid);
                $DB->delete_records('local_sidenotes_notes', ['id' => $noteid, 'courseid' => $courseid]);
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            if ($transaction) {
                $transaction->rollback($exception);
            }
            throw $exception;
        } finally {
            if ($notelock) {
                $notelock->release();
            }
            $ownerlock->release();
        }
    }

    /** Handle a final unenrolment synchronously, preserving the existing request behaviour. */
    public static function unenrol(int $userid, int $courseid): void {
        global $DB;
        $preserve = (bool)get_config('local_sidenotes', 'preserve_on_unenrol');
        $name = (string)$DB->get_field('course', 'fullname', ['id' => $courseid]);
        $notes = $DB->get_records('local_sidenotes_notes', ['userid' => $userid, 'courseid' => $courseid], 'id ASC', 'id');
        foreach ($notes as $note) {
            self::process((int)$note->id, $courseid, $preserve, $name, 1);
        }
        if ($preserve) {
            $DB->set_field('local_sidenotes_import', 'courseid', 0, ['userid' => $userid, 'courseid' => $courseid]);
        } else {
            $DB->delete_records('local_sidenotes_import', ['userid' => $userid, 'courseid' => $courseid]);
        }
    }
}
