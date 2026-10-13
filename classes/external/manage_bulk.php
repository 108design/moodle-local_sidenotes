<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\external;

use local_sidenotes\local\access_policy;
use local_sidenotes\local\bulk_manager;

/**
 * Batch operations on explicitly selected private notes, with no client-supplied owner.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_bulk extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'operation' => new \core_external\external_value(PARAM_ALPHA, 'select, preview or apply'),
            'ids' => new \core_external\external_multiple_structure(new \core_external\external_value(PARAM_INT, 'Note id'),
                'Explicit selection', VALUE_DEFAULT, []),
            'archive' => new \core_external\external_value(PARAM_BOOL, 'Archive view', VALUE_DEFAULT, false),
            'action' => new \core_external\external_value(PARAM_ALPHA, 'Batch action', VALUE_DEFAULT, ''),
            'revision' => new \core_external\external_value(PARAM_ALPHANUM, 'Confirmed selection snapshot', VALUE_DEFAULT, ''),
            'tags' => new \core_external\external_multiple_structure(new \core_external\external_value(PARAM_TAG, 'Private tag'),
                'Tags to add/remove', VALUE_DEFAULT, []),
            'coursefilter' => new \core_external\external_value(PARAM_INT, 'Course filter', VALUE_DEFAULT, 0),
            'tagids' => new \core_external\external_multiple_structure(new \core_external\external_value(PARAM_INT, 'Filter tag'),
                'AND tag filters', VALUE_DEFAULT, []),
            'searchterm' => new \core_external\external_value(PARAM_TEXT, 'Search term', VALUE_DEFAULT, ''),
        ]);
    }
    public static function execute(string $operation, array $ids = [], bool $archive = false, string $action = '',
        string $revision = '', array $tags = [], int $coursefilter = 0, array $tagids = [], string $searchterm = ''): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact('operation', 'ids', 'archive', 'action',
            'revision', 'tags', 'coursefilter', 'tagids', 'searchterm'));
        require_login(); self::validate_context(\context_system::instance()); access_policy::require_overview();
        if ($params['operation'] === 'select') {
            $selected = bulk_manager::select((int) $USER->id, $params['archive'], $params['coursefilter'],
                $params['tagids'], $params['searchterm']);
            return ['ids' => $selected, 'count' => count($selected), 'revision' => '']
                + \local_sidenotes\local\archive_manager::status((int) $USER->id);
        }
        if (!in_array($params['operation'], ['preview', 'apply'], true)) {throw new \invalid_parameter_exception('Invalid operation.');}
        return bulk_manager::execute((int) $USER->id, $params['ids'], $params['archive'], $params['action'],
            $params['tags'], $params['revision'], $params['operation'] === 'preview');
    }
    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'ids' => new \core_external\external_multiple_structure(new \core_external\external_value(PARAM_INT, 'Affected id')),
            'count' => new \core_external\external_value(PARAM_INT, 'Selection count'),
            'revision' => new \core_external\external_value(PARAM_ALPHANUM, 'Selection snapshot'),
            'activecount' => new \core_external\external_value(PARAM_INT, 'Active count'),
            'archivecount' => new \core_external\external_value(PARAM_INT, 'Archive count'),
        ]);
    }
}
