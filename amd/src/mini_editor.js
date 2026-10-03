// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** Shared minimal Markdown editor; raw source remains the form value.
 * @module local_sidenotes/mini_editor
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str', 'local_sidenotes/editor_engine'], function(Str, Engine) {
    var instances = new Map();
    var keys = ['heading', 'paragraph', 'bold', 'italic', 'bullet', 'ordered', 'task', 'source', 'visual', 'unsupported'];
    var labels = Promise.resolve(Str.get_strings(keys.map(function(key) {
        return {key: 'editor:' + key, component: 'local_sidenotes'};
    })))
        .then(function(values) {var strings = {}; keys.forEach(function(key, i) {strings[key] = values[i];}); return strings;});
    // Keep unsupported Markdown in source mode, instead of silently round-tripping it through a smaller schema.
    var unsupported = function(value) {
        return /<\/?[a-z][\w-]*(?:\s[^>]*)?\s*\/?>|\[\^[^\]]+\]|(^|\n)\s*\$\$|!\[[^\]]*\]\(data:|\{:[^}]+\}|~~~/im.test(value);
    };
    var plainDocument = function(value) {
        return {type: 'doc', content: String(value).split(/\r?\n/).map(function(line) {
            return {type: 'paragraph', content: line ? [{type: 'text', text: line}] : []};
        })};
    };
    var attach = function(textarea, options) {
        if (instances.has(textarea)) {return instances.get(textarea).ready;}
        var instance = {editor: null, source: false, format: Number(options && options.format) || 4};
        instances.set(textarea, instance);
        instance.ready = labels.then(function(strings) {
            if (!textarea.isConnected) {instances.delete(textarea); return null;}
            var wrapper = document.createElement('div'); wrapper.className = 'local-sidenotes-editor';
            wrapper.dataset.sidenotesEditor = '1'; wrapper._sidenotesText = textarea;
            textarea.before(wrapper); wrapper.append(textarea);
            var toolbar = document.createElement('div'); toolbar.className = 'local-sidenotes-editor__toolbar';
            toolbar.setAttribute('role', 'toolbar'); toolbar.setAttribute('aria-label', strings.heading);
            var host = document.createElement('div'); host.className = 'local-sidenotes-editor__visual';
            wrapper.prepend(toolbar); wrapper.append(host);
            var buttons = [], syncing = false;
            var button = function(key, icon, action) {
                var control = document.createElement('button'); control.type = 'button'; control.title = strings[key];
                control.setAttribute('aria-label', strings[key]); control.dataset.editorAction = key;
                control.innerHTML = '<i class="fa ' + icon + '" aria-hidden="true"></i>';
                control.addEventListener('mousedown', function(event) {event.preventDefault();});
                control.addEventListener('click', action); toolbar.append(control); buttons.push(control); return control;
            };
            var heading = document.createElement('select'); heading.setAttribute('aria-label', strings.heading);
            heading.append(new Option(strings.paragraph, '0'));
            [1, 2, 3, 4, 5, 6].forEach(function(level) {heading.append(new Option('H' + level, String(level)));});
            toolbar.append(heading);
            var refresh = function() {
                if (!instance.editor) {return;}
                var active = {bold: 'bold', italic: 'italic', bullet: 'bulletList', ordered: 'orderedList', task: 'taskList'};
                buttons.forEach(function(control) {
                    if (active[control.dataset.editorAction]) {
                        control.setAttribute('aria-pressed', String(instance.editor.isActive(active[control.dataset.editorAction])));
                    }
                    if (control.dataset.editorAction !== 'source') {control.disabled = instance.source;}
                });
                heading.disabled = instance.source;
                heading.value = String(instance.editor.getAttributes('heading').level || 0);
            };
            var emit = function() {
                if (syncing || instance.source) {return;}
                var value = instance.editor.getMarkdown();
                if (value === textarea.value) {return;}
                textarea.value = value;
                instance.format = 4; textarea.dataset.contentformat = '4';
                textarea.dispatchEvent(new Event('input', {bubbles: true}));
            };
            instance.editor = Engine.create({element: host,
                content: instance.format === 2 ? plainDocument(textarea.value) : textarea.value,
                contentType: instance.format === 2 ? 'json' : 'markdown',
                editorProps: {
                    attributes: {role: 'textbox', 'aria-multiline': 'true', 'aria-label': textarea.getAttribute('aria-label')
                        || strings.paragraph, spellcheck: 'true'},
                    handlePaste: function(view, event) {
                        if (event.clipboardData && Array.from(event.clipboardData.items).some(function(item) {
                            return /^image\//.test(item.type);
                        })) {
                            textarea.dispatchEvent(new ClipboardEvent('paste', {bubbles: true, cancelable: true,
                                clipboardData: event.clipboardData}));
                            event.preventDefault(); return true;
                        }
                        return false;
                    },
                    handleKeyDown: function(view, event) {
                        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                            textarea.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter', ctrlKey: event.ctrlKey,
                                metaKey: event.metaKey, bubbles: true, cancelable: true}));
                            event.preventDefault(); return true;
                        }
                        return false;
                    }
                },
                onUpdate: function() {emit(); refresh();}, onSelectionUpdate: refresh,
            });
            heading.addEventListener('change', function() {
                var chain = instance.editor.chain().focus();
                if (Number(heading.value)) {chain.setHeading({level: Number(heading.value)}).run();}
                else {chain.setParagraph().run();}
            });
            [['bold', 'fa-bold', 'toggleBold'], ['italic', 'fa-italic', 'toggleItalic'],
                ['bullet', 'fa-list-ul', 'toggleBulletList'], ['ordered', 'fa-list-ol', 'toggleOrderedList'],
                ['task', 'fa-check-square-o', 'toggleTaskList']].forEach(function(item) {
                button(item[0], item[1], function() {instance.editor.chain().focus()[item[2]]().run();});
            });
            var source = button('source', 'fa-code', function() {
                if (instance.source && unsupported(textarea.value)) {
                    hint.hidden = false; hint.textContent = strings.unsupported; return;
                }
                if (instance.source) {
                    syncing = true;
                    instance.editor.commands.setContent(instance.format === 2 ? plainDocument(textarea.value) : textarea.value,
                        {emitUpdate: false, contentType: instance.format === 2 ? 'json' : 'markdown'});
                    syncing = false;
                }
                showSource(!instance.source); instance.focus();
            });
            var hint = document.createElement('small'); hint.className = 'form-text text-muted'; hint.hidden = true;
            wrapper.append(hint);
            var showSource = function(value) {
                instance.source = value; textarea.hidden = !value; host.hidden = value;
                source.setAttribute('aria-pressed', String(value)); source.title = value ? strings.visual : strings.source;
                source.setAttribute('aria-label', source.title); refresh();
                if (value) {textarea.style.height = Math.min(window.innerHeight * .45, Math.max(100, textarea.scrollHeight)) + 'px';}
            };
            textarea.addEventListener('input', function() {if (instance.source) {instance.format = 4;}});
            instance.focus = function() {if (instance.source) {textarea.focus();} else {instance.editor.commands.focus();}};
            instance.reset = function(value, format) {
                textarea.value = value; instance.format = Number(format) || 4; syncing = true;
                instance.editor.commands.setContent(instance.format === 2 ? plainDocument(value) : value,
                    {emitUpdate: false, contentType: instance.format === 2 ? 'json' : 'markdown'});
                syncing = false; var raw = unsupported(value); hint.hidden = !raw; hint.textContent = raw ? strings.unsupported : '';
                showSource(raw);
            };
            instance.reset(textarea.value, instance.format);
            return instance;
        }).catch(function(error) {
            // A usable raw input is safer than a broken editor when initialization fails.
            textarea.hidden = false; instances.delete(textarea); throw error;
        });
        return instance.ready;
    };
    new MutationObserver(function() {
        instances.forEach(function(instance, textarea) {
            if (!textarea.isConnected && instance.editor) {instance.editor.destroy(); instances.delete(textarea);}
        });
    }).observe(document.documentElement, {childList: true, subtree: true});
    return {attach: attach, get: function(textarea) {return instances.get(textarea);},
        focus: function(textarea) {return attach(textarea, {format: textarea.dataset.contentformat}).then(function(editor) {
            if (editor) {editor.focus();}
        });}};
});
