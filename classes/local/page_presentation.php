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
 * File documentation for classes/local/page_presentation.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.

namespace local_sidenotes\local;

/**
 * Display-only page labels; stored source metadata is never changed.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_presentation {
    /**
     * Remove an exact current site-name suffix from a page title.
     *
     * @param string $title Original page title.
     * @param string[] $sitenames Current site's full and short names.
     * @return string Page title without its redundant site suffix, if present.
     */
    public static function title(string $title, array $sitenames): string {
        foreach ($sitenames as $sitename) {
            $sitename = trim((string) $sitename);
            if ($sitename === '') {
                continue;
            }
            $label = preg_replace('/\s+\|\s*' . preg_quote($sitename, '/') . '\s*$/u', '', $title);
            if ($label !== null && trim($label) !== '' && $label !== $title) {
                return trim($label);
            }
        }
        return $title;
    }
}
