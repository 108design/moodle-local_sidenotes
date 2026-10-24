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

$string['bulk:select'] = 'Select notes';
$string['bulk:selectnote'] = 'Select note: {$a}';
$string['bulk:count'] = '{$a} selected';
$string['bulk:visible'] = 'Select visible';
$string['bulk:filtered'] = 'Select all filtered results ({$a})';
$string['bulk:clear'] = 'Clear selection';
$string['bulk:action'] = 'Choose action';
$string['bulk:apply'] = 'Apply';
$string['bulk:addtags'] = 'Add tag';
$string['bulk:removetags'] = 'Remove tag';
$string['bulk:tag'] = 'Tag name';
$string['bulk:globalon'] = 'Show globally within my scope';
$string['bulk:globaloff'] = 'Stop showing globally';
$string['bulk:hint'] = 'Shift-click selects a range. Changing filters clears the selection; paging keeps it.';
$string['bulk:confirm'] = 'Apply this action to {$a} selected notes? Unsaved text in selected editors is saved first.';
$string['bulk:deleteconfirm'] = 'Permanently delete {$a} selected archived notes, including their images? This cannot be undone.';
$string['bulk:done'] = 'Updated {$a} notes.';
$string['bulk:changed'] = 'The selection has changed. Nothing was changed by this batch; refresh and select again.';
$string['bulk:limit'] = 'Select at most 500 notes. Narrow the filters for larger collections.';
$string['bulk:taglimit'] = 'A selected note would exceed 20 tags. No tags were changed.';
$string['bulk:exportsaved'] = 'Save changes in selected editors before exporting. Exports use the saved notes.';
$string['sidebar:loading'] = 'Loading notes …';
$string['sidebar:loaderror'] = 'Could not load notes. Retry';
$string['sidebar:pagecount'] = 'Notes on this page: {$a}';
$string['shortcut:enabled'] = 'New note with Alt+Q';
$string['privacy:metadata:shortcut'] = 'Personal preference for the new-note keyboard shortcut.';

