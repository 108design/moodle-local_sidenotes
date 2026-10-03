<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_sidenotes\local;

use context_system;
use core_tag_area;
use core_tag_tag;

/**
 * User-scoped access to the Moodle Tag API for SideNotes notes.
 *
 * @package     local_sidenotes
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tag_manager {
    public const COMPONENT = 'local_sidenotes';
    public const ITEMTYPE = 'local_sidenotes_notes';
    public const MAX_TAGS = 20;
    public const MAX_TAG_LENGTH = 100;
    public const COLOUR_PREFIX = 'local_sidenotes_tagcolour_';

    /** Stable, restrained category colours; black/white text is chosen by WCAG relative luminance. */
    public static function colours(int $tagid, string $name, int $userid): array {
        $palette = ['#dbeafe', '#dcfce7', '#fef3c7', '#fce7f3', '#ede9fe', '#cffafe', '#ffedd5', '#e2e8f0',
            '#ccfbf1', '#fae8ff', '#e0e7ff', '#fecdd3'];
        $automatic = $palette[hexdec(substr(hash('sha256', \core_text::strtolower($name)), 0, 6)) % count($palette)];
        $custom = get_user_preferences(self::COLOUR_PREFIX . $tagid, '', $userid);
        $background = preg_match('/^#[a-f0-9]{6}$/i', $custom) ? strtolower($custom) : $automatic;
        return ['background' => $background, 'foreground' => self::contrast($background),
            'automatic' => $automatic, 'customcolour' => (bool) preg_match('/^#[a-f0-9]{6}$/i', $custom)];
    }

    public static function contrast(string $hex): string {
        $linear = [];
        foreach ([1, 3, 5] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $linear[] = $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
        return ($luminance + 0.05) / 0.05 >= 1.05 / ($luminance + 0.05) ? '#000000' : '#ffffff';
    }

    /** Whether the SideNotes tag area is currently enabled. */
    public static function is_enabled(): bool {
        return core_tag_area::is_enabled(self::COMPONENT, self::ITEMTYPE) === true;
    }

    /**
     * Normalise user-entered tag names while preserving their display spelling.
     *
     * @param array $tags Raw tag names.
     * @return string[]
     */
    public static function normalise(array $tags): array {
        $result = [];
        $seen = [];

        foreach ($tags as $tag) {
            $tag = trim(clean_param((string) $tag, PARAM_TAG));
            $tag = \core_text::substr($tag, 0, self::MAX_TAG_LENGTH);
            if ($tag === '') {
                continue;
            }

            $key = \core_text::strtolower($tag);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $tag;
            if (count($result) >= self::MAX_TAGS) {
                break;
            }
        }

        return $result;
    }

    /** Set the current user's tags for one owned note. */
    public static function set_for_note(int $noteid, int $userid, array $tags): void {
        if (!self::is_enabled()) {
            return;
        }

        core_tag_tag::set_item_tags(
            self::COMPONENT,
            self::ITEMTYPE,
            $noteid,
            context_system::instance(),
            self::normalise($tags),
            $userid
        );
    }

    /** Remove every user-specific tag instance attached to one note. */
    public static function remove_for_note(int $noteid, int $userid): void {
        core_tag_tag::remove_all_item_tags(self::COMPONENT, self::ITEMTYPE, $noteid, $userid);
    }

    /**
     * Return externally safe tag data for several notes.
     *
     * @param int[] $noteids Note ids.
     * @param int $userid Tag-instance owner.
     * @return array<int, array<int, array{id: int, name: string}>>
     */
    public static function get_for_notes(array $noteids, int $userid): array {
        $noteids = array_values(array_unique(array_map('intval', $noteids)));
        $result = array_fill_keys($noteids, []);
        if (!$noteids || !self::is_enabled()) {
            return $result;
        }

        $items = core_tag_tag::get_items_tags(
            self::COMPONENT,
            self::ITEMTYPE,
            $noteids,
            core_tag_tag::BOTH_STANDARD_AND_NOT,
            $userid
        );

        foreach ($items as $noteid => $tags) {
            foreach ($tags as $tag) {
                $result[(int) $noteid][] = [
                    'id' => (int) $tag->id,
                    'name' => $tag->get_display_name(false),
                ] + self::colours((int) $tag->id, $tag->get_display_name(false), $userid);
            }
        }

        return $result;
    }

    /** Return externally safe tag data for one note. */
    public static function get_for_note(int $noteid, int $userid): array {
        $items = self::get_for_notes([$noteid], $userid);
        return $items[$noteid] ?? [];
    }

    /** Shared external-service structure for a tag. */
    public static function external_structure(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'id' => new \core_external\external_value(PARAM_INT, 'Tag id.'),
            'name' => new \core_external\external_value(PARAM_TEXT, 'Tag display name.'),
            'background' => new \core_external\external_value(PARAM_RAW, 'Validated background hex colour.'),
            'foreground' => new \core_external\external_value(PARAM_RAW, 'Automatic black/white contrast colour.'),
            'automatic' => new \core_external\external_value(PARAM_RAW, 'Stable default background hex colour.'),
            'customcolour' => new \core_external\external_value(PARAM_BOOL, 'Whether the owner overrides the colour.'),
        ]);
    }

    /** Own assigned categories, never Moodle's sitewide tag catalogue. */
    public static function owned(int $userid): array {
        global $DB;
        if (!self::is_enabled()) {return [];}
        $records = $DB->get_records_sql('SELECT t.id,t.name,t.rawname,COUNT(DISTINCT qn.id) AS notecount
            FROM {tag} t JOIN {tag_instance} ti ON ti.tagid=t.id
            JOIN {local_sidenotes_notes} qn ON qn.id=ti.itemid
            WHERE ti.component=:component AND ti.itemtype=:itemtype AND ti.tiuserid=:taguserid AND qn.userid=:noteuserid
            GROUP BY t.id,t.name,t.rawname ORDER BY t.name', ['component' => self::COMPONENT,
                'itemtype' => self::ITEMTYPE, 'taguserid' => $userid, 'noteuserid' => $userid]);
        $result = [];
        foreach ($records as $tag) {
            $name = $tag->rawname ?: $tag->name;
            $canmanage = true;
            foreach (self::notes_for_tag((int) $tag->id, $userid) as $note) {
                $canmanage = $canmanage && access_policy::can_edit($note);
            }
            $result[] = ['id' => (int) $tag->id, 'name' => $name, 'notecount' => (int) $tag->notecount,
                'canmanage' => $canmanage] + self::colours((int) $tag->id, $name, $userid);
        }
        return $result;
    }

    private static function notes_for_tag(int $tagid, int $userid): array {
        global $DB;
        return $DB->get_records_sql('SELECT qn.id,qn.userid,qn.courseid,qn.url,qn.pagehash FROM {local_sidenotes_notes} qn
            JOIN {tag_instance} ti ON ti.itemid=qn.id
            WHERE ti.tagid=:tagid AND ti.component=:component AND ti.itemtype=:itemtype
                AND ti.tiuserid=:taguserid AND qn.userid=:noteuserid ORDER BY qn.id',
            ['tagid' => $tagid, 'component' => self::COMPONENT, 'itemtype' => self::ITEMTYPE,
                'taguserid' => $userid, 'noteuserid' => $userid]);
    }

    /** Rename/remove only this owner's instances. Never mutate/delete a shared core tag definition. */
    public static function manage(int $tagid, int $userid, string $operation, string $name, string $colour): void {
        global $DB;
        if ($colour !== '' && !preg_match('/^#[a-f0-9]{6}$/i', $colour)) {
            throw new \invalid_parameter_exception('A six-digit hex background colour is required.');
        }
        $owned = array_column(self::owned($userid), null, 'id');
        if (!isset($owned[$tagid])) {throw new \invalid_parameter_exception('Private tag not found.');}
        $old = $owned[$tagid];
        $names = self::normalise([$name]);
        if ($operation === 'update' && !$names) {throw new \moodle_exception('tags:emptyname', 'local_sidenotes');}
        $name = $names[0] ?? '';
        $rename = $operation === 'update' && \core_text::strtolower($name) !== \core_text::strtolower($old['name']);
        if (($rename || $operation === 'delete') && !$old['canmanage']) {
            throw new \moodle_exception('access:denied', 'local_sidenotes');
        }
        $locks = [];
        $transaction = null;
        try {
            $notes = ($rename || $operation === 'delete') ? self::notes_for_tag($tagid, $userid) : [];
            // Lock in deterministic order before opening a DB transaction; never release a row lock mid-batch.
            foreach ($notes as $note) {$locks[] = access_policy::note_lock((int) $note->id);}
            $transaction = $DB->start_delegated_transaction();
            $newid = $tagid;
            if ($rename || $operation === 'delete') {
                foreach ($notes as $note) {
                    access_policy::require_edit($note);
                        // Re-read under the note lock to preserve changes made since the manager was opened.
                        $tags = self::get_for_note((int) $note->id, $userid);
                        $tags = array_map(static fn(array $tag): string => $tag['id'] === $tagid
                            ? ($operation === 'delete' ? '' : $name) : $tag['name'], $tags);
                        self::set_for_note((int) $note->id, $userid, $tags);
                        $DB->set_field('local_sidenotes_notes', 'timemodified', time(), ['id' => $note->id, 'userid' => $userid]);
                        if ($rename) {
                            foreach (self::get_for_note((int) $note->id, $userid) as $tag) {
                                if (\core_text::strtolower($tag['name']) === \core_text::strtolower($name)) {$newid = $tag['id'];}
                            }
                        }
                }
                unset_user_preference(self::COLOUR_PREFIX . $tagid, $userid);
            }
            // When merging into an existing category, retain its colour unless the owner actually changed the colour field.
            $mergepreservescolour = $rename && $newid !== $tagid && isset($owned[$newid])
                && strtolower($colour) === ($old['customcolour'] ? $old['background'] : '');
            if ($operation === 'update' && !$mergepreservescolour) {
                if ($colour === '') {unset_user_preference(self::COLOUR_PREFIX . $newid, $userid);}
                else {set_user_preference(self::COLOUR_PREFIX . $newid, strtolower($colour), $userid);}
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            if ($transaction) {$transaction->rollback($exception);}
            throw $exception;
        } finally {
            foreach ($locks as $lock) {$lock->release();}
        }
    }

    public static function clear_colours(int $userid): void {
        foreach (get_user_preferences(null, null, $userid) as $name => $value) {
            if (strpos($name, self::COLOUR_PREFIX) === 0) {unset_user_preference($name, $userid);}
        }
    }
}
