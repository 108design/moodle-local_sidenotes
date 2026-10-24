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
 * File documentation for cli/migrate_quicknote_fork.php.
 *
 * @package   local_sidenotes
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later. Copyright 2026 Andreas Giesen <andreas@108design.com>.
define('CLI_SCRIPT', true);
$webroot = dirname(__DIR__, 3);
$root = basename($webroot) === 'public' && is_file(dirname($webroot) . '/lib/setup.php') ? dirname($webroot) : $webroot;
require($root . '/config.php');
require_once($CFG->libdir . '/clilib.php');
[$options, $unrecognised] = cli_get_params(['apply' => false, 'help' => false], ['h' => 'help']);
if ($unrecognised) {cli_error('Unknown options.');}
if ($options['help']) {
    echo "Explicit enhanced QuickNote 0.12.0 cutover into an empty Side Notes archive.\n"
        . "Back up code, full database and Moodle file storage first. Enable maintenance mode, then use --apply.\n"
        . "All owners and rich data/settings/permissions are copied and verified; QuickNote UI is then disabled.\n"
        . "Source data/code are retained. Original QuickNote uses the separate owner import in the overview.\n";
    exit(0);
}
if (!$options['apply'] || empty($CFG->maintenance_enabled)) {
    cli_error('Explicit --apply and active maintenance mode are required; nothing imported.');
}
if (!is_siteadmin(get_admin())) {cli_error('No site administrator available.');}
\core\session\manager::set_user(get_admin());
$lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('fork-cutover', 10);
if (!$lock) {cli_error('Another cutover is running.');}
$transaction = $DB->start_delegated_transaction();
$failure = null;
try {
    $result = \local_sidenotes\local\quicknote_importer::migrate_fork();
    $verified = \local_sidenotes\local\migration_verifier::verify();
    // Only after full parity succeeds. Do not uninstall or remove any original data.
    set_config('courseenabled', 0, 'local_quicknote');
    set_config('sitewideenabled', 0, 'local_quicknote');
    $transaction->allow_commit();
} catch (Throwable $exception) {
    // Native DML exceptions may include note text in SQL parameters. Never dump them to the CLI.
    $failure = get_class($exception);
    try {$transaction->rollback($exception);} catch (Throwable $rollback) {}
} finally {$lock->release();}
if ($failure !== null) {
    cli_error('Migration failed; keep maintenance active and check rollback/backup before reopening. Error type: ' . $failure);
}
try {purge_all_caches();} catch (Throwable $exception) {
    cli_error('Migration was verified and committed, but cache purge failed. Keep maintenance active; do not repeat the migration.');
}
echo json_encode(['copied' => $result, 'verified' => $verified], JSON_PRETTY_PRINT) . "\n";
echo "SIDENOTES_FORK_MIGRATION_OK\n";
