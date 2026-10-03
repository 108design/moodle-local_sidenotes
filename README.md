# SideNotes

Private notes for Moodle courses, with optional capability-gated sitewide use. **SideNotes** is the product name in every language.

Independent GPLv3-or-later derivative of [QuickNote by Matheus Mathias](https://github.com/Matheu46/moodle-local_quicknote).
Fork maintainer: Andreas Giesen <andreas@108design.com> (108design). Upstream copyright and licence notices are retained.
Component: `local_sidenotes`. Install under `local/sidenotes`. SideNotes and QuickNote can coexist;
there is no runtime dependency or shared note store. Independent versioning starts at 1.0.0; current release: **1.4.0**.

## Features

- Page-bound, course-scoped global and overview-only private notes; collapsed global drawer group.
- Minimal visual Markdown editor with typing shortcuts, headings, bold/italic, bullet/numbered/task lists
  and source fallback. Plain notes remain literal until their text is actually changed.
- Private Moodle Tag API categories with automatic accessible colours and per-owner colour overrides.
- Inline overview editing, clickable checklists, screenshot paste/delete and Markdown/PDF exports.
- Course/student use by default; optional sitewide mode requires a separate system capability.
  Students cannot create page-bound notes outside accessible courses. Notes/media/tags remain owner-private.

## Student mode and optional administrator mode

SideNotes explicitly retains QuickNote's course-based student use: learners keep
private notes within courses they can access. Course notes are enabled by default
and can be controlled through the site and course settings.

The administrator mode adds notes outside courses for users with the separate
system capability. It is optional and disabled by default; enabling it does not
grant students sitewide access or make their notes visible to administrators.

## Optional QuickNote migration

When compatible QuickNote notes exist, the overview offers an import of **your own** not-yet-imported notes.
Nothing is imported automatically during detection, installation or upgrade. Confirm explicitly on the import page.
The original notes are not changed or deleted. Text, quotations, source URL/course and timestamps are copied.
Original QuickNote text remains plain; enhanced-fork Markdown, screenshots and private tags are also retained.
Existing imports are skipped; later source edits never overwrite SideNotes. Import receipts remain after deleting
individual copied notes to prevent reimport, and participate in Privacy API export/erasure.
Source settings/permissions are not imported by this user action. Source notes in unavailable courses are read-only.

## Enhanced-fork cutover

`cli/migrate_quicknote_fork.php` is a separate administrator-operated one-time migration for the verified
0.12.0 enhanced fork. It requires maintenance mode, an empty SideNotes archive and explicit `--apply`.
Back up code, database and Moodle file storage before running it. It copies all owners' notes, screenshots,
private tags/colours, course overrides, plugin settings and exact capability permissions. It verifies every
mapping before disabling the old fork's course/sitewide UI. Original data and code remain intact.
It does not uninstall QuickNote and cannot be used to overwrite an existing SideNotes archive.

## Building

Compiled assets are included for installation. To rebuild the editor engine, run
`npm ci` in `editor/`, then `npm run build`. The small AMD modules are distributed as source/build pairs. Pinned sources, GPL notices and
MIT dependency notices are distributed. There is no CDN/editor service/telemetry dependency.

Requires Moodle 4.2+ and its normal PHP/database requirements. Functional verification targets Moodle 5.2;
the full older-version/theme/mobile matrix is not claimed as tested.

## License

GNU General Public License version 3 or later. See [LICENSE.md](LICENSE.md) for the full terms.
Upstream copyright notices are retained; bundled dependencies keep the licences listed in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