$string['allcourses'] = 'All courses';
$string['allpages'] = 'All pages';
$string['alltags'] = 'All tags';
$string['sidenotes:use'] = 'Use private Side Notes notes';
$string['exportmd'] = 'Export to Markdown';
$string['exportpdf'] = 'Export to PDF';
$string['filterbycourse'] = 'Filter by course';
$string['filterbytag'] = 'Filter by tag';
$string['markdown:preview'] = 'Formatted preview';
$string['note:add'] = 'New note';
$string['note:delete'] = 'Delete note';
$string['note:delete_confirm'] = 'Delete this note?';
$string['archive:title'] = 'Archive';
$string['archive:active'] = 'Notes';
$string['archive:views'] = 'Notes view';
$string['archive:complete'] = 'Mark complete and archive';
$string['archive:restore'] = 'Restore from archive';
$string['archive:clear'] = 'Empty archive';
$string['archive:delete'] = 'Delete permanently';
$string['archive:deleteconfirm'] = 'Permanently delete this note and its images? This cannot be undone.';
$string['archive:clearconfirm'] = 'Permanently delete all {$a} archived notes and their images? This cannot be undone. Course, tag and search filters do not limit this action.';
$string['archive:empty'] = 'Your archive is empty.';
$string['archive:hint'] = 'Completed notes are kept here. Uncheck the box in a note header to restore it.';
$string['archive:changed'] = 'The archive has changed. Please review the current view and confirm deletion again.';
$string['archive:archived'] = 'Note archived.';
$string['archive:restored'] = 'Note restored.';
$string['archive:cleared'] = 'Archive emptied.';
$string['archive:draftblocked'] = 'This note was archived. Your local text draft is preserved; copy it before closing.';
$string['tags:filteradd'] = 'Add tag …';
$string['tags:selectedfilters'] = 'Selected tags – all must match';
$string['tags:removefilter'] = 'Remove tag filter: {$a}';
$string['privacy:metadata:local_sidenotes_notes:archived'] = 'Whether the note was marked complete and archived.';
$string['privacy:metadata:local_sidenotes_notes:timearchived'] = 'Archive timestamp, or zero after restoration.';
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
$string['pluginname'] = 'Side Notes';
$string['import:title'] = 'Import from QuickNote';
$string['import:offer'] = '{$a} of your QuickNote notes can be imported into Side Notes.';
$string['import:explanation'] = 'Copy your own QuickNote notes into Side Notes. Text, quotations, page/course references and dates are retained; original notes remain unchanged. Original plain text stays plain. Enhanced-fork Markdown, tags and screenshots are retained too. Previously imported notes are skipped, even if you deleted the copy. Later source edits do not overwrite Side Notes. Source permissions and plugin settings are not imported. Imported notes from unavailable courses remain read-only.';
$string['import:pending'] = 'New notes available: {$a}';
$string['import:confirm'] = 'Import my notes now';
$string['import:complete'] = '{$a} notes imported into Side Notes.';
$string['privacy:metadata:imports'] = 'Owner-private QuickNote import receipts: owner, course, original note id/creation date, destination note id and import time. Retained after individual note deletion to prevent duplicate imports.';
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
$string['privacy:metadata:files'] = 'Screenshots pasted into private Side Notes notes.';
$string['privacy:metadata:tags'] = 'Private categorisation tags assigned to Side Notes notes.';
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
$string['tagarea_local_sidenotes_notes'] = 'Side Notes notes';
$string['tagcollection_sidenotes_private'] = 'Private Side Notes tags';
$string['tags'] = 'Tags';
$string['tags:placeholder'] = 'Tags separated by commas';
$string['viewnotescenter'] = 'View notes center';
$string['sidenotes:usecourse'] = 'Use own Side Notes notes in accessible courses';
$string['sidenotes:managecourse'] = 'Configure Side Notes availability in a course';
$string['settings:courseenabled'] = 'Enable course notes';
$string['settings:courseenabled_desc'] = 'Private notes for learners and teachers in accessible courses. Course managers can disable the drawer in their course or individual activities. Existing notes are retained.';
$string['settings:coursedefault'] = 'Enabled in courses by default';
$string['settings:coursedefault_desc'] = 'Default for courses without their own Side Notes setting.';
$string['settings:sitewideenabled'] = 'Additionally enable sitewide use';
$string['settings:sitewideenabled_desc'] = 'Notes on home, administration and other Moodle pages for administrators and explicitly assigned users with the system capability to use Side Notes. No access to other users\' notes.';
$string['course:settings'] = 'Side Notes in this course';
$string['course:enabled'] = 'Allow course notes';
$string['course:inherit'] = 'Use site default';
$string['course:excluded'] = 'Disable in these activities';
$string['course:help'] = 'Applies to course use only. Authorised sitewide users retain access. Existing private notes are not deleted.';
$string['access:denied'] = 'Side Notes is not available to you in this area.';
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
$string['tags:readonly'] = 'This tag is also used by an archived or currently uneditable note. You may change its colour, but cannot rename/remove it until all affected notes are restored or available for editing.';
$string['privacy:metadata:tagcolours'] = 'Custom private Side Notes tag background colours stored as Moodle user preferences.';
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
$string['quote:more'] = 'More';
$string['quote:less'] = 'Less';
$string['tags:filtercount'] = '{$a} selected';
$string['search:removefilter'] = 'Remove filter: {$a}';

$string['image:close'] = 'Close image viewer';
$string['image:previous'] = 'Previous image';
$string['image:next'] = 'Next image';
$string['image:zoom'] = 'Zoom image';
$string['image:error'] = 'The image could not be loaded.';
$string['image:gallery'] = 'Note screenshots';
$string['image:thumbnail'] = 'Show image {$a}';

$string['overviewshortcut'] = 'Open notes overview';

// Contextual administration help.
$string['adminhelp'] = 'Administration help';
$string['adminhelpmore'] = 'More in the help panel';
$string['adminhelptopics'] = 'Help Topics';
$string['adminhelp_overview'] = 'Making notes available';
$string['adminhelp_overview_article'] = '<p><strong>Enable course notes</strong> makes personal notes available in accessible courses. Course managers can disable the drawer for their course or individual activities. Turning availability off retains existing notes.</p><p><strong>Enabled in courses by default</strong> applies to courses without their own Side Notes setting, including existing courses. A course with an explicit setting keeps that choice.</p><p><strong>Additionally enable sitewide use</strong> allows notes outside courses for administrators and users who already have the system permission to use Side Notes. Enabling this switch does not grant that permission or access to another user’s notes.</p>';
$string['adminhelp_lists'] = 'Lists and page size';
$string['adminhelp_lists_article'] = '<p><strong>Notes per page</strong> controls how many entries appear on one page in the notes center. Choose 12, 24 or 48, or <strong>All</strong> to display the full list.</p><p>A page limit does not limit how many notes a user can store and does not delete or archive notes. <strong>All</strong> can create a long page when many notes exist.</p>';

$string['taskdeletecoursenotes'] = 'Clean up notes from a deleted course';

