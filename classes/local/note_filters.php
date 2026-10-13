<?php
// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
namespace local_sidenotes\local;

/**
 * Portable private multi-tag matching, applied before pagination and export.
 * @package local_sidenotes
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class note_filters {
    /** Shared owner-scoped query for overview, export and explicit bulk selection. */
    public static function query(int $userid, bool $archive, int $coursefilter, array $tags, string $search): array {
        global $DB;
        $sql = 'FROM {local_sidenotes_notes} qn LEFT JOIN {course} c ON c.id=qn.courseid
            WHERE qn.userid=:userid AND qn.archived=:archived';
        $params = ['userid' => $userid, 'archived' => (int) $archive];
        if ($coursefilter > 0) {$sql .= ' AND qn.courseid=:courseid'; $params['courseid'] = $coursefilter;}
        if ($coursefilter === -1) {$sql .= ' AND qn.pagehash=:unboundhash'; $params['unboundhash'] = access_policy::unbound_hash();}
        [$tagsql, $tagparams] = self::tag_conditions($tags, $userid);
        $sql .= $tagsql; $params += $tagparams;
        if ($search !== '') {
            $parts = [];
            foreach (['content', 'quote', 'pagetitle'] as $field) {
                $parts[] = $DB->sql_like('qn.' . $field, ':search' . $field, false, false);
            }
            $parts[] = $DB->sql_like('c.fullname', ':searchcourse', false, false);
            $name = $DB->sql_like('ts.name', ':searchtagname', false, false);
            $raw = $DB->sql_like('ts.rawname', ':searchtagrawname', false, false);
            $parts[] = "EXISTS (SELECT 1 FROM {tag_instance} tis JOIN {tag} ts ON ts.id=tis.tagid
                WHERE tis.itemid=qn.id AND tis.component=:searchtagcomponent AND tis.itemtype=:searchtagitemtype
                    AND tis.tiuserid=:searchtaguserid AND ($name OR $raw))";
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
            $escaped = '%' . $DB->sql_like_escape($search) . '%';
            foreach (['content', 'quote', 'pagetitle', 'course', 'tagname', 'tagrawname'] as $field) {
                $params['search' . $field] = $escaped;
            }
            $params += ['searchtagcomponent' => tag_manager::COMPONENT, 'searchtagitemtype' => tag_manager::ITEMTYPE,
                'searchtaguserid' => $userid];
        }
        return [$sql, $params];
    }
    public static function normalise_tags(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if (count($ids) > 50) {throw new \invalid_parameter_exception('At most 50 tag filters are supported.');}
        sort($ids);
        return $ids;
    }
    /** Caller uses qn for the notes table and has already restricted qn.userid. */
    public static function tag_conditions(array $ids, int $userid): array {
        $sql = ''; $params = [];
        foreach (self::normalise_tags($ids) as $index => $id) {
            $prefix = 'filtertag' . $index;
            $sql .= " AND EXISTS (SELECT 1 FROM {tag_instance} tf
                WHERE tf.itemid = qn.id AND tf.tagid = :{$prefix}id
                  AND tf.component = :{$prefix}component AND tf.itemtype = :{$prefix}itemtype
                  AND tf.tiuserid = :{$prefix}owner)";
            $params += [$prefix . 'id' => $id, $prefix . 'component' => tag_manager::COMPONENT,
                $prefix . 'itemtype' => tag_manager::ITEMTYPE, $prefix . 'owner' => $userid];
        }
        return [$sql, $params];
    }
}
