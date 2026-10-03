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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_sidenotes
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sidenotes;

/**
 * Class observers for SideNotes.
 *
 * @package    local_sidenotes
 */
class observers {
    /**
     * Delete the per-course synthetic config when a course is deleted.
     *
     * @param \core\event\course_deleted $event The event triggered.
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $courseid = $event->objectid;

        // Remove any notes still attached to this course (covers non-enrolled authors).
        self::delete_notes(['courseid' => $courseid]);

        // Remove the per-course configuration.
        $DB->delete_records('local_sidenotes_course', ['courseid' => $courseid]);
    }

    /**
     * Delete all notes for a user in a course when they are unenrolled.
     *
     * @param \core\event\user_enrolment_deleted $event The event triggered.
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $DB;

        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        self::delete_notes([
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
    }

    /**
     * Delete all notes across all courses when a user is fully deleted.
     *
     * @param \core\event\user_deleted $event The event triggered.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;
        \local_sidenotes\local\tag_manager::clear_colours((int) $userid);
        $DB->delete_records('local_sidenotes_import', ['userid' => $userid]);

        self::delete_notes([
            'userid' => $userid,
        ]);
    }

    /** Delete matching notes together with their private screenshot areas. */
    private static function delete_notes(array $conditions): void {
        global $DB;

        $notes = $DB->get_records('local_sidenotes_notes', $conditions, '', 'id,userid');
        foreach ($notes as $note) {
            \local_sidenotes\local\screenshot_manager::delete_for_note((int) $note->id);
            \local_sidenotes\local\tag_manager::remove_for_note((int) $note->id, (int) $note->userid);
        }
        $DB->delete_records('local_sidenotes_notes', $conditions);
        $DB->delete_records('local_sidenotes_import', $conditions);
    }
}
