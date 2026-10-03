<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen.
defined('MOODLE_INTERNAL') || die();

/** Independent SideNotes upgrade series. Imports are never upgrade side effects. */
function xmldb_local_sidenotes_upgrade(int $oldversion): bool {
    return true;
}
