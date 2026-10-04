<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\external;

use local_sidenotes\local\access_policy;
use local_sidenotes\local\tag_manager;

/** Partial Notes Center updates; source metadata is immutable here.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_note extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Owned note id, zero for create.'),
            'operation' => new \core_external\external_value(PARAM_ALPHA, 'create, content, task, tags or global'),
            'content' => new \core_external\external_value(PARAM_RAW, 'Raw Markdown.', VALUE_DEFAULT, ''),
            'expectedcontent' => new \core_external\external_value(PARAM_RAW, 'Content read before editing.', VALUE_DEFAULT, ''),
            'tags' => new \core_external\external_multiple_structure(
                new \core_external\external_value(PARAM_TAG, 'Private tag name'), 'Tags', VALUE_DEFAULT, []),
            'isglobal' => new \core_external\external_value(PARAM_BOOL, 'Display globally.', VALUE_DEFAULT, false),
            'taskline' => new \core_external\external_value(PARAM_INT, 'Markdown task source line.', VALUE_DEFAULT, -1),
            'checked' => new \core_external\external_value(PARAM_BOOL, 'Task state.', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(int $noteid, string $operation, string $content = '',
        string $expectedcontent = '', array $tags = [], bool $isglobal = false, int $taskline = -1, bool $checked = false): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), compact(
            'noteid', 'operation', 'content', 'expectedcontent', 'tags', 'isglobal', 'taskline', 'checked'));
        require_login();
        self::validate_context(\context_system::instance());
        access_policy::require_overview();
        if (!in_array($params['operation'], ['create', 'content', 'task', 'tags', 'global'], true)
                || (($params['operation'] === 'create') !== ($params['noteid'] === 0))) {
            throw new \invalid_parameter_exception('Invalid note operation.');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_sidenotes')->get_lock('note-' . $noteid, 10);
        if (!$lock) {
            throw new \moodle_exception('error:busy', 'local_sidenotes');
        }
        try {
            if ($operation !== 'create') {
                $note = $DB->get_record('local_sidenotes_notes', ['id' => $params['noteid'], 'userid' => $USER->id],
                    '*', MUST_EXIST);
                access_policy::require_edit($note);
            }
            $transaction = $DB->start_delegated_transaction();
            if ($operation === 'create') {
                $note = (object) ['userid' => $USER->id, 'courseid' => 0, 'url' => '', 'quote' => '', 'quoteurl' => '',
                    'pagehash' => access_policy::unbound_hash(), 'pagetitle' => '', 'isglobal' => 0,
                    'content' => \core_text::substr($params['content'], 0, 20000), 'contentformat' => FORMAT_MARKDOWN,
                    'timecreated' => time(), 'timemodified' => time()];
                $note->id = $DB->insert_record('local_sidenotes_notes', $note);
            } else {
                // Only the changed field is written, never stale page/source/content snapshots for a tag update.
                $update = (object) ['id' => $note->id, 'timemodified' => time()];
                if ($operation === 'content' || $operation === 'task') {
                    if ((string) ($note->content ?? '') !== $params['expectedcontent']) {
                        throw new \moodle_exception('error:conflict', 'local_sidenotes');
                    }
                    if ($operation === 'task' && (int) $note->contentformat !== (int) FORMAT_MARKDOWN) {
                        throw new \invalid_parameter_exception('Plain text does not contain Markdown tasks.');
                    }
                    $update->content = $operation === 'task'
                        ? \local_sidenotes\local\note_formatter::toggle_task($note->content, $params['taskline'],
                            $params['checked'], \context_system::instance())
                        : \core_text::substr($params['content'], 0, 20000);
                    if ($update->content !== (string) ($note->content ?? '')) {
                        $update->contentformat = FORMAT_MARKDOWN;
                    }
                } else if ($operation === 'global') {
                    $update->isglobal = (int) $params['isglobal'];
                } else {
                    tag_manager::set_for_note((int) $note->id, (int) $USER->id, $params['tags']);
                }
                $DB->update_record('local_sidenotes_notes', $update);
            }
            $transaction->allow_commit();
            return save_note::export_note($DB->get_record('local_sidenotes_notes', ['id' => $note->id], '*', MUST_EXIST));
        } catch (\Throwable $exception) {
            if (isset($transaction)) {
                $transaction->rollback($exception);
            }
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return save_note::note_structure();
    }
}
