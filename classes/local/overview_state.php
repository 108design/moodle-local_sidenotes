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
 * File documentation for classes/local/overview_state.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/**
 * Independent, session-private overview criteria for each view.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sidenotes\local;
defined('MOODLE_INTERNAL') || die();

final class overview_state {
    /** Remember canonical criteria, never note contents or results. */
    public static function remember(bool $archive, array $params): void {
        global $SESSION, $USER;
        unset($params['archive'], $params['export']);
        $states = $SESSION->local_sidenotes_filters ?? [];
        $states[(int) $USER->id][$archive ? 'archive' : 'active'] = $params;
        $SESSION->local_sidenotes_filters = $states;
    }

    /** Native links restore only the destination view's own criteria. */
    public static function navigation(bool $archive = false, bool $tags = false): array {
        global $SESSION, $USER;
        $states = $SESSION->local_sidenotes_filters[(int) $USER->id] ?? [];
        $status = archive_manager::status((int) $USER->id);
        return $status + [
            'activeview' => !$archive && !$tags, 'archiveview' => $archive, 'tagsview' => $tags,
            'activeurl' => (new \moodle_url('/local/sidenotes/view.php', $states['active'] ?? []))->out(false),
            'archiveurl' => (new \moodle_url('/local/sidenotes/view.php',
                ($states['archive'] ?? []) + ['archive' => 1]))->out(false),
            'tagsurl' => (new \moodle_url('/local/sidenotes/tags.php'))->out(false),
            'tagsenabled' => tag_manager::is_enabled(),
        ];
    }
}
