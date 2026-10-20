<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.

namespace local_sidenotes\external;

use local_sidenotes\local\access_policy;
use local_sidenotes\local\page_identity;

/** Count active owner-private page notes without loading content or attachments.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_page_count extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'courseid' => new \core_external\external_value(PARAM_INT, 'Current course id or 0.'),
            'pageurl' => new \core_external\external_value(PARAM_RAW, 'Current Moodle page URL.'),
        ]);
    }

    public static function execute(int $courseid, string $pageurl): int {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid, 'pageurl' => $pageurl]);
        require_login();
        self::validate_context(\context_system::instance());
        access_policy::require_page((int) $params['courseid'], $params['pageurl']);
        // Same canonical identity as the drawer; global/unbound/archive records never contribute.
        $select = 'userid = :userid AND pagehash = :pagehash AND isglobal = 0 AND archived = 0';
        $queryparams = ['userid' => $USER->id, 'pagehash' => page_identity::hash($params['pageurl'])];
        if (access_policy::sitewide()) {
            return $DB->count_records_select('local_sidenotes_notes', $select, $queryparams);
        }
        // Match the drawer's per-record availability check for learners, including legacy metadata.
        $records = $DB->get_records_select('local_sidenotes_notes', $select, $queryparams, '', 'id, courseid, url');
        $count = 0;
        $availability = [];
        foreach ($records as $record) {
            $key = $record->courseid . ':' . $record->url;
            if (!array_key_exists($key, $availability)) {
                $availability[$key] = access_policy::page_allowed((int) $record->courseid, (string) $record->url);
            }
            if ($availability[$key]) {
                $count++;
            }
        }
        return $count;
    }

    public static function execute_returns(): \core_external\external_value {
        return new \core_external\external_value(PARAM_INT, 'Number of active non-global notes on this page.');
    }
}
