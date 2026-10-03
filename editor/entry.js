// Bundled locally; see the plugin's THIRD_PARTY_NOTICES.md. No CDN or network calls.
import {Editor, InputRule} from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import {Markdown} from '@tiptap/markdown';
import {TaskList, TaskItem} from '@tiptap/extension-list';
import {TableKit} from '@tiptap/extension-table';
import Image from '@tiptap/extension-image';

const Tasks = TaskItem.extend({
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
