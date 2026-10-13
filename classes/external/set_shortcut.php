<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\external;
/**
 * Personal shortcut opt-out, independent of site/course availability.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_shortcut extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'enabled' => new \core_external\external_value(PARAM_BOOL, 'Enable Alt+Q'),
        ]);
    }
    public static function execute(bool $enabled): bool {
        self::validate_parameters(self::execute_parameters(), ['enabled' => $enabled]);
        require_login(); self::validate_context(\context_system::instance());
        \local_sidenotes\local\access_policy::require_overview();
        set_user_preference('local_sidenotes_shortcut', (int) $enabled);
        return $enabled;
    }
    public static function execute_returns(): \core_external\external_value {
        return new \core_external\external_value(PARAM_BOOL, 'Shortcut enabled');
    }
}
