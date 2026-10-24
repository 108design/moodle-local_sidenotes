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
 * Stable identities for deciding whether a quotation link stays on this page.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.

const VOLATILE_KEYS = new Set(['sesskey', '_qf__', 'local_quicknote_open', 'local_sidenotes_open']);

/** Normalize navigation identity without changing the link or its text fragment. */
const navigationIdentity = value => {
    value.hash = '';
    for (const key of [...value.searchParams.keys()]) {
        if (VOLATILE_KEYS.has(key) || key.startsWith('_qf__')) value.searchParams.delete(key);
    }
    value.searchParams.sort();
    return value.href;
};

/** Keep another origin, installation path or activity id as a normal navigation. */
export const samePage = (target, current) => {
    try {
        const here = new URL(current);
        const there = new URL(target, here);
        if (!['http:', 'https:'].includes(here.protocol) ||
                !['http:', 'https:'].includes(there.protocol)) return false;
        return navigationIdentity(there) === navigationIdentity(here);
    } catch (error) {
        return false;
    }
};
