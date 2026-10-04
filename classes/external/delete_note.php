<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes\external;

use context_system;

/**
 * Delete one owned private note and its screenshots.
 *
 * @package     local_sidenotes
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_note extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Note id to delete.'),
            'expectedarchived' => new \core_external\external_value(PARAM_INT, 'Expected archive state, -1 for legacy clients.', VALUE_DEFAULT, -1),
        ]);
    }

    public static function execute(int $noteid, int $expectedarchived = -1): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('noteid', 'expectedarchived'));
        if (!in_array($params['expectedarchived'], [-1, 0, 1], true)) {throw new \invalid_parameter_exception('Invalid archive state.');}

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        \local_sidenotes\local\access_policy::require_overview();

        \local_sidenotes\local\archive_manager::delete_owned($params['noteid'], (int) $USER->id, $params['expectedarchived']);
        return ['noteid' => $params['noteid'], 'deleted' => true];
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Deleted note id.'),
            'deleted' => new \core_external\external_value(PARAM_BOOL, 'Whether the note was deleted.'),
        ]);
    }
}
