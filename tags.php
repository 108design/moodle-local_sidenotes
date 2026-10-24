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
 * File documentation for tags.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_login();
\local_sidenotes\local\access_policy::require_overview();
$PAGE->set_url('/local/sidenotes/tags.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('tags:manage', 'local_sidenotes'));
$PAGE->set_heading(get_string('tags:manage', 'local_sidenotes'));
$PAGE->set_pagelayout('standard');
$tags = \local_sidenotes\local\tag_manager::owned((int) $USER->id);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_sidenotes/tag_manager', ['tags' => $tags]
    + \local_sidenotes\local\overview_state::navigation(false, true));
echo $OUTPUT->footer();
