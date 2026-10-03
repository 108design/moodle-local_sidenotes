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

use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Class hooks for SideNotes.
 *
 * @package    local_sidenotes
 */
class hooks {
    /**
     * Injects the SideNotes UI in the standard top of body HTML.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook The hook object.
     */
    public static function before_standard_top_of_body_html_generation(before_standard_top_of_body_html_generation $hook) {
        $html = self::get_top_of_body_html();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * Generates the SideNotes UI HTML.
     *
     * @return string
     */
    public static function get_top_of_body_html(): string {
        global $OUTPUT, $PAGE;

        if (during_initial_install() || (defined('CLI_SCRIPT') && CLI_SCRIPT)) {
            return '';
        }

        if (!isloggedin() || isguestuser()) {
            return '';
        }

        $courseid = !empty($PAGE->course->id) && (int) $PAGE->course->id !== SITEID ? (int) $PAGE->course->id : 0;
        if (!\local_sidenotes\local\access_policy::page_allowed($courseid, $PAGE->url->out(false))) {
            return '';
        }

        if ($PAGE->pagelayout === 'embedded') {
            // In H5P Core, inserting JS worked, but it didn't in mod_hvp and SCORM.
            $PAGE->requires->js_call_amd('local_sidenotes/notes', 'initIframe', [[
                'highlightlabel' => get_string('select:highlightlabel', 'local_sidenotes'),
            ]]);
            return '';
        }

        $excludedlayouts = ['popup', 'frametop', 'maintenance', 'print'];
        if (in_array($PAGE->pagelayout, $excludedlayouts)) {
            return '';
        }

        $courseid = !empty($PAGE->course->id) && (int) $PAGE->course->id !== SITEID
            ? (int) $PAGE->course->id
            : 0;
        $pageurl = $PAGE->url->out(false);
        $pagetitle = trim((string) ($PAGE->title ?: $PAGE->heading));
        if ($pagetitle === '') {
            $pagetitle = get_string('unknownpage', 'local_sidenotes');
        }

        $PAGE->requires->js_call_amd('local_sidenotes/notes', 'init', [[
            'courseid' => $courseid,
            'pageurl' => $pageurl,
            'pagetitle' => $pagetitle,
        ]]);

        $position = get_config('local_sidenotes', 'position');
        if (empty($position)) {
            $position = 'right';
        }
        $positionclass = 'local-sidenotes--' . $position;

        $html = $OUTPUT->render_from_template('local_sidenotes/sidebar', [
            'courseid' => $courseid,
            'pageurl' => $pageurl,
            'pagetitle' => $pagetitle,
            'positionclass' => $positionclass,
            'hasquote' => false,
            'quotetext' => '',
            'title' => get_string('sidebar:title', 'local_sidenotes'),
            'togglelabel' => get_string('sidebar:toggle', 'local_sidenotes'),
            'closelabel' => get_string('sidebar:close', 'local_sidenotes'),
            'addlabel' => get_string('note:add', 'local_sidenotes'),
            'placeholder' => get_string('note:placeholder', 'local_sidenotes'),
            'emptytext' => get_string('note:empty', 'local_sidenotes'),
            'savingtext' => get_string('note:saving', 'local_sidenotes'),
            'savedtext' => get_string('note:saved', 'local_sidenotes'),
            'errortext' => get_string('note:error', 'local_sidenotes'),
            'updatedlabel' => get_string('note:updated', 'local_sidenotes'),
            'locationlabel' => get_string('note:location', 'local_sidenotes'),
            'deleteconfirm' => get_string('note:delete_confirm', 'local_sidenotes'),
            'noresultstext' => get_string('search:noresultstext', 'local_sidenotes'),
            'highlightlabel' => get_string('select:highlightlabel', 'local_sidenotes'),
            'globallabel' => get_string(\local_sidenotes\local\access_policy::sitewide()
                ? 'note:isglobal' : 'note:globalcourses', 'local_sidenotes'),
            'globalbadge' => get_string('note:globalbadge', 'local_sidenotes'),
            'pastehint' => get_string('screenshot:pastehint', 'local_sidenotes'),
            'uploadingtext' => get_string('screenshot:uploading', 'local_sidenotes'),
            'deleteimagelabel' => get_string('screenshot:delete', 'local_sidenotes'),
            'taglabel' => get_string('tags', 'local_sidenotes'),
            'tagplaceholder' => get_string('tags:placeholder', 'local_sidenotes'),
            'previewlabel' => get_string('markdown:preview', 'local_sidenotes'),
            'tagsenabled' => \local_sidenotes\local\tag_manager::is_enabled() ? '1' : '0',
        ]);

        return $html;
    }

}
