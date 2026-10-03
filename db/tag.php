<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Tag area definitions for private SideNotes categorisation.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tagareas = [
    [
        'itemtype' => 'local_sidenotes_notes',
        'component' => 'local_sidenotes',
        'collection' => 'sidenotes_private',
        'searchable' => false,
        'showstandard' => core_tag_tag::HIDE_STANDARD,
        'customurl' => '/local/sidenotes/view.php',
    ],
];
