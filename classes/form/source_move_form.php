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
 * Explicit installation-base mapping for stored Side Notes source metadata.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.
namespace local_sidenotes\form;

defined('MOODLE_INTERNAL') || die();

/** Native administrator form for the explicitly confirmed previous installation base. */
final class source_move_form extends \moodleform {
    public function definition(): void {
        global $CFG;
        $form = $this->_form;
        $form->addElement('hidden', 'action', 'preview');
        $form->setType('action', PARAM_ALPHA);
        $form->addElement('text', 'oldbase', get_string('sourcemoveold', 'local_sidenotes'),
            ['size' => 60, 'maxlength' => 2048]);
        $form->setType('oldbase', PARAM_RAW_TRIMMED);
        $form->addRule('oldbase', null, 'required', null, 'client');
        $form->addHelpButton('oldbase', 'sourcemoveold', 'local_sidenotes');
        $form->addElement('html', \html_writer::span(
            \local_sidenotes\local\admin_help::icon('sourcemove'), '',
            ['data-sidenotes-help-field' => 'oldbase']));
        $previous = get_config('local_sidenotes', 'sourcebase');
        if ($previous && $previous !== $CFG->wwwroot) {
            $form->setDefault('oldbase', $previous);
        }
        $form->addElement('static', 'target', get_string('sourcemovetarget', 'local_sidenotes'),
            \html_writer::span(s($CFG->wwwroot), 'text-break'));
        $this->add_action_buttons(false, get_string('sourcemovepreview', 'local_sidenotes'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            \local_sidenotes\local\source_relocation::normalise_base($data['oldbase']);
        } catch (\invalid_parameter_exception $exception) {
            $errors['oldbase'] = get_string('sourcemoveinvalidbase', 'local_sidenotes');
        }
        return $errors;
    }
}
