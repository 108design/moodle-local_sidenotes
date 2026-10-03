<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.

namespace local_sidenotes\local;

/** Central availability policy, independent of note ownership.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_policy {
    public static function note_lock(int $noteid): \core\lock\lock {
        $lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('note-' . $noteid, 10);
        if (!$lock) {
            throw new \moodle_exception('error:busy', 'local_sidenotes');
        }
        return $lock;
    }
    public static function sitewide(): bool {
        $enabled = get_config('local_sidenotes', 'sitewideenabled');
        return ($enabled === false || !empty($enabled))
            && has_capability('local/sidenotes:use', \context_system::instance());
    }

    public static function course_allowed(int $courseid): bool {
        global $DB;
        if ($courseid <= 0 || $courseid === SITEID || !get_config('local_sidenotes', 'courseenabled')) {
            return false;
        }
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || !can_access_course($course, null, 'local/sidenotes:usecourse', true)) {
            return false;
        }
        $settings = $DB->get_record('local_sidenotes_course', ['courseid' => $courseid]);
        return $settings && $settings->enabled !== null
            ? (bool) $settings->enabled : (bool) get_config('local_sidenotes', 'coursedefault');
    }

    /** Reject forged course ids and arbitrary non-course URLs in student mode. */
    public static function page_allowed(int $courseid, string $url): bool {
        global $DB, $CFG;
        if (self::sitewide()) {
            return true;
        }
        if (!self::course_allowed($courseid)) {
            return false;
        }
        $canonical = page_identity::canonicalise($url);
        $path = parse_url($canonical, PHP_URL_PATH);
        $basepath = rtrim((string) parse_url($CFG->wwwroot, PHP_URL_PATH), '/');
        if ($basepath !== '' && strpos($path, $basepath . '/') === 0) {
            $path = substr($path, strlen($basepath));
        }
        parse_str((string) parse_url($canonical, PHP_URL_QUERY), $query);
        // Do not accept the whole /course/ directory: index/search/category pages are site-level surfaces.
        if (preg_match('~^/course/(?:view|info|recent|resources|user)\.php$~', (string) $path)) {
            return (int) ($query['id'] ?? 0) === $courseid;
        }
        if ($path === '/course/section.php') {
            return $DB->record_exists('course_sections', ['id' => (int) ($query['id'] ?? 0), 'course' => $courseid]);
        }
        if (!preg_match('~^/mod/([a-z][a-z0-9_]*)/[a-z0-9_]+\.php$~', (string) $path, $matches)) {
            return false;
        }
        $cmid = (int) ($query['id'] ?? $query['cmid'] ?? 0);
        $cm = get_fast_modinfo($courseid)->get_cms()[$cmid] ?? null;
        if (!$cm || !$cm->uservisible || $cm->modname !== $matches[1]) {
            return false;
        }
        $settings = $DB->get_record('local_sidenotes_course', ['courseid' => $courseid]);
        $modules = $settings ? json_decode($settings->module_settings ?? '', true) : [];
        return !isset($modules[$cmid]) || (bool) $modules[$cmid];
    }

    public static function require_page(int $courseid, string $url): void {
        if (!self::page_allowed($courseid, $url)) {
            throw new \moodle_exception('access:denied', 'local_sidenotes');
        }
    }

    public static function overview_allowed(): bool {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (self::sitewide()) {
            return true;
        }
        if (!get_config('local_sidenotes', 'courseenabled')) {
            return false;
        }
        // An enrolled learner may retain access to their archive even if a course disables its drawer.
        foreach (get_user_capability_course('local/sidenotes:usecourse', $USER->id, true, 'visible') as $course) {
            if ((int) $course->id !== SITEID && can_access_course($course, $USER, 'local/sidenotes:usecourse', true)) {
                return true;
            }
        }
        return false;
    }

    public static function require_overview(): void {
        if (!self::overview_allowed()) {
            throw new \moodle_exception('access:denied', 'local_sidenotes');
        }
    }

    public static function can_edit(\stdClass $note): bool {
        try {
            return self::sitewide() || (self::overview_allowed()
                && (self::unbound($note) || self::page_allowed((int) $note->courseid, (string) $note->url)));
        } catch (\invalid_parameter_exception $exception) {
            return false;
        }
    }

    public static function require_edit(\stdClass $note): void {
        if (!self::can_edit($note)) {
            throw new \moodle_exception('access:denied', 'local_sidenotes');
        }
    }

    public static function unbound(\stdClass $note): bool {
        return $note->pagehash === self::unbound_hash();
    }

    public static function unbound_hash(): string {
        // A non-URL identity cannot collide with canonical Moodle page paths.
        return hash('sha256', 'sidenotes:unbound');
    }
}