$string['overview'] = $string['adminhelp_overview'];
$string['lists'] = $string['adminhelp_lists'];
$string['overview_help'] = 'Course and sitewide availability are separate. Explicit course choices take precedence over the course default; disabling availability retains existing notes.';
$string['lists_help'] = 'This sets the number shown on one page of the notes center. It does not limit stored notes.';

// Administrator source-link relocation.
$string['sourcemove'] = 'Update Source Links After a Site Move';
$string['sourcemoveold'] = 'Previous Moodle Base URL';
$string['sourcemovetarget'] = 'Current Moodle Base URL';
$string['sourcemoveold_help'] = 'Enter the previous Moodle address, including any installation subdirectory, without a query or fragment. Only links within that exact installation are matched.';
$string['sourcemoveintro'] = 'After moving Moodle to another domain or subdirectory, preview and update the stored source and quotation links in Side Notes. The destination is this Moodle installation.';
$string['sourcemovepreview'] = 'Create Preview';
$string['sourcemovecontinue'] = 'Continue Processing';
$string['sourcemovepause'] = 'Pause';
$string['sourcemoveresume'] = 'Resume';
$string['sourcemovestop'] = 'Stop';
$string['sourcemoveapply'] = 'Apply Previewed Mapping';
$string['sourcemoveinvalidbase'] = 'Enter an absolute http:// or https:// Moodle base URL without credentials, a query or a fragment.';
$string['sourcemovebusy'] = 'A source-link update is already in progress. Resume or stop it before starting another preview.';
$string['sourcemovetargetchanged'] = 'The current Moodle address has changed. Stop this job and create a new preview.';
$string['sourcemoveerror'] = 'The last action could not be completed. Check the current Moodle address and resume from the saved progress, or stop the job and create a new preview.';
$string['sourcemovestatus_preview'] = 'Preview in Progress';
$string['sourcemovestatus_ready'] = 'Preview Ready';
$string['sourcemovestatus_apply'] = 'Updating Source Links';
$string['sourcemovestatus_paused'] = 'Paused';
$string['sourcemovestatus_failed'] = 'Processing Interrupted';
$string['sourcemovestatus_done'] = 'Update Complete';
$string['sourcemovestatus_stopped'] = 'Stopped';
$string['sourcemovephase_preview'] = 'Preview';
$string['sourcemovephase_apply'] = 'Update';
$string['sourcemoveprogress'] = '{$a->scanned} of {$a->total} notes checked';
$string['sourcemovechanged'] = 'Notes with Changes';
$string['sourcemovesources'] = 'Source Links';
$string['sourcemovequotes'] = 'Quotation Links';
$string['sourcemovehashes'] = 'Page Associations';
$string['sourcemoveskipped'] = 'Invalid Records Skipped';
$string['sourcemoveready'] = 'The preview has not changed any notes. Apply Previewed Mapping confirms the previous and current base URLs shown above. Notes added after this preview are excluded; counts can change if users edit or delete notes meanwhile.';
$string['sourcemoverunning'] = 'Processing continues automatically while this page is visible. You can pause, close the page and return later, or use Continue Processing without JavaScript.';
$string['sourcemovestophelp'] = 'Pause or Stop preserves all completed batches. Stop ends this job; it does not undo changes already applied. Note text, ownership, titles, archive state, tags, attachments and timestamps are retained.';
$string['adminhelp_sourcemove'] = 'Source Links After a Site Move';
$string['adminhelp_sourcemove_article'] = '<p>Open <strong>Update Source Links After a Site Move</strong> after moving Moodle to another domain or subdirectory. Enter the <strong>Previous Moodle Base URL</strong>, including its old subdirectory. The <strong>Current Moodle Base URL</strong> comes from this installation and cannot be edited here.</p><p><strong>Create Preview</strong> checks existing notes in portions of up to 200. It shows counts for source links, quotation links and page associations without displaying private note contents or changing notes. Links on other sites or outside the previous installation path are retained.</p><p><strong>Apply Previewed Mapping</strong> confirms those two addresses and starts the update. Quotation fragments are retained. Note text, ownership, full titles, archive state, tags, attachments and timestamps stay unchanged. Approved old links remain readable while the update runs.</p><p>Keep the page visible for automatic progress. <strong>Pause</strong> and <strong>Resume</strong> use saved progress. Closing the page stops further requests; return to continue. <strong>Stop</strong> ends the job without undoing completed batches. Notes created after the preview are excluded; create another preview if needed. Normal new notes must still use the current Moodle address.</p>';
$string['sourcemove_help'] = 'Preview the exact previous-to-current installation mapping before applying it. Processing is bounded and resumable; completed batches remain when stopped.';
