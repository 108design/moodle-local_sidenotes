<?php
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
