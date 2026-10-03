<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** @package local_sidenotes
 * @copyright 2026 Matheus Mathias
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_sidenotes_install(): void {
    set_config('courseenabled', 1, 'local_sidenotes');
    set_config('coursedefault', 1, 'local_sidenotes');
    set_config('sitewideenabled', 0, 'local_sidenotes');
}
