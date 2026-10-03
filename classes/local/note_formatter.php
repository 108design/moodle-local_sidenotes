<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes\local;

use context;

/** Safe note rendering with the small task-list extension used by SideNotes. */
final class note_formatter {
    /** Render one note while preserving Moodle's normal text-format boundary. */
    public static function format(string $content, int $format, context $context, bool $interactive = false): string {
        if (!in_array($format, [(int) FORMAT_PLAIN, (int) FORMAT_MARKDOWN], true)) {
            $format = FORMAT_PLAIN;
        }
        $html = format_text($content, $format, [
            'context' => $context,
            'filter' => false,
        ]);
        if ($format !== (int) FORMAT_MARKDOWN) {
            return $html;
        }

        // Moodle Markdown renders task markers as literal list-item text. Enhance only a marker at the
        // beginning of a rendered list item (or its first paragraph), never arbitrary brackets in note content.
        $lines = self::task_lines($content);
        $index = 0;
        // A mismatch (for example an indented code block) stays read-only rather than targeting the wrong source line.
        preg_match_all('~<li\b[^>]*>\s*(?:<p\b[^>]*>\s*)?\[( |x|X)\](?:\s|&nbsp;)+~u', $html, $markers);
        $interactive = $interactive && count($markers[0]) === count($lines);
        return preg_replace_callback(
            '~(<li\b)([^>]*)(>\s*(?:<p\b[^>]*>\s*)?)\[( |x|X)\](?:\s|&nbsp;)+~u',
            static function(array $matches) use ($interactive, $lines, &$index): string {
                $attributeshtml = $matches[2];
                if (preg_match('~\bclass=(["\'])(.*?)\1~iu', $attributeshtml)) {
                    $attributeshtml = preg_replace_callback(
                        '~\bclass=(["\'])(.*?)\1~iu',
                        static fn(array $classmatch): string => 'class=' . $classmatch[1]
                            . trim($classmatch[2] . ' local-sidenotes-task-item') . $classmatch[1],
                        $attributeshtml,
                        1
                    );
                } else {
                    $attributeshtml .= ' class="local-sidenotes-task-item"';
                }
                $attributes = [
                    'type' => 'checkbox',
                    'class' => 'local-sidenotes-task-checkbox',
                ];
                if ($interactive) {
                    $attributes['data-taskline'] = $lines[$index];
                    $attributes['aria-label'] = get_string('editor:task', 'local_sidenotes');
                } else {
                    $attributes['disabled'] = 'disabled';
                }
                $index++;
                if (strtolower($matches[4]) === 'x') {
                    $attributes['checked'] = 'checked';
                }
                return $matches[1] . $attributeshtml . $matches[3]
                    . \html_writer::empty_tag('input', $attributes) . ' ';
            },
            $html
        );
    }

    /** Candidate task source lines, excluding fenced code. Render-count matching supplies the final safety check. */
    private static function task_lines(string $content): array {
        $tasks = [];
        $fence = null;
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line => $value) {
            if (preg_match('/^\s{0,3}(\x60{3,}|~{3,})/', $value, $matches)) {
                if ($fence === null) {
                    $fence = $matches[1];
                } else if ($matches[1][0] === $fence[0] && strlen($matches[1]) >= strlen($fence)) {
                    $fence = null;
                }
                continue;
            }
            if ($fence === null && preg_match('/^\s*[-+*]\s+\[[ xX]\]\s+\S/u', $value)) {
                $tasks[] = $line;
            }
        }
        return $tasks;
    }

    /** Change only a verified rendered task marker, preserving every other byte of the Markdown. */
    public static function toggle_task(string $content, int $line, bool $checked, context $context): string {
        $html = self::format($content, FORMAT_MARKDOWN, $context, true);
        if ($line < 0 || !in_array($line, self::task_lines($content), true)
                || !preg_match('~<input\b[^>]*\bdata-taskline="' . $line . '"(?:\s|/?>)~', $html)) {
            throw new \invalid_parameter_exception('Not an editable Markdown task.');
        }
        $parts = preg_split('/(\r\n|\n|\r)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $parts[$line * 2] = preg_replace('/^(\s*[-+*]\s+\[)[ xX](\])/', '${1}'
            . ($checked ? 'x' : ' ') . '${2}', $parts[$line * 2], 1);
        return implode('', $parts);
    }
}
