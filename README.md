# Side Notes for Moodle

Keep private notes alongside Moodle course content. Save a quotation while reading,
add your own thoughts, organise notes with coloured tags, and return to the original
page when you need it. Side Notes supports learners' course notes and an optional
administrator mode for notes elsewhere on the site.

## Features

- A notes drawer alongside course pages and a searchable notes overview.
- Page notes, notes shown throughout a course, and personal notes kept in the overview.
- A visual Markdown editor with headings, bold, italic, lists and clickable checklists.
- Private coloured tags for organising and finding notes.
- Screenshots pasted into notes, with controls to remove them again.
- Markdown and PDF downloads.

Notes, screenshots and tags belong to their author. Enabling administrator mode
does not make other users' notes visible to administrators.

## Installation

Requires Moodle 4.2 or later and its corresponding PHP and database requirements.
The current release is **1.4.0**. Check the notes interface with your site's theme
before enabling it for learners.

1. Install the plugin as `local/sidenotes` below Moodle's plugin directory.
2. Complete installation through **Site administration → Notifications**.
3. Review the Side Notes site settings and the availability settings in your courses.

## Using Side Notes

Open the notes drawer while viewing a course page. Select a passage to save it as
a quotation, or create a note and enter your own text. Changes are saved as you edit.
Paste a screenshot into the note to attach it.

Use the notes overview to search, filter by course or tag, edit notes, and export
them. The **Show on every page** option makes a course note available throughout
that course; its source link still takes you back to the original page.

## Student mode and optional administrator mode

Course-based student use is enabled by default. Learners can keep private notes in
courses they can access. Administrators set the site default, and course settings
control availability within individual courses.

The optional administrator mode allows notes outside courses for users granted
the separate system capability. It is disabled by default. Enable it explicitly
and grant access only to the users who need it; students' course access stays limited
to their accessible courses.

## Importing QuickNote notes

If compatible QuickNote notes are available, the overview offers an import of
**your own** notes. Open the import page and confirm the import. Nothing is copied
automatically during installation or upgrade, and the original notes are preserved.

The import keeps note text, quotations, source links, courses and timestamps.
Supported Markdown notes, screenshots and private tags are also retained. Notes
already imported are skipped, and later changes in QuickNote do not overwrite
your Side Notes copies. Site settings and role permissions are configured separately.

Side Notes can be installed alongside QuickNote. QuickNote is needed only when
importing its existing notes.

## Maintainer and origin

Side Notes is an independently maintained derivative of
[QuickNote by Matheus Mathias](https://github.com/Matheu46/moodle-local_quicknote).
Maintained by Andreas Giesen <andreas@108design.com> (108design).
Original authorship and copyright notices are retained.

## License

GNU General Public License version 3 or later. See [LICENSE.md](LICENSE.md) for the full terms.
Bundled dependencies retain the licences listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
