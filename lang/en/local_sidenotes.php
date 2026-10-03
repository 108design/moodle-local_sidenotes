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

/**
 * Plugin strings are defined here.
 *
 * @package     local_sidenotes
 * @category    string
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allcourses'] = 'All courses';
$string['allpages'] = 'All pages';
$string['alltags'] = 'All tags';
$string['sidenotes:use'] = 'Use private SideNotes notes';
$string['exportmd'] = 'Export to Markdown';
$string['exportpdf'] = 'Export to PDF';
$string['filterbycourse'] = 'Filter by course';
$string['filterbytag'] = 'Filter by tag';
$string['markdown:preview'] = 'Formatted preview';
$string['note:add'] = 'New note';
$string['note:delete'] = 'Delete note';
$string['note:delete_confirm'] = 'Delete this note?';
$string['note:empty'] = 'No notes yet in this course.';
$string['note:error'] = 'Could not save this note';
$string['note:location'] = 'Page';
$string['note:globalbadge'] = 'Global';
$string['note:isglobal'] = 'Show on every page';
$string['note:placeholder'] = 'Write a private note...';
$string['note:saved'] = 'Saved automatically';
$string['note:saving'] = 'Saving...';
$string['note:updated'] = 'Updated';
$string['note:viewintext'] = 'View in text';
$string['notescenter'] = 'Notes center';
$string['perpage'] = 'Notes per page';
$string['perpage_desc'] = 'Maximum number of notes to display per page in the notes center.';
$string['pluginname'] = 'SideNotes';
$string['import:title'] = 'Import from QuickNote';
$string['import:offer'] = '{$a} of your QuickNote notes can be imported into SideNotes.';
$string['import:explanation'] = 'Copy your own QuickNote notes into SideNotes. Text, quotations, page/course references and dates are retained; original notes remain unchanged. Original plain text stays plain. Enhanced-fork Markdown, tags and screenshots are retained too. Previously imported notes are skipped, even if you deleted the copy. Later source edits do not overwrite SideNotes. Source permissions and plugin settings are not imported. Imported notes from unavailable courses remain read-only.';
$string['import:pending'] = 'New notes available: {$a}';
$string['import:confirm'] = 'Import my notes now';
$string['import:complete'] = '{$a} notes imported into SideNotes.';
$string['privacy:metadata:imports'] = 'Owner-private QuickNote import receipts: owner, course, original note id/creation date, destination note id and import time. Retained after individual note deletion to prevent duplicate imports.';
$string['position'] = 'Position';
$string['position_desc'] = 'Choose the position where the SideNotes toggle and panel will be displayed.';
$string['position_left'] = 'Left';
$string['position_right'] = 'Right';
$string['privacy:metadata:local_sidenotes_notes'] = 'Information about private quick notes created by users in courses.';
$string['privacy:metadata:local_sidenotes_notes:content'] = 'The text content of the note.';
$string['privacy:metadata:local_sidenotes_notes:contentformat'] = 'The Moodle text format used for the note content.';
$string['privacy:metadata:local_sidenotes_notes:courseid'] = 'The course where the note was created.';
$string['privacy:metadata:local_sidenotes_notes:isglobal'] = 'Whether the note appears on every page.';
$string['privacy:metadata:local_sidenotes_notes:pagehash'] = 'A non-reversible identity of the source page.';
$string['privacy:metadata:local_sidenotes_notes:pagetitle'] = 'The title of the source page.';
$string['privacy:metadata:local_sidenotes_notes:quote'] = 'The selected text quote that the note refers to.';
$string['privacy:metadata:local_sidenotes_notes:quoteurl'] = 'The URL of the specific section quoted.';
$string['privacy:metadata:local_sidenotes_notes:timecreated'] = 'The time when the note was created.';
$string['privacy:metadata:local_sidenotes_notes:timemodified'] = 'The time when the note was last modified.';
$string['privacy:metadata:local_sidenotes_notes:url'] = 'The page URL where the note was created.';
$string['privacy:metadata:local_sidenotes_notes:userid'] = 'The user who created the note.';
$string['privacy:metadata:files'] = 'Screenshots pasted into private SideNotes notes.';
$string['privacy:metadata:tags'] = 'Private categorisation tags assigned to SideNotes notes.';
$string['search'] = 'Search';
$string['search:clear'] = 'Clear search';
$string['search:noresultstext'] = 'No notes found matching your search.';
$string['search:placeholder'] = 'Search in my notes...';
$string['screenshot:delete'] = 'Delete screenshot';
$string['screenshot:attachment'] = 'Screenshot';
$string['screenshot:pastehint'] = 'Paste a screenshot here with Ctrl+V.';
$string['screenshot:uploading'] = 'Uploading screenshot...';
$string['select:highlightlabel'] = 'Save selection as note';
$string['sidebar:close'] = 'Close notes';
$string['sidebar:title'] = 'My notes';
$string['sidebar:toggle'] = 'Open notes';
$string['tagarea_local_sidenotes_notes'] = 'SideNotes notes';
$string['tagcollection_sidenotes_private'] = 'Private SideNotes tags';
$string['tags'] = 'Tags';
$string['tags:placeholder'] = 'Tags separated by commas';
$string['viewnotescenter'] = 'View notes center';
$string['sidenotes:usecourse'] = 'Use own SideNotes notes in accessible courses';
$string['sidenotes:managecourse'] = 'Configure SideNotes availability in a course';
$string['settings:courseenabled'] = 'Enable course notes';
$string['settings:courseenabled_desc'] = 'Private notes for learners and teachers in accessible courses. Course managers can disable the drawer in their course or individual activities. Existing notes are retained.';
$string['settings:coursedefault'] = 'Enabled in courses by default';
$string['settings:coursedefault_desc'] = 'Default for courses without their own SideNotes setting.';
$string['settings:sitewideenabled'] = 'Additionally enable sitewide use';
$string['settings:sitewideenabled_desc'] = 'Notes on home, administration and other Moodle pages for administrators and explicitly assigned users with the system capability to use SideNotes. No access to other users\' notes.';
$string['course:settings'] = 'SideNotes in this course';
$string['course:enabled'] = 'Allow course notes';
$string['course:inherit'] = 'Use site default';
$string['course:excluded'] = 'Disable in these activities';
$string['course:help'] = 'Applies to course use only. Authorised sitewide users retain access. Existing private notes are not deleted.';
$string['access:denied'] = 'SideNotes is not available to you in this area.';
$string['error:busy'] = 'This note is being saved. Please try again.';
$string['error:conflict'] = 'The text has changed elsewhere. Your input has been retained. Copy it before reloading and reconcile the changes.';
$string['note:unbound'] = 'No page association';
$string['note:globalcourses'] = 'Show in all my courses';
$string['note:edit'] = 'Edit text';
$string['note:readonly'] = 'The source area is currently not available for editing.';
$string['center:add'] = 'New note';
$string['center:addhint'] = 'New notes stay in Notes Center until you deliberately make them global.';
$string['center:editorhint'] = 'Markdown shortcuts supported · Ctrl+Enter saves · Paste screenshots with Ctrl+V';
$string['center:pendingtext'] = 'Change saved · text draft still unsaved';
$string['center:immediatehint'] = 'Tags, global visibility and images save immediately; Cancel only discards text changes.';
$string['center:discard'] = 'Discard the unsaved text changes?';
$string['tags:add'] = 'Add tag';
$string['tags:remove'] = 'Remove tag: {$a}';
$string['screenshot:deleteconfirm'] = 'Permanently delete this screenshot from the note?';
$string['sidebar:globalnotes'] = 'Global notes';
$string['sidebar:globalcount'] = '{$a->visible} of {$a->total}';
$string['sidebar:pageempty'] = 'No notes for this page yet.';
$string['tags:manage'] = 'Manage tags';
$string['tags:managerhint'] = 'Your tags only: renaming updates your notes; an existing name merges the assignments. Deleting removes the tag, not the notes. Other users are unaffected.';
$string['tags:notecount'] = '{$a} notes';
$string['tags:name'] = 'Tag name';
$string['tags:colour'] = 'Background';
$string['tags:automatic'] = 'Automatic colour';
$string['tags:delete'] = 'Remove tag from my notes';
$string['tags:deleteconfirm'] = 'Remove this tag from all your notes? The notes will be retained.';
$string['tags:empty'] = 'No tags yet. Add them directly to a note.';
$string['tags:emptyname'] = 'Please enter a tag name.';
$string['tags:readonly'] = 'This tag is also used in an area currently unavailable for editing. You may change its colour, but cannot rename/remove it until that area is enabled.';
$string['privacy:metadata:tagcolours'] = 'Custom private SideNotes tag background colours stored as Moodle user preferences.';
$string['editor:heading'] = 'Heading';
$string['editor:paragraph'] = 'Normal text';
$string['editor:bold'] = 'Bold';
$string['editor:italic'] = 'Italic';
$string['editor:bullet'] = 'Bullet list';
$string['editor:ordered'] = 'Numbered list';
$string['editor:task'] = 'Checklist';
$string['editor:source'] = 'Markdown source';
$string['editor:visual'] = 'Edit visually';
$string['editor:hint'] = 'Markdown shortcuts also work here. Paste screenshots with Ctrl+V.';
$string['editor:unsupported'] = 'This content remains in Markdown source mode to avoid losing unsupported formatting.';
$string['unknownpage'] = 'Moodle page';
