<?php
// Copyright (C) 2026 Andreas Giesen. See LICENSE.
namespace local_sidenotes\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class admin_help {
    private const TOPICS = ['overview', 'lists'];
    private static bool $prepared = false;

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE;
        if (self::$prepared || admin_branding::page_capability() === null) {
            return;
        }
        $PAGE->add_body_class('local-sidenotes-admin-help-page');
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/local/sidenotes/styles/admin_help.css', [
            'rev' => get_config('local_sidenotes', 'version'),
        ]));
        $module = (int)$CFG->version < 2025041400 ? 'admin_help_legacy' : 'admin_help';
        $PAGE->requires->js_call_amd('local_sidenotes/admin_help_form', 'init');
        $PAGE->requires->js_call_amd('local_sidenotes/' . $module, 'init', [[
            'topic' => optional_param('sidenoteshelp', '', PARAM_ALPHA),
        ]]);
    }

    /** Native popover with concise guidance and a direct link to the extended topic. */
    public static function icon(string $key): string {
        global $CFG, $PAGE;
        if (!in_array($key, self::TOPICS, true)) {
            throw new \coding_exception('Unknown Administration help topic.');
        }
        $url = new \moodle_url($PAGE->url);
        $url->param('sidenoteshelp', $key);
        $help = new \help_icon($key, 'local_sidenotes', $url->out(false));
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('adminhelpmore', 'local_sidenotes'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('local_sidenotes/settings_help_icon', $data);
    }

    /** Always available in the native Topnav user area on the registered page. */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (!self::$prepared) {
            return '';
        }
        $label = get_string('adminhelp', 'local_sidenotes');
        return \html_writer::tag('button', $renderer->pix_icon(
            (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''), [
                'type' => 'button', 'class' => 'nav-link icon-no-margin local-sidenotes-help-navbar',
                'data-sidenotes-help-open' => '', 'aria-label' => $label, 'title' => $label,
                'aria-controls' => 'local-sidenotes-context-help', 'aria-expanded' => 'false',
            ]);
    }

    /** Same header, topic chooser and article layout as the Frontpage reference. */
    public static function render(): string {
        global $PAGE;
        if (!self::$prepared) {
            return '';
        }
        $title = get_string('adminhelp', 'local_sidenotes');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm local-sidenotes-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-sidenotes-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::TOPICS as $topic) {
            $label = get_string('adminhelp_' . $topic, 'local_sidenotes');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag('article', \html_writer::tag('h3', $label, ['class' => 'h5', 'tabindex' => '-1'])
                . get_string('adminhelp_' . $topic . '_article', 'local_sidenotes'), [
                    'data-sidenotes-help-article' => $topic, 'hidden' => 'hidden',
                ]);
        }
        $choice = \html_writer::tag('label', get_string('adminhelptopics', 'local_sidenotes'), [
            'for' => 'local-sidenotes-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'local-sidenotes-help-topic', 'class' => 'form-select custom-select',
        ]);
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'local_sidenotes')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'local-sidenotes-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
            'id' => 'local-sidenotes-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
        ]) . \html_writer::div($choice, 'local-sidenotes-help-choices') . $brand . $close,
            ['class' => 'local-sidenotes-help-header local-sidenotes-help-header-branded']);
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'local-sidenotes-help-content', ['tabindex' => '0']), [
                'id' => 'local-sidenotes-context-help', 'class' => 'local-sidenotes-context-help',
                'aria-labelledby' => 'local-sidenotes-context-help-title', 'hidden' => 'hidden',
                'data-sidenotes-help-url' => $PAGE->url->out(false),
            ]);
    }

    /** Append the registered panel without changing the page's controls. */
    public static function footer(\core\hook\output\before_standard_footer_html_generation $hook): void {
        $hook->add_html(self::render());
    }
}
