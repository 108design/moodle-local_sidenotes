# Changelog

## 1.24.0 — unreleased

- Add an administrator preview and resumable update of source links after a Moodle domain or subdirectory move.
- Repair page associations containing transient form arguments and recalculate imported QuickNote page hashes.
- Distinguish quotation destinations with activity IDs sharing a numeric prefix.
- Keep native field help, exact topic links and focus return working across supported help adapters.

- Process deleted-course notes, screenshots and tags in bounded background tasks.
- Keep user unenrolment cleanup immediate and preserve notes in other courses.
- Fix contextual help loading on Moodle 5.2.
- Declare all 46 bundled editor packages in Moodle's third-party library list and generate this metadata with the editor build.

## 1.23.0 — 2026-10-20

- Show a discreet count of notes available on the current page inside the edge tab.
- Refresh the count after saved changes and when returning to the page.

## 1.22.0 — 2026-10-20

- Use the active theme's colours for links in quotations.

## 1.21.0 — 2026-10-20

- Restore bounded hover reveal and retraction of the notes edge tab.

## 1.20.0 — 2026-10-20

- Keep exposed edge tabs clickable inside the viewport while the notes panel is open.

## 1.19.0 — 2026-10-17

- Standardize the branded help header, topic selector and close controls.
- Include verified JavaScript source maps.

## 1.18.0 — 2026-10-16

- Add product logos to administration pages and contextual help.

## 1.16.0 — 2026-10-13

- Distinguish completion from selection with a gray header checkmark, green in the archive.
- Preserve completion tooltips, keyboard activation and failed-save rollback.
- Show selection helpers as compact buttons with icons; keep icons when counters refresh.
- Move the selected-note count beside the bulk action menu.

## 1.15.0 — 2026-10-13

- Add a small new-note edge tab above the main grip; move the overview tab below it.
- Add Alt+Q / Alt+Shift+Q quick creation, with typing/IME/repeat guards and a personal opt-out.
- Load drawer notes only on first opening; preserve mounted editors, drafts and focus while loading.
- Add separate selection checkboxes, Shift-click ranges and selection across overview pages.
- Support archive/restore, private tag add/remove, scoped global visibility and selected PDF/Markdown exports.
- Keep unsaved selected export drafts and request saving before exporting the stored version.
- Confirm permanent deletion of selected archived notes and guard batch operations with owner checks, locks and snapshots.
- Explicitly credit Matheus Mathias' QuickNote 0.12/0.13 for the adapted lazy-loading, Speed Dial, shortcut and multi-selection concepts. Existing GPL/upstream notices remain intact.

## 1.14.0 — 2026-10-11

- Fix task-checkbox activation in the visual editor without moving the text cursor.
- Keep native label and keyboard activation, undo and Markdown persistence in both editors.
- Match task-checkbox appearance in the sidebar editor and rendered notes.
- Include the right-edge notes grip and adjacent, same-tab Notes Center shortcut.
- Remove the obsolete left/right placement setting; existing notes are preserved.
- Support Moodle 4.5–5.3, with targeted Moodle 4.5 browser and save/reload checks.

## 1.7.1 — 2026-10-05

- Add the product logo above the README title at 125 by 125 pixels.
- Clarify current-release licence and availability.
- Plugin functionality and complete licence terms are unchanged.

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
