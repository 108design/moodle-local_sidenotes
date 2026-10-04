# Side Notes

## 1.6.0 (2026-10-04)

- Notes, Archive and Manage tags share native tabs; note/archive criteria are remembered independently per session.
- Compact labels sit above the course/tag menus; multiple tags use checkbox selection with immediate AND filtering.
- Search and filters have removable chips beneath their own field, without a redundant search button.
- New note and export actions share a right-aligned row above the filters.
- Long quotes start at three lines with accessible More/Less actions in the overview and drawer; saved text is unchanged.

## 1.5.0 (2026-10-04)

- Complete/archive notes through an icon-only header checkbox and native tooltip; restore in a private archive.
- Permanently delete individual archived notes or empty a confirmed exact archive snapshot, preserving active notes.
- Combine private tag filters with coloured, removable chips beneath search; all selected tags must match.
- Apply course/tag filters immediately and text search after a short typing pause, with stale-response and IME protection.
- Keep header titles single-line with ellipsis and preserve note source/text/media/tags across archive/restore.

## 1.4.0 (2026-10-03)

- Smaller single-row editor toolbar and aligned screenshot hint/copy action in the drawer.
- Page-opening actions show the installation-relative target in native Bootstrap tooltips.
- Overview titles omit the exact current site-name suffix; stored metadata and searches remain unchanged.

## 1.3.0 (2026-10-03)

- Correct task-item editor layout and interactive checkboxes; align tag removal icons with hover/focus feedback.
- Drawer tags use the same coloured chips and add/remove actions as the overview, above the editor.
- Global visibility is set only in the overview; redundant drawer source URLs and editing explanations removed.
- Add-note button with plus icon shares its row with drawer search; overview search button is icon-only.
- Drawer text saves use guarded partial updates, keeping independently changed tags/scope intact.
- Empty-note screenshot pasting works in both editors without requiring text first.
## 1.2.0 (2026-10-03)

- Verify migrated tags against their stored names, independently of Moodle's display-case setting.
- Preserve original category spelling and private colours without changing the site's tag-display preference.

## 1.1.0 (2026-10-03)

- Use the supported context cache-invalidation API when copying capability permissions.
- Keep migration failures transactional and private; the explicit retry requires an empty target and retains original notes.

## 1.0.0 (2026-10-03)

- Independent component `local_sidenotes`, installation path, capabilities, database tables, private tag
  collection, media areas, preferences, AMD modules and UI identifiers; untranslated product name SideNotes.
- Retain the enhanced QuickNote derivative's private notes, Markdown editor, screenshots, categories,
  exports, course/student permissions and optional sitewide administration use.
- Explicit owner-only QuickNote import with metadata retention and duplicate-import protection.
- Separate verified one-time enhanced-fork migration, including all owners' rich data, settings and permissions.
- Independent version series; no organisational suffix in release/package names.

Based on Matheus Mathias' GPLv3-or-later QuickNote and Andreas Giesen's downstream enhancements.
Original notices remain in the source; SideNotes' release series does not replace QuickNote's history.
