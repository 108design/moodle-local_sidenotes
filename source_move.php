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
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

use local_sidenotes\local\source_move_manager as manager;

admin_externalpage_setup('local_sidenotes_sourcemove');
require_capability('moodle/site:config', context_system::instance());
$url = new moodle_url('/local/sidenotes/source_move.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('sourcemove', 'local_sidenotes'));
$PAGE->set_heading(get_string('pluginname', 'local_sidenotes'));
$form = new \local_sidenotes\form\source_move_form($url);
$action = optional_param('action', '', PARAM_ALPHA);
$error = false;
if ($action !== '') {
    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest');
    }
    require_sesskey();
    if ($action === 'preview') {
        if ($data = $form->get_data()) {
            manager::start_preview($data->oldbase);
            redirect($url);
        }
    } else {
        $jobid = required_param('jobid', PARAM_ALPHANUMEXT);
        // Explicit allowlist: no dynamic method invocation from a request.
        try {
            switch ($action) {
                case 'advance':
                    manager::advance($jobid);
                    break;
                case 'approve':
                    manager::approve($jobid);
                    break;
                case 'pause':
                    manager::pause($jobid);
                    break;
                case 'resume':
                    manager::resume($jobid);
                    break;
                case 'stop':
                    manager::stop($jobid);
                    break;
                default:
                    throw new invalid_parameter_exception('Unknown source-move action.');
            }
        } catch (\moodle_exception $exception) {
            // The persisted checkpoint stays available. Never expose private records in an error.
            $error = true;
        }
        if (!$error) {
            redirect($url);
        }
    }
}
$job = manager::current_job();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sourcemove', 'local_sidenotes'), 2);
echo html_writer::tag('p', get_string('sourcemoveintro', 'local_sidenotes'));
if ($error) {
    echo $OUTPUT->notification(get_string('sourcemoveerror', 'local_sidenotes'), 'error');
}
if ($job) {
    $actions = [];
    $addaction = static function(string $action, string $label, bool $primary = false) use (&$actions, $job, $url): void {
        $actions[] = ['url' => $url->out(false), 'action' => $action, 'jobid' => $job['id'],
            'sesskey' => sesskey(), 'label' => get_string($label, 'local_sidenotes'),
            'class' => $primary ? 'btn-primary' : 'btn-secondary', 'advance' => $action === 'advance'];
    };
    $running = in_array($job['status'], ['preview', 'apply'], true);
    if ($running) {
        $addaction('advance', 'sourcemovecontinue', true);
        $addaction('pause', 'sourcemovepause');
    } else if ($job['status'] === 'ready') {
        $addaction('approve', 'sourcemoveapply', true);
    } else if (in_array($job['status'], ['paused', 'failed'], true)) {
        $addaction('resume', 'sourcemoveresume', true);
    }
    if (!in_array($job['status'], ['done', 'stopped'], true)) {
        $addaction('stop', 'sourcemovestop');
    }
    echo $OUTPUT->render_from_template('local_sidenotes/source_move', [
        'old' => $job['old'], 'target' => $job['target'],
        'status' => get_string('sourcemovestatus_' . $job['status'], 'local_sidenotes'),
        'phase' => get_string('sourcemovephase_' . $job['phase'], 'local_sidenotes'),
        'progress' => get_string('sourcemoveprogress', 'local_sidenotes',
            (object)['scanned' => $job['scanned'], 'total' => $job['total']]),
        'changed' => $job['changed'], 'sourcecount' => $job['url'], 'quotecount' => $job['quoteurl'],
        'hashcount' => $job['pagehash'], 'skipped' => $job['skipped'],
        'percent' => $job['total'] ? min(100, (int)floor(100 * $job['scanned'] / $job['total'])) : 100,
        'actions' => $actions, 'running' => $running && !$error,
        'ready' => $job['status'] === 'ready',
    ]);
    if ($running && !$error) {
        $PAGE->requires->js_call_amd('local_sidenotes/source_move', 'init');
    }
}
if (!$job || in_array($job['status'], ['done', 'stopped'], true)) {
    $form->display();
}
echo html_writer::link(new moodle_url('/' . $CFG->admin . '/settings.php', ['section' => 'local_sidenotes']),
    get_string('settings'));
echo $OUTPUT->footer();
