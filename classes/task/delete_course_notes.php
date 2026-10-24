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

namespace local_sidenotes\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Remove a deleted course's private notes without holding its HTTP request open.
 *
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class delete_course_notes extends \core\task\adhoc_task {
    /** Maximum note/file/tag units processed in one task execution. */
    public const BATCH_SIZE = 200;

    /** Human-readable task name in native task administration. */
    public function get_name(): string {
        return get_string('taskdeletecoursenotes', 'local_sidenotes');
    }

    /** Execute one idempotent batch; Moodle retries failures through its native task queue. */
    public function execute(): void {
        global $DB;
        $data = (array)$this->get_custom_data();
        $courseid = (int)($data['courseid'] ?? 0);
        if ($courseid <= 0 || $DB->record_exists('course', ['id' => $courseid])) {
            return;
        }

        $notes = $DB->get_records('local_sidenotes_notes', ['courseid' => $courseid],
            'id ASC', 'id, userid', 0, self::BATCH_SIZE);
        $transaction = $DB->start_delegated_transaction();
        foreach ($notes as $note) {
            \local_sidenotes\local\screenshot_manager::delete_for_note((int)$note->id);
            \local_sidenotes\local\tag_manager::remove_for_note((int)$note->id, (int)$note->userid);
            $DB->delete_records('local_sidenotes_notes', ['id' => $note->id, 'courseid' => $courseid]);
        }

        if ($DB->record_exists('local_sidenotes_notes', ['courseid' => $courseid])) {
            $next = new self();
            // A distinct batch identity avoids deduplication against the currently running task.
            // Always select the lowest remaining IDs, so a retry cannot skip partially processed data.
            $next->set_custom_data(['courseid' => $courseid,
                'batch' => max((int)array_key_last($notes), (int)($data['batch'] ?? 0) + 1)]);
            \core\task\manager::queue_adhoc_task($next, true);
        } else {
            $DB->delete_records('local_sidenotes_import', ['courseid' => $courseid]);
        }
        $transaction->allow_commit();
    }
}
