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
 * Plugin callbacks.
 *
 * @package     local_sidenotes
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Legacy callback to inject the SideNotes UI in Moodle < 4.4.
 * In Moodle 4.4+, this is handled by the Hooks API (db/hooks.php).
 *
 * @return string HTML to inject.
 */
function local_sidenotes_before_standard_top_of_body_html() {
    // If the new Hook class exists, Moodle 4.4+ Hooks API will handle it.
    if (class_exists(\core\hook\output\before_standard_top_of_body_html_generation::class)) {
        return '';
    }

    // Otherwise, generate and return the HTML for older Moodle versions.
    return \local_sidenotes\hooks::get_top_of_body_html();
}

/** Course settings remain native Moodle controls, separate from private note access. */
function local_sidenotes_extend_navigation_course($navigation, $course, $context) {
    if (get_config('local_sidenotes', 'courseenabled') && $course->id !== SITEID
            && has_capability('local/sidenotes:managecourse', $context)) {
        $navigation->add(get_string('course:settings', 'local_sidenotes'),
            new moodle_url('/local/sidenotes/course.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING, null, 'sidenotes');
    }
}

/**
 * Serve a screenshot only to the owner of its note.
 *
 * @param stdClass $course Unused course record.
 * @param stdClass|null $cm Unused course module.
 * @param context $context File context.
 * @param string $filearea File area.
 * @param array $args Item id, path and filename.
 * @param bool $forcedownload Whether download was requested.
 * @param array $options File serving options.
 * @return bool|void False when the request is not valid.
 */
function local_sidenotes_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== \local_sidenotes\local\screenshot_manager::FILEAREA) {
        return false;
    }

    require_login();
    \local_sidenotes\local\access_policy::require_overview();
    $noteid = (int) array_shift($args);
    if (!$DB->record_exists('local_sidenotes_notes', ['id' => $noteid, 'userid' => $USER->id])) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file(
        $context->id,
        'local_sidenotes',
        $filearea,
        $noteid,
        $filepath,
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}
