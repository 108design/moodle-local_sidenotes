<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/sidenotes:managecourse', $context);
if ($courseid === SITEID || !get_config('local_sidenotes', 'courseenabled')) {
    throw new moodle_exception('access:denied', 'local_sidenotes');
}
$PAGE->set_url('/local/sidenotes/course.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('course:settings', 'local_sidenotes'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');
$returnurl = new moodle_url('/course/view.php', ['id' => $courseid]);
$settings = $DB->get_record('local_sidenotes_course', ['courseid' => $courseid]);
$form = new \local_sidenotes\form\course_settings(null, ['courseid' => $courseid]);
$modules = $settings ? (json_decode($settings->module_settings ?? '', true) ?: []) : [];
$excluded = array_keys(array_filter($modules, static fn($enabled) => !$enabled));
$form->set_data(['courseid' => $courseid, 'enabled' => $settings->enabled ?? -1, 'excluded' => $excluded]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $record = (object) ['courseid' => $courseid, 'enabled' => (int) $data->enabled === -1 ? null : (int) $data->enabled,
        'module_settings' => json_encode(array_fill_keys($data->excluded ?? [], 0))];
    if ($settings) {
        $record->id = $settings->id;
        $DB->update_record('local_sidenotes_course', $record);
    } else {
        $DB->insert_record('local_sidenotes_course', $record);
    }
    redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course:settings', 'local_sidenotes'));
$form->display();
echo $OUTPUT->footer();
