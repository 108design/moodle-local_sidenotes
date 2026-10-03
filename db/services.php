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
 * External service definitions.
 *
 * @package     local_sidenotes
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_sidenotes_manage_tags' => [
        'classname' => 'local_sidenotes\\external\\manage_tags', 'methodname' => 'execute',
        'description' => 'Manage the current owner private tag instances and colour preferences.',
        'type' => 'write', 'ajax' => true, 'loginrequired' => true,
    ],
    'local_sidenotes_edit_note' => [
        'classname' => 'local_sidenotes\\external\\edit_note',
        'methodname' => 'execute',
        'description' => 'Create an unbound private note or update one field in Notes Center.',
        'type' => 'write', 'ajax' => true, 'loginrequired' => true,
    ],
    'local_sidenotes_save_note' => [
        'classname' => 'local_sidenotes\\external\\save_note',
        'methodname' => 'execute',
        'description' => 'Create or update a private quick note for the current user.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_sidenotes_get_notes' => [
        'classname' => 'local_sidenotes\\external\\get_notes',
        'methodname' => 'execute',
        'description' => 'Retrieve private notes for the current page plus the user global notes.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_sidenotes_delete_note' => [
        'classname' => 'local_sidenotes\\external\\delete_note',
        'methodname' => 'execute',
        'description' => 'Delete a private quick note owned by the current user.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_sidenotes_upload_screenshot' => [
        'classname' => 'local_sidenotes\\external\\upload_screenshot',
        'methodname' => 'execute',
        'description' => 'Attach a pasted screenshot to a private quick note.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_sidenotes_delete_screenshot' => [
        'classname' => 'local_sidenotes\\external\\delete_screenshot',
        'methodname' => 'execute',
        'description' => 'Delete a screenshot from a private quick note.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];

$services = [
    'Local sidenotes AJAX services' => [
        'functions' => [
            'local_sidenotes_manage_tags',
            'local_sidenotes_edit_note',
            'local_sidenotes_save_note',
            'local_sidenotes_get_notes',
            'local_sidenotes_delete_note',
            'local_sidenotes_upload_screenshot',
            'local_sidenotes_delete_screenshot',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
