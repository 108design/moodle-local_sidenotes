<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes\external;

use context_system;
use local_sidenotes\local\screenshot_manager;

/**
 * Delete one screenshot attached to an owned note.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_screenshot extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Owning note id.'),
            'fileid' => new \core_external\external_value(PARAM_INT, 'Stored file id.'),
        ]);
    }

    public static function execute(int $noteid, int $fileid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['noteid' => $noteid, 'fileid' => $fileid]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        $lock = \local_sidenotes\local\access_policy::note_lock($noteid);
        try {
        $note = $DB->get_record('local_sidenotes_notes', [
            'id' => $params['noteid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);
        \local_sidenotes\local\access_policy::require_edit($note);

        return ['fileid' => $params['fileid'], 'deleted' => screenshot_manager::delete_file($params['fileid'], $params['noteid'])];
        } finally {
            $lock->release();
        }
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'fileid' => new \core_external\external_value(PARAM_INT, 'Deleted file id.'),
            'deleted' => new \core_external\external_value(PARAM_BOOL, 'Whether the file was deleted.'),
        ]);
    }
}
