<?php
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
