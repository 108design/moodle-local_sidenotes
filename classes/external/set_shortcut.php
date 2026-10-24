<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for classes/external/set_shortcut.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
