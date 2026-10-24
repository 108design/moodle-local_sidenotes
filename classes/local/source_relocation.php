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
namespace local_sidenotes\local;

defined('MOODLE_INTERNAL') || die();

/** Pure URL rules shared by the administrator preview and bounded apply workflow. */
final class source_relocation {
    /** A confirmed installation base has an HTTP origin and a path, without credentials/query/fragment. */
    public static function normalise_base(string $base): string {
        $base = trim($base);
        $parts = parse_url($base);
        if ($parts === false || !filter_var($base, FILTER_VALIDATE_URL)
                || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment'])
                || !self::safe_path($parts['path'] ?? '/')) {
            throw new \invalid_parameter_exception('A valid installation base URL is required.');
        }
        return self::origin($parts) . rtrim($parts['path'] ?? '', '/');
    }

    /** Map only the exact confirmed origin and path boundary; a sibling installation never matches. */
    public static function map_url(string $url, string $oldbase, string $newbase, bool $skipcurrent = true): ?string {
        $oldbase = self::normalise_base($oldbase);
        $old = parse_url($oldbase);
        $target = self::normalise_base($newbase);
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || self::origin($parts) !== self::origin($old)) {
            return null;
        }
        $path = $parts['path'] ?? '/';
        $prefix = $old['path'] ?? '';
        if (!self::safe_path($path) || !self::within_path($path, $prefix)) {
            return null;
        }
        $destination = parse_url($target);
        $targetpath = $destination['path'] ?? '';
        // A root-to-subdirectory move must not prepend the new path twice on retry.
        if ($skipcurrent && $target !== $oldbase && self::origin($old) === self::origin($destination)
                && $targetpath !== '' && self::within_path($targetpath, $prefix)
                && self::within_path($path, $targetpath)) {
            return null;
        }
        $suffix = substr($path, strlen($prefix));
        return $target . $suffix
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    /** Return a metadata-only patch for the current installation, retaining ownership/content/history. */
    public static function changes_for_note(\stdClass $note, string $oldbase, string $newbase): array {
        global $CFG;
        if (self::normalise_base($newbase) !== self::normalise_base($CFG->wwwroot)) {
            throw new \invalid_parameter_exception('The destination must be the current Moodle installation.');
        }
        $changes = [];
        $mapped = self::map_url((string) $note->url, $oldbase, $newbase);
        if ($mapped !== null) {
            $safe = page_identity::sanitise($mapped, true);
            if ($safe !== (string) $note->url) {
                $changes['url'] = $safe;
            }
            if (!access_policy::unbound($note)) {
                $hash = page_identity::hash($safe);
                if ($hash !== (string) $note->pagehash) {
                    $changes['pagehash'] = $hash;
                }
            }
        }
        if (!empty($note->quoteurl)) {
            $mapped = self::map_url((string) $note->quoteurl, $oldbase, $newbase);
            if ($mapped !== null) {
                $safe = page_identity::sanitise($mapped, true);
                if ($safe !== (string) $note->quoteurl) {
                    $changes['quoteurl'] = $safe;
                }
            }
        }
        return $changes;
    }

    /** Match a complete installation path segment, never a similarly named sibling. */
    private static function within_path(string $path, string $base): bool {
        return $base === '' || $path === $base || str_starts_with($path, $base . '/');
    }

    /** Compare equivalent origins while preserving the explicitly chosen scheme and port. */
    private static function origin(array $parts): string {
        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $default = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        return $scheme . '://' . strtolower($parts['host']) . ($default ? '' : ':' . $port);
    }

    /** Stored traversal forms are left for review, never rebased across an installation boundary. */
    private static function safe_path(string $path): bool {
        $decoded = rawurldecode($path);
        if ($path !== '' && $path[0] !== '/') {
            return false;
        }
        if (str_contains($decoded, '\\')) {
            return false;
        }
        foreach (explode('/', $decoded) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return false;
            }
        }
        return true;
    }
}
