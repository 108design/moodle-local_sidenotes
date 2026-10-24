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
 * File documentation for classes/form/course_settings.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
