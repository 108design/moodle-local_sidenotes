/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for editor/entry.js.
 *
 * @package   local_sidenotes
 * @copyright 2026 Matheus Mathias
 * @copyright 2026 Andreas Giesen (downstream changes)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Bundled locally; see the plugin's THIRD_PARTY_NOTICES.md. No CDN or network calls.
import {Editor, InputRule} from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import {Markdown} from '@tiptap/markdown';
import {TaskList, TaskItem} from '@tiptap/extension-list';
import {TableKit} from '@tiptap/extension-table';
import Image from '@tiptap/extension-image';

const Tasks = TaskItem.extend({
    addNodeView() {
        const parent = this.parent();
        return props => {
            const nodeView = parent(props);
            const label = nodeView.dom.querySelector('label');
            const checkbox = label.querySelector('input[type="checkbox"]');
            // Own the activation, not the browser's tentative checked state or a focus-changing change handler.
            // Keep the upstream node view for Markdown, nested content, accessibility and transaction updates.
            label.addEventListener('click', event => {
                // A label click forwards one native click to its input; do not toggle twice.
                if (event.target !== checkbox) {return;}
                event.stopPropagation();
                const {editor, getPos} = props;
                const position = typeof getPos === 'function' ? getPos() : undefined;
                if (editor.isEditable && typeof position === 'number') {
                    const node = editor.state.doc.nodeAt(position);
                    if (node?.type.name === this.name) {
                        editor.view.dispatch(editor.state.tr.setNodeAttribute(position, 'checked', !node.attrs.checked));
                    }
                }
                checkbox.checked = nodeView.dom.dataset.checked === 'true';
            }, true);
            // The transaction above already saved the change; skip upstream's second, focus-changing update.
            label.addEventListener('change', event => {event.stopPropagation();}, true);
            return nodeView;
        };
    },
    addInputRules() {
        return [new InputRule({find: /^\s*(?:-\s)?\[( |x|X)\]\s$/,
            handler: ({range, match, chain}) => {
                // "- " has already become a bullet list when the following checkbox marker is completed.
                // Convert that list instead of trying to nest a taskItem inside an ordinary listItem.
                chain().deleteRange(range).toggleTaskList().updateAttributes('taskItem', {checked: /x/i.test(match[1])}).run();
            }})];
    },
});

export function create(options) {
    return new Editor({...options, extensions: [
        StarterKit.configure({link: {openOnClick: false}}),
        Markdown.configure({markedOptions: {gfm: true}}),
        TaskList, Tasks.configure({nested: true, HTMLAttributes: {'data-type': 'taskItem'}}),
        TableKit, Image.configure({allowBase64: false}),
    ]});
}
