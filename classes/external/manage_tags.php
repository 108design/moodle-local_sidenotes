<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\external;
use local_sidenotes\local\tag_manager;

/** Owner-scoped tag management, never Moodle's shared tag rename/delete API.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_tags extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'operation' => new \core_external\external_value(PARAM_ALPHA, 'list, update or delete'),
            'tagid' => new \core_external\external_value(PARAM_INT, 'Own tag id.', VALUE_DEFAULT, 0),
            'name' => new \core_external\external_value(PARAM_TAG, 'New label.', VALUE_DEFAULT, ''),
            'colour' => new \core_external\external_value(PARAM_RAW_TRIMMED, 'Background hex, empty for automatic.', VALUE_DEFAULT, ''),
        ]);
    }
    public static function execute(string $operation, int $tagid = 0, string $name = '', string $colour = ''): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('operation', 'tagid', 'name', 'colour'));
        require_login();
        self::validate_context(\context_system::instance());
        \local_sidenotes\local\access_policy::require_overview();
        if (!in_array($operation, ['list', 'update', 'delete'], true)) {
            throw new \invalid_parameter_exception('Invalid tag operation.');
        }
        if ($operation !== 'list') {
            tag_manager::manage($params['tagid'], (int) $USER->id, $operation, $params['name'], $params['colour']);
        }
        return ['tags' => tag_manager::owned((int) $USER->id)];
    }
    public static function execute_returns(): \core_external\external_single_structure {
        $tag = tag_manager::external_structure();
        return new \core_external\external_single_structure([
            'tags' => new \core_external\external_multiple_structure(new \core_external\external_single_structure($tag->keys + [
                'notecount' => new \core_external\external_value(PARAM_INT, 'Own notes using the tag.'),
                'canmanage' => new \core_external\external_value(PARAM_BOOL, 'All tagged notes are editable.'),
            ])),
        ]);
    }
}
