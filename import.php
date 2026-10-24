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
 * File documentation for import.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen <andreas@108design.com>.
require_once('../../config.php');
require_login();
\local_sidenotes\local\access_policy::require_overview();
$url = new moodle_url('/local/sidenotes/import.php');
$overview = new moodle_url('/local/sidenotes/view.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('import:title', 'local_sidenotes'));
$PAGE->set_heading(get_string('import:title', 'local_sidenotes'));
$PAGE->set_pagelayout('standard');
if (optional_param('confirm', 0, PARAM_BOOL)) {
    require_sesskey();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {throw new invalid_parameter_exception('Import requires POST.');}
    $result = \local_sidenotes\local\quicknote_importer::import_own();
    redirect($overview, get_string('import:complete', 'local_sidenotes', $result['notes']),
        null, \core\output\notification::NOTIFY_SUCCESS);
}
$count = \local_sidenotes\local\quicknote_importer::pending((int) $USER->id);
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('p', get_string('import:explanation', 'local_sidenotes'));
echo html_writer::tag('p', get_string('import:pending', 'local_sidenotes', $count));
if ($count) {
    echo $OUTPUT->single_button(new moodle_url($url, ['confirm' => 1, 'sesskey' => sesskey()]),
        get_string('import:confirm', 'local_sidenotes'), 'post');
}
echo html_writer::link($overview, get_string('cancel'), ['class' => 'btn btn-link']);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
