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
 * Attach a pasted screenshot to an owned note.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_screenshot extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Owning note id.'),
            'filename' => new \core_external\external_value(PARAM_FILE, 'Original screenshot filename.'),
            'mimetype' => new \core_external\external_value(PARAM_RAW_TRIMMED, 'Screenshot MIME type.'),
            'data' => new \core_external\external_value(PARAM_RAW, 'Base64 screenshot content.'),
        ]);
    }

    public static function execute(int $noteid, string $filename, string $mimetype, string $data): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), [
            'noteid' => $noteid,
            'filename' => $filename,
            'mimetype' => $mimetype,
            'data' => $data,
        ]);

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

        return screenshot_manager::create($note, $params['filename'], $params['mimetype'], $params['data']);
        } finally {
            $lock->release();
        }
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return screenshot_manager::external_structure();
    }
}
