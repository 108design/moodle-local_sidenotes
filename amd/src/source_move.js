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
 * File documentation for amd/src/source_move.js.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/** Automatically advance the visible administrator workflow by one bounded POST at a time. */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.

export const init = () => {
    const form = document.querySelector('[data-sidenotes-move-advance]');
    if (!form || form.dataset.initialised) return;
    form.dataset.initialised = '1';
    const timer = window.setTimeout(() => {
        if (document.visibilityState === 'visible') form.requestSubmit();
    }, 1500);
    // A manual pause/stop/resume action wins over the next automatic batch.
    document.addEventListener('submit', () => window.clearTimeout(timer), {capture: true, once: true});
    document.addEventListener('visibilitychange', () => {
        window.clearTimeout(timer);
        if (document.visibilityState === 'visible') location.reload();
    });
};
