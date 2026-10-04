<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\external;

use local_sidenotes\local\access_policy;
use local_sidenotes\local\archive_manager;

/**
 * Owner-private archive membership and confirmed deletion; never accepts an owner from the client.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_archive extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'operation' => new \core_external\external_value(PARAM_ALPHA, 'archive, restore, preview or empty'),
            'noteid' => new \core_external\external_value(PARAM_INT, 'Owned note id.', VALUE_DEFAULT, 0),
            'revision' => new \core_external\external_value(PARAM_ALPHANUM, 'Confirmed archive snapshot.', VALUE_DEFAULT, ''),
        ]);
    }
    public static function execute(string $operation, int $noteid = 0, string $revision = ''): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('operation', 'noteid', 'revision'));
        require_login();
        self::validate_context(\context_system::instance());
        access_policy::require_overview();
        $operation = $params['operation'];
        if (!in_array($operation, ['archive', 'restore', 'preview', 'empty'], true)
                || (in_array($operation, ['archive', 'restore'], true) ? $params['noteid'] <= 0 : $params['noteid'] !== 0)) {
            throw new \invalid_parameter_exception('Invalid archive operation.');
        }
        $deleted = 0;
        if ($operation === 'archive' || $operation === 'restore') {
            archive_manager::set_state($params['noteid'], (int) $USER->id, $operation === 'archive');
        } else if ($operation === 'empty') {
            $deleted = archive_manager::empty_owned((int) $USER->id, $params['revision']);
        }
        return ['noteid' => $params['noteid'], 'archived' => $operation === 'archive', 'deleted' => $deleted]
            + archive_manager::status((int) $USER->id);
    }
    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Affected note or zero.'),
            'archived' => new \core_external\external_value(PARAM_BOOL, 'Archive state of affected note.'),
            'deleted' => new \core_external\external_value(PARAM_INT, 'Number of permanently deleted notes.'),
            'activecount' => new \core_external\external_value(PARAM_INT, 'Owner active note count.'),
            'archivecount' => new \core_external\external_value(PARAM_INT, 'Owner archive count.'),
            'revision' => new \core_external\external_value(PARAM_ALPHANUM, 'Archive revision.'),
        ]);
    }
}
