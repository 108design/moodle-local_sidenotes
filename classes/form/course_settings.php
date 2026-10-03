<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\form;

/** Native course-level availability controls.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_settings extends \moodleform {
    public function definition(): void {
        $form = $this->_form;
        $form->addElement('hidden', 'courseid');
        $form->setType('courseid', PARAM_INT);
        $form->addElement('select', 'enabled', get_string('course:enabled', 'local_sidenotes'), [
            -1 => get_string('course:inherit', 'local_sidenotes'),
            1 => get_string('yes'), 0 => get_string('no'),
        ]);
        $form->addElement('static', 'help', '', get_string('course:help', 'local_sidenotes'));
        $modules = [];
        foreach (get_fast_modinfo($this->_customdata['courseid'])->get_cms() as $cm) {
            if ($cm->has_view()) {
                $modules[$cm->id] = format_string($cm->name);
            }
        }
        if ($modules) {
            $form->addElement('autocomplete', 'excluded', get_string('course:excluded', 'local_sidenotes'),
                $modules, ['multiple' => true]);
        }
        $this->add_action_buttons();
    }
}
