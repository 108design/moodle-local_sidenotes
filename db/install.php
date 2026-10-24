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
 * File documentation for db/install.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Matheus Mathias
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** @package local_sidenotes
 * @copyright 2026 Matheus Mathias
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_sidenotes_install(): void {
    global $CFG;
    set_config('sourcebase', $CFG->wwwroot, 'local_sidenotes');
    set_config('courseenabled', 1, 'local_sidenotes');
    set_config('coursedefault', 1, 'local_sidenotes');
    set_config('sitewideenabled', 0, 'local_sidenotes');
}
