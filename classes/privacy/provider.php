<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_sidenotes\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for local_sidenotes.
 *
 * @package    local_sidenotes
 * @category   privacy
 * @copyright  2026 Matheus Mathias
 * @copyright  2026 Andreas Giesen (downstream changes)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_sidenotes_notes', [
            'userid' => 'privacy:metadata:local_sidenotes_notes:userid',
            'courseid' => 'privacy:metadata:local_sidenotes_notes:courseid',
            'pagehash' => 'privacy:metadata:local_sidenotes_notes:pagehash',
            'pagetitle' => 'privacy:metadata:local_sidenotes_notes:pagetitle',
            'isglobal' => 'privacy:metadata:local_sidenotes_notes:isglobal',
            'archived' => 'privacy:metadata:local_sidenotes_notes:archived',
            'timearchived' => 'privacy:metadata:local_sidenotes_notes:timearchived',
            'content' => 'privacy:metadata:local_sidenotes_notes:content',
            'contentformat' => 'privacy:metadata:local_sidenotes_notes:contentformat',
            'quote' => 'privacy:metadata:local_sidenotes_notes:quote',
            'quoteurl' => 'privacy:metadata:local_sidenotes_notes:quoteurl',
            'url' => 'privacy:metadata:local_sidenotes_notes:url',
            'timecreated' => 'privacy:metadata:local_sidenotes_notes:timecreated',
            'timemodified' => 'privacy:metadata:local_sidenotes_notes:timemodified',
        ], 'privacy:metadata:local_sidenotes_notes');
        $collection->add_database_table('local_sidenotes_import', array_fill_keys(
            ['userid', 'courseid', 'sourceid', 'sourcecreated', 'noteid', 'timeimported'],
            'privacy:metadata:imports'), 'privacy:metadata:imports');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:files');
        $collection->add_subsystem_link('core_tag', [], 'privacy:metadata:tags');
        $collection->add_user_preference('local_sidenotes_tagcolour_*', 'privacy:metadata:tagcolours');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Notes are associated with courses. If courseid is 0, they are associated with the system context.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {local_sidenotes_notes} qn ON (c.instanceid = qn.courseid AND c.contextlevel = :courselevel)
                                                  OR (qn.courseid = 0 AND c.id = :systemcontextid)
                 WHERE qn.userid = :userid";

        $params = [
            'courselevel' => CONTEXT_COURSE,
            'systemcontextid' => context_system::instance()->id,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        $contextlist->add_from_sql(str_replace('{local_sidenotes_notes}', '{local_sidenotes_import}', $sql), $params);
        foreach (get_user_preferences(null, null, $userid) as $name => $value) {
            if (strpos($name, \local_sidenotes\local\tag_manager::COLOUR_PREFIX) === 0) {
                $contextlist->add_system_context();
                break;
            }
        }

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params = ['userid' => $userid] + $contextparams;

        $sql = "SELECT qn.*, c.id AS contextid
                  FROM {local_sidenotes_notes} qn
                  JOIN {context} c ON (c.instanceid = qn.courseid AND c.contextlevel = :courselevel)
                                   OR (qn.courseid = 0 AND c.id = :systemcontextid)
                 WHERE qn.userid = :userid AND c.id $contextsql";

        $params['courselevel'] = CONTEXT_COURSE;
        $params['systemcontextid'] = context_system::instance()->id;

        $notes = $DB->get_recordset_sql($sql, $params);
        $noteids = [];
        foreach ($notes as $note) {
            $noteids[] = (int) $note->id;
        }
        $notes->close();
        $tagsbynote = \local_sidenotes\local\tag_manager::get_for_notes($noteids, (int) $userid);
        $notes = $DB->get_recordset_sql($sql, $params);

        foreach ($notes as $note) {
            $context = context::instance_by_id($note->contextid);
            $data = (object) [
                'content' => $note->content,
                'contentformat' => (int) ($note->contentformat ?? FORMAT_PLAIN),
                'tags' => implode(', ', array_column($tagsbynote[(int) $note->id] ?? [], 'name')),
                'quote' => $note->quote,
                'quoteurl' => $note->quoteurl,
                'url' => $note->url,
                'pagetitle' => $note->pagetitle,
                'isglobal' => transform::yesno($note->isglobal),
                'archived' => transform::yesno($note->archived),
                'timearchived' => $note->timearchived ? transform::datetime($note->timearchived) : '',
                'timecreated' => transform::datetime($note->timecreated),
                'timemodified' => transform::datetime($note->timemodified),
            ];

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_sidenotes'), $note->id],
                $data
            );
            // Screenshot files deliberately live in system context even when the source page is a course.
            writer::with_context(context_system::instance())->export_area_files(
                [get_string('pluginname', 'local_sidenotes'), $note->id],
                'local_sidenotes',
                \local_sidenotes\local\screenshot_manager::FILEAREA,
                $note->id
            );
        }
        $notes->close();
        // Receipts are personal data even after an individual imported note was deleted.
        foreach ($DB->get_records('local_sidenotes_import', ['userid' => $userid]) as $receipt) {
            $receiptcontext = $receipt->courseid ? \context_course::instance($receipt->courseid, IGNORE_MISSING)
                : context_system::instance();
            if ($receiptcontext && in_array($receiptcontext->id, $contextlist->get_contextids())) {
                writer::with_context($receiptcontext)->export_data(
                    [get_string('pluginname', 'local_sidenotes'), 'QuickNote imports', $receipt->id],
                    (object) ['sourceid' => $receipt->sourceid, 'sourcecreated' => transform::datetime($receipt->sourcecreated),
                        'noteid' => $receipt->noteid, 'timeimported' => transform::datetime($receipt->timeimported)]);
            }
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_COURSE) {
            self::delete_dependencies_for_select('courseid = :courseid', ['courseid' => $context->instanceid]);
            $DB->delete_records('local_sidenotes_notes', ['courseid' => $context->instanceid]);
            $DB->delete_records('local_sidenotes_import', ['courseid' => $context->instanceid]);
        } else if ($context->id == context_system::instance()->id) {
            self::delete_dependencies_for_select('courseid = :courseid', ['courseid' => 0]);
            $users = $DB->get_fieldset_select('user_preferences', 'DISTINCT userid', $DB->sql_like('name', ':prefprefix'),
                ['prefprefix' => $DB->sql_like_escape(\local_sidenotes\local\tag_manager::COLOUR_PREFIX) . '%']);
            foreach ($users as $userid) {\local_sidenotes\local\tag_manager::clear_colours((int) $userid);}
            $DB->delete_records('local_sidenotes_notes', ['courseid' => 0]);
            $DB->delete_records('local_sidenotes_import', ['courseid' => 0]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $systemcontextid = context_system::instance()->id;
        $courseids = [];
        $deletesystem = false;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $courseids[] = $context->instanceid;
            } else if ($context->id == $systemcontextid) {
                $deletesystem = true;
            }
        }

        if (!empty($courseids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $params = ['userid' => $userid] + $inparams;
            $select = "userid = :userid AND courseid $insql";
            self::delete_dependencies_for_select($select, $params);
            $DB->delete_records_select('local_sidenotes_notes', $select, $params);
            $DB->delete_records_select('local_sidenotes_import', $select, $params);
        }

        if ($deletesystem) {
            \local_sidenotes\local\tag_manager::clear_colours((int) $userid);
            self::delete_dependencies_for_select(
                'userid = :userid AND courseid = :courseid',
                ['userid' => $userid, 'courseid' => 0]
            );
            $DB->delete_records('local_sidenotes_notes', ['userid' => $userid, 'courseid' => 0]);
            $DB->delete_records('local_sidenotes_import', ['userid' => $userid, 'courseid' => 0]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_COURSE) {
            $sql = "SELECT userid
                      FROM {local_sidenotes_notes}
                     WHERE courseid = :courseid";
            $params = ['courseid' => $context->instanceid];
            $userlist->add_from_sql('userid', $sql, $params);
            $userlist->add_from_sql('userid', str_replace('{local_sidenotes_notes}', '{local_sidenotes_import}', $sql), $params);
        } else if ($context->id == context_system::instance()->id) {
            $sql = "SELECT userid
                      FROM {local_sidenotes_notes}
                     WHERE courseid = 0";
            $userlist->add_from_sql('userid', $sql, []);
            $userlist->add_from_sql('userid', str_replace('{local_sidenotes_notes}', '{local_sidenotes_import}', $sql), []);
            $userlist->add_from_sql('userid', 'SELECT userid FROM {user_preferences} WHERE '
                . $DB->sql_like('name', ':prefprefix'),
                ['prefprefix' => $DB->sql_like_escape(\local_sidenotes\local\tag_manager::COLOUR_PREFIX) . '%']);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context->contextlevel == CONTEXT_COURSE) {
            $params = ['courseid' => $context->instanceid] + $userparams;
            $select = "courseid = :courseid AND userid $usersql";
            self::delete_dependencies_for_select($select, $params);
            $DB->delete_records_select('local_sidenotes_notes', $select, $params);
        } else if ($context->id == context_system::instance()->id) {
            $params = ['courseid' => 0] + $userparams;
            foreach ($userids as $userid) {\local_sidenotes\local\tag_manager::clear_colours((int) $userid);}
            $select = "courseid = :courseid AND userid $usersql";
            self::delete_dependencies_for_select($select, $params);
            $DB->delete_records_select('local_sidenotes_notes', $select, $params);
        }
        if ($context->contextlevel == CONTEXT_COURSE || $context->id == context_system::instance()->id) {
            $DB->delete_records_select('local_sidenotes_import', $select, $params);
        }
    }

    /** Export this owner's private category-colour preferences. */
    public static function export_user_preferences(int $userid): void {
        foreach (get_user_preferences(null, null, $userid) as $name => $value) {
            if (strpos($name, \local_sidenotes\local\tag_manager::COLOUR_PREFIX) === 0) {
                writer::export_user_preference('local_sidenotes', $name, $value,
                    get_string('privacy:metadata:tagcolours', 'local_sidenotes'));
            }
        }
    }

    /** Delete private tag assignments and screenshot areas for the selected notes. */
    private static function delete_dependencies_for_select(string $select, array $params): void {
        global $DB;

        $notes = $DB->get_records_select('local_sidenotes_notes', $select, $params, '', 'id,userid');
        foreach ($notes as $note) {
            \local_sidenotes\local\screenshot_manager::delete_for_note((int) $note->id);
            \local_sidenotes\local\tag_manager::remove_for_note((int) $note->id, (int) $note->userid);
        }
    }
}
