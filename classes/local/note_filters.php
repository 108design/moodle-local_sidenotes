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
