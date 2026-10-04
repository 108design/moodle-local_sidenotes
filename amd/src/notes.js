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
 * @module      local_sidenotes/notes
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'local_sidenotes/mini_editor',
    'core/ajax',
    'core/notification',
    'core/str',
    'core/user_date'
], function(Editor, Ajax, Notification, Str, UserDate) {
    var SELECTORS = {
        root: '#local-sidenotes-root',
        panel: '[data-region="panel"]',
        list: '[data-region="notes-list"]',
        noteTemplate: '[data-region="note-template"]',
        toggle: '[data-action="toggle"]',
        close: '[data-action="close"]',
        add: '[data-action="add"]',
        search: '[data-action="search"]',
        searchwrapper: '.local-sidenotes__search',
        clearsearch: '[data-action="clear-search"]',
        deletebutton: '[data-action="delete-note"]',
        textarea: '.local-sidenotes__textarea',
        note: '.local-sidenotes__note',
        emptystate: '.local-sidenotes__empty',
        status: '[data-region="note-status"]',
        updated: '[data-region="note-updated"]',
        quotewrapper: '[data-region="note-quote-wrapper"]',
        quote: '[data-region="note-quote"]',
        quotelink: '[data-region="note-quote-link"]',
        screenshots: '[data-region="screenshots"]',
        deletescreenshot: '[data-action="delete-screenshot"]',
        tagswrapper: '[data-region="note-tags-wrapper"]',
        tagsinput: '[data-region="tag-input"]',
        previewwrapper: '[data-region="note-preview-wrapper"]',
        preview: '[data-region="note-preview"]'
    };

    var SAVE_DELAY = 500;
    var MIN_SELECTION_LENGTH = 5;
    var HIGHLIGHT_BUTTON_CLASS = 'local-sidenotes__highlight-action';
    var OPEN_DRAWER_QUERY_KEY = 'local_sidenotes_open';

    var state = null;

    var escapeHtml = function(value) {
        var div = document.createElement('div');
        div.textContent = String(value || '');
        return div.innerHTML.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    };

    var updateSearchVisibility = function() {
        var searchwrapper = state.root.querySelector(SELECTORS.searchwrapper);
        var searchInput = state.root.querySelector(SELECTORS.search);
        if (searchwrapper) {searchwrapper.style.display = '';}
        if (!state.notes.length) {
            if (searchInput) {
                searchInput.value = '';
            }
        }
    };

    var createDraftNote = function() {
        var now = Math.floor(Date.now() / 1000);

        return {
            id: 0,
            clientid: 'draft-' + Date.now() + '-' + Math.random().toString(16).slice(2),
            content: '',
            savedcontent: '',
            url: state.pageurl,
            pagetitle: state.pagetitle,
            courseid: state.courseid,
            isglobal: false,
            contentformat: 4,
            contenthtml: '',
            tagsenabled: state.tagsenabled,
            tags: [],
            tagschanged: false,
            screenshots: [],
            timecreated: now,
            timemodified: now,
            status: ''
        };
    };

    var normaliseSelectionText = function(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    };

    var formatQuotedNote = function(text) {
        return '"' + normaliseSelectionText(text) + '"';
    };

    var normaliseNote = function(note) {
        var normalisednote = Object.assign({}, note, {
            clientid: note.clientid || ('note-' + note.id),
            content: note.content || '',
            savedcontent: note.content || '',
            quote: note.quote || '',
            quoteurl: note.quoteurl || '',
            url: note.url || '',
            pagetitle: note.pagetitle || '',
            isglobal: !!note.isglobal,
            contentformat: Number(note.contentformat || 0),
            contenthtml: note.contenthtml || '',
            tagsenabled: note.tagsenabled !== false,
            tags: note.tags || [],
            tagschanged: false,
            screenshots: note.screenshots || [],
            status: note.status || ''
        });

        normalisednote.hasquote = !!(normalisednote.quote && normalisednote.quote.trim() !== '');
        normalisednote.quotetext = normalisednote.quote;

        return normalisednote;
    };

    var getRoot = function() {
        return document.querySelector(SELECTORS.root);
    };

    var getList = function() {
        return state.root.querySelector(SELECTORS.list);
    };

    var getSearchTerm = function() {
        var searchInput = state.root.querySelector(SELECTORS.search);
        var value = searchInput ? searchInput.value : '';
        return String(value || '').toLowerCase();
    };

    var getNoteByKey = function(key) {
        return state.notes.find(function(note) {
            return note.clientid === key;
        }) || null;
    };

    var getNoteElementByKey = function(key) {
        return state.root.querySelector(SELECTORS.note + '[data-note-key="' + key + '"]');
    };

    var setNoteStatus = function(noteEl, text, timestamp) {
        var statusEl = noteEl.querySelector(SELECTORS.status);
        if (statusEl) {
            if (text) {
                statusEl.textContent = text;
            } else if (timestamp) {
                Str.get_string('strftimedatetimeshort', 'langconfig').then(function(format) {
                    return UserDate.get([{
                        timestamp: timestamp,
                        format: format
                    }]);
                }).then(function(dates) {
                    var noteKey = noteEl.getAttribute('data-note-key');
                    var note = getNoteByKey(noteKey);
                    // Only update if the note hasn't changed its status while we were fetching the date
                    if (note && !note.status && note.timemodified === timestamp) {
                        statusEl.textContent = state.strings.updatedlabel + ': ' + dates[0];
                    }
                    return true;
                }).catch(function() {
                    statusEl.textContent = state.strings.updatedlabel + ': ' + new Date(timestamp * 1000).toLocaleString();
                });
            } else {
                statusEl.textContent = '';
            }
        }
    };

    var setNoteLocation = function(noteEl, url, hasquote) {
        var locationEl = noteEl.querySelector('[data-region="note-location"]');
        if (!locationEl) {
            return;
        }

        if (hasquote || !url) {
            locationEl.innerHTML = '';
            return;
        }

        locationEl.textContent = state.strings.locationlabel + ': ';
        var a = document.createElement('a');
        a.setAttribute('href', url);
        a.textContent = url;
        locationEl.appendChild(a);
    };

    var setNoteQuote = function(noteEl, note) {
        var wrapper = noteEl.querySelector(SELECTORS.quotewrapper);
        var quote = noteEl.querySelector(SELECTORS.quote);
        var link = noteEl.querySelector(SELECTORS.quotelink);

        if (!note.hasquote) {
            if (wrapper) {
                wrapper.setAttribute('hidden', 'hidden');
            }
            if (quote) {
                quote.textContent = '';
            }
            if (link) {
                link.setAttribute('href', '#');
                link.setAttribute('hidden', 'hidden');
            }
            return;
        }

        if (quote) {
            quote.textContent = note.quotetext;
        }
        if (link) {
            var safeHref = '#';
            if (note.quoteurl && /^(https?:\/\/|#)/i.test(note.quoteurl)) {
                safeHref = note.quoteurl;
            }
            link.setAttribute('href', safeHref);
            if (note.quoteurl) {
                link.removeAttribute('hidden');
            } else {
                link.setAttribute('hidden', 'hidden');
            }
        }
        if (wrapper) {
            wrapper.removeAttribute('hidden');
        }
    };

    var setNoteGlobal = function(noteEl, note) {
        var toggle = noteEl.querySelector('[data-action="toggle-global"]');
        var badge = noteEl.querySelector('[data-region="global-badge"]');
        var label = noteEl.querySelector('[data-region="global-label"]');
        var inputid = 'local-sidenotes-global-' + note.clientid;

        if (toggle) {
            toggle.id = inputid;
            toggle.checked = !!note.isglobal;
            toggle.setAttribute('data-note-key', note.clientid);
        }
        if (label) {
            label.setAttribute('for', inputid);
        }
        if (badge) {
            if (note.isglobal) {
                badge.removeAttribute('hidden');
            } else {
                badge.setAttribute('hidden', 'hidden');
            }
        }
    };

    var renderScreenshots = function(noteEl, note) {
        var container = noteEl.querySelector(SELECTORS.screenshots);
        if (!container) {
            return;
        }
        container.innerHTML = '';
        (note.screenshots || []).forEach(function(screenshot) {
            var figure = document.createElement('figure');
            figure.className = 'local-sidenotes__screenshot';

            var link = document.createElement('a');
            link.href = screenshot.url;
            link.target = '_blank';
            link.rel = 'noopener';
            var image = document.createElement('img');
            image.src = screenshot.url;
            image.alt = screenshot.filename;
            image.loading = 'lazy';
            link.appendChild(image);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-danger local-sidenotes__screenshot-delete';
            remove.setAttribute('data-action', 'delete-screenshot');
            remove.setAttribute('data-fileid', screenshot.id);
            remove.setAttribute('title', state.strings.deleteimagelabel);
            remove.setAttribute('aria-label', state.strings.deleteimagelabel);
            remove.textContent = '×';

            figure.appendChild(link);
            figure.appendChild(remove);
            container.appendChild(figure);
        });
    };

    var parseTags = function(value) {
        var seen = {};
        return String(value || '').split(',').map(function(tag) {
            return tag.trim();
        }).filter(function(tag) {
            var key = tag.toLowerCase();
            if (!tag || seen[key]) {
                return false;
            }
            seen[key] = true;
            return true;
        }).slice(0, 20);
    };

    var autogrowTextarea = function(textarea) {
        if (!textarea || textarea.hidden) {
            return;
        }
        var configuredMaxHeight = parseFloat(window.getComputedStyle(textarea).maxHeight);
        var maxHeight = Number.isFinite(configuredMaxHeight) && configuredMaxHeight > 0
            ? configuredMaxHeight
            : Math.floor(window.innerHeight * 0.45);
        textarea.style.height = 'auto';
        var borderHeight = Math.max(0, textarea.offsetHeight - textarea.clientHeight);
        var contentHeight = textarea.scrollHeight + borderHeight;
        textarea.style.height = Math.min(contentHeight, maxHeight) + 'px';
        textarea.style.overflowY = contentHeight > maxHeight ? 'auto' : 'hidden';
    };

    var autogrowTextareas = function() {
        if (!state.root) {
            return;
        }
        var panel = state.root.querySelector(SELECTORS.panel);
        if (panel && panel.getAttribute('aria-hidden') === 'true') {return;}
        state.root.querySelectorAll(SELECTORS.quotewrapper).forEach(function(wrapper) {
            var text = wrapper.querySelector(SELECTORS.quote), button = wrapper.querySelector('[data-action="toggle-quote"]');
            if (!text || !button || !text.offsetWidth) {return;}
            var style = window.getComputedStyle(text);
            var padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
            button.hidden = text.scrollHeight <= parseFloat(style.lineHeight) * 3 + padding + 1;
            wrapper.classList.toggle('local-sidenotes-center__quote--collapsible', !button.hidden);
            if (button.hidden) {
                wrapper.classList.remove('local-sidenotes-center__quote--expanded');
                button.setAttribute('aria-expanded', 'false'); button.textContent = button.dataset.more;
            }
        });
        state.root.querySelectorAll(SELECTORS.textarea).forEach(function(textarea) {
            // Closed global groups and filtered notes do not need a rich editor until they become visible.
            if (!textarea.offsetWidth) {return;}
            Editor.attach(textarea, {format: textarea.dataset.contentformat}).catch(Notification.exception);
            autogrowTextarea(textarea);
        });
    };

    var scheduleAutogrowTextareas = function() {
        window.requestAnimationFrame(function() {
            autogrowTextareas();
        });
    };

    var renderTagsAndPreview = function(noteEl, note) {
        var tagswrapper = noteEl.querySelector(SELECTORS.tagswrapper);
        var tagsinput = noteEl.querySelector(SELECTORS.tagsinput);
        var tagslist = noteEl.querySelector('[data-region="note-tags"]');
        var previewwrapper = noteEl.querySelector(SELECTORS.previewwrapper);
        var preview = noteEl.querySelector(SELECTORS.preview);

        if (tagswrapper) {
            if (note.tagsenabled) {
                tagswrapper.removeAttribute('hidden');
            } else {
                tagswrapper.setAttribute('hidden', 'hidden');
            }
        }
        if (tagsinput) {
            var tagsinputid = 'local-sidenotes-tags-' + note.clientid;
            tagsinput.id = tagsinputid;
        }
        if (tagslist) {
            tagslist.replaceChildren();
            (note.tags || []).forEach(function(tag) {
                var chip = document.createElement('span'); chip.className = 'badge local-sidenotes-center__tag';
                chip.style.setProperty('--sidenotes-tag-bg', tag.background);
                chip.style.setProperty('--sidenotes-tag-fg', tag.foreground);
                var name = document.createElement('span'); name.textContent = tag.name;
                var remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×';
                remove.dataset.action = 'remove-tag'; remove.dataset.tagname = tag.name;
                remove.setAttribute('aria-label', state.strings.removetaglabel.replace('__TAG__', tag.name));
                remove.disabled = !!note.tagpending;
                chip.append(name, remove); tagslist.appendChild(chip);
            });
        }

        if (preview) {
            preview.innerHTML = note.contenthtml || '';
        }
        if (previewwrapper) {
            if (String(note.content || '').trim()) {
                previewwrapper.removeAttribute('hidden');
            } else {
                previewwrapper.setAttribute('hidden', 'hidden');
                previewwrapper.removeAttribute('open');
            }
        }
    };

    var updateNoteElement = function(note, noteEl, preservecontent) {
        var textarea = noteEl.querySelector(SELECTORS.textarea);
        var currentcontent = preservecontent && textarea ? textarea.value : note.content;
        var textareaid = 'local-sidenotes-textarea-' + note.clientid;

        noteEl.setAttribute('data-note-key', note.clientid);

        if (textarea) {
            textarea.setAttribute('id', textareaid);
            textarea.setAttribute('data-note-key', note.clientid);
            textarea.setAttribute('placeholder', state.strings.placeholder);
            textarea.dataset.contentformat = String(note.contentformat || 4);

            if (textarea.value !== currentcontent) {
                textarea.value = currentcontent;
                var editor = Editor.get(textarea);
                if (editor && editor.reset) {editor.reset(currentcontent, note.contentformat);}
            }
            autogrowTextarea(textarea);
        }

        var deletebutton = noteEl.querySelector(SELECTORS.deletebutton);
        if (deletebutton) {
            deletebutton.setAttribute('data-noteid', note.id || 0);
        }

        setNoteStatus(noteEl, note.status, note.timemodified);
        setNoteQuote(noteEl, note);
        setNoteLocation(noteEl, note.url, note.hasquote);
        setNoteGlobal(noteEl, note);
        renderScreenshots(noteEl, note);
        renderTagsAndPreview(noteEl, note);

        var copyBtn = noteEl.querySelector('[data-action="copy-note"]');
        if (copyBtn) {
            if (currentcontent.trim().length > 0) {
                copyBtn.style.display = 'inline-flex';
            } else {
                copyBtn.style.display = 'none';
            }
        }
    };

    var createNoteElement = function(note) {
        var template = state.root.querySelector(SELECTORS.noteTemplate);
        var element = document.importNode(template.content.firstElementChild, true);

        updateNoteElement(note, element, false);

        return element;
    };

    var renderEmptyState = function() {
        getList().innerHTML = '<p class="local-sidenotes__empty">' + escapeHtml(state.strings.emptytext) + '</p>';
    };

    var renderNoResultsState = function() {
        var empty = document.createElement('p');
        empty.className = 'local-sidenotes__empty';
        empty.textContent = state.strings.noresultstext;
        getList().appendChild(empty);
    };

    var noteMatchesSearch = function(note, term) {
        if (!term) {
            return true;
        }

        return String(note.content || '').toLowerCase().indexOf(term) !== -1 ||
            String(note.quote || '').toLowerCase().indexOf(term) !== -1 ||
            String(note.pagetitle || '').toLowerCase().indexOf(term) !== -1 ||
            (note.tags || []).some(function(tag) {
                return String(tag.name || '').toLowerCase().indexOf(term) !== -1;
            });
    };

    var applyFilter = function() {
        var term = getSearchTerm();
        var visiblecount = 0;
        var list = getList();

        if (!state.notes.length) {
            renderEmptyState();
            return;
        }

        var noteElements = list.querySelectorAll(SELECTORS.note);
        noteElements.forEach(function(noteEl) {
            var note = getNoteByKey(noteEl.getAttribute('data-note-key'));
            var matches = note && noteMatchesSearch(note, term);

            if (matches) {
                noteEl.style.display = '';
                visiblecount += 1;
            } else {
                noteEl.style.display = 'none';
            }
        });

        var emptyState = list.querySelector(SELECTORS.emptystate);
        if (emptyState) {
            emptyState.remove();
        }

        if (!visiblecount) {
            noteElements.forEach(function(n) {
                n.style.display = 'none';
            });
            renderNoResultsState();
        }

        var globalGroup = list.querySelector('[data-region="global-group"]');
        if (globalGroup) {
            var globalNotes = state.notes.filter(function(note) {return note.isglobal;});
            var matches = globalNotes.filter(function(note) {return noteMatchesSearch(note, term);});
            var pageGroup = list.querySelector('[data-region="page-group"]');
            noteElements.forEach(function(noteEl) {
                var note = getNoteByKey(noteEl.getAttribute('data-note-key'));
                var destination = note && note.isglobal ? globalGroup.querySelector('[data-region="global-list"]') : pageGroup;
                if (noteEl.parentElement !== destination) {destination.appendChild(noteEl);}
            });
            globalGroup.hidden = !globalNotes.length || (!!term && !matches.length);
            globalGroup.open = term ? !!matches.length : !!state.globalOpen;
            var count = term ? state.strings.globalcount.replace('{$a->visible}', String(matches.length))
                .replace('{$a->total}', String(globalNotes.length)) : String(globalNotes.length);
            globalGroup.querySelector('summary').textContent = state.strings.globalnotes + ' · ' + count;
            var pageEmpty = list.querySelector('[data-region="page-empty"]');
            pageEmpty.hidden = !!term || state.notes.some(function(note) {return !note.isglobal;});
        }
    };

    var renderNotes = function() {
        var list = getList();

        updateSearchVisibility();

        if (!state.notes.length) {
            renderEmptyState();
            return;
        }

        list.innerHTML = '';
        var pageGroup = document.createElement('div');
        pageGroup.dataset.region = 'page-group';
        list.appendChild(pageGroup);
        var pageEmpty = document.createElement('p');
        pageEmpty.className = 'local-sidenotes__page-empty text-muted';
        pageEmpty.dataset.region = 'page-empty';
        pageEmpty.textContent = state.strings.pageempty;
        list.appendChild(pageEmpty);
        var globalGroup = document.createElement('details');
        globalGroup.className = 'local-sidenotes__globals';
        globalGroup.dataset.region = 'global-group';
        globalGroup.open = !!state.globalOpen;
        var summary = document.createElement('summary');
        summary.addEventListener('click', function() {
            if (!getSearchTerm()) {state.globalOpen = !globalGroup.open;}
        });
        globalGroup.appendChild(summary);
        var globalList = document.createElement('div');
        globalList.dataset.region = 'global-list';
        globalGroup.appendChild(globalList);
        globalGroup.addEventListener('toggle', function() {
            if (globalGroup.open) {scheduleAutogrowTextareas();}
        });
        list.appendChild(globalGroup);
        state.notes.forEach(function(note) {
            (note.isglobal ? globalList : pageGroup).appendChild(createNoteElement(note));
        });

        applyFilter();
        scheduleAutogrowTextareas();
    };

    var openSidebar = function() {
        setOpenState(true);
    };

    var createHighlightButton = function() {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = HIGHLIGHT_BUTTON_CLASS;
        button.setAttribute('aria-label', state.strings.highlightlabel);
        button.textContent = '+';
        button.setAttribute('hidden', 'hidden');
        document.body.appendChild(button);
        return button;
    };

    var hideHighlightButton = function(clearselection) {
        if (!state || !state.highlightbutton) {
            return;
        }

        state.highlightbutton.setAttribute('hidden', 'hidden');
        state.highlightselectiontext = '';

        if (clearselection) {
            try {
                window.getSelection().removeAllRanges();
            } catch (e) {
                // Ignore — selection API may not be available.
            }
        }
    };

    var showHighlightButton = function(rect, text) {
        var buttonwidth = 40;
        var buttonheight = 40;
        var spacing = 10;
        var top = rect.top - buttonheight - spacing;
        var left = rect.left + (rect.width / 2) - (buttonwidth / 2);
        var maxleft = Math.max(spacing, window.innerWidth - buttonwidth - spacing);

        if (top < spacing) {
            top = rect.bottom + spacing;
        }

        left = Math.max(spacing, Math.min(left, maxleft));

        state.highlightselectiontext = text;
        state.highlightbutton.style.top = top + 'px';
        state.highlightbutton.style.left = left + 'px';
        state.highlightbutton.removeAttribute('hidden');
    };

    var getValidSelection = function(targetWindow) {
        var selection;
        var text;
        var range;
        var container;
        var win = targetWindow || window;

        try {
            selection = win.getSelection();

            if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                return null;
            }

            text = normaliseSelectionText(selection.toString());

            if (text.length <= MIN_SELECTION_LENGTH) {
                return null;
            }

            range = selection.getRangeAt(0);
            container = range.commonAncestorContainer;

            if (container && container.nodeType === Node.TEXT_NODE) {
                container = container.parentNode;
            }

            if (!container || container.closest(SELECTORS.root)) {
                return null;
            }

            if (container.closest('input, textarea, button')) {
                return null;
            }

            return {
                text: text,
                rect: range.getBoundingClientRect()
            };
        } catch (e) {
            return null;
        }
    };

    var prependNote = function(note) {
        state.notes.unshift(note);
        renderNotes();
    };

    var setOpenState = function(isopen) {
        var panel = state.root.querySelector(SELECTORS.panel);
        var toggle = state.root.querySelector(SELECTORS.toggle);

        if (isopen) {
            state.root.classList.add('is-open');
        } else {
            state.root.classList.remove('is-open');
        }

        if (panel) {
            panel.setAttribute('aria-hidden', isopen ? 'false' : 'true');
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', isopen ? 'true' : 'false');
        }

        if (isopen) {
            scheduleAutogrowTextareas();
            var closeBtn = panel ? panel.querySelector(SELECTORS.close) : null;
            if (closeBtn) {
                closeBtn.focus();
            }
        } else {
            if (toggle) {
                toggle.focus();
            }
        }
    };

    var consumeOpenDrawerLink = function() {
        var url;

        try {
            url = new URL(window.location.href);
        } catch (e) {
            return false;
        }

        if (url.searchParams.get(OPEN_DRAWER_QUERY_KEY) !== '1') {
            return false;
        }

        url.searchParams.delete(OPEN_DRAWER_QUERY_KEY);
        if (window.history && typeof window.history.replaceState === 'function') {
            window.history.replaceState(window.history.state, document.title, url.toString());
        }
        return true;
    };

    var saveNote = function(note) {
        if (note.tagrequest) {return note.tagrequest.then(function() {return saveNote(note);});}
        if (note.saving) {return note.saving.then(function() {return saveNote(note);});}
        var request;
        var noteEl = getNoteElementByKey(note.clientid);
        var submittedTags = (note.tags || []).map(function(tag) {
            return tag.name || '';
        });
        var updateTags = !!note.tagschanged;

        if (noteEl) {
            setNoteStatus(noteEl, state.strings.savingtext, note.timemodified);
        }

        var args = note.id ? {noteid: note.id, operation: 'content', content: note.content,
            expectedcontent: note.savedcontent} : {
                id: note.id || 0,
                courseid: note.courseid || 0,
                content: note.content,
                url: note.url || state.pageurl,
                pagetitle: note.pagetitle || state.pagetitle,
                isglobal: !!note.isglobal,
                quote: note.quote || '',
                quoteurl: note.quoteurl || '',
                tags: submittedTags,
                updatetags: updateTags
            };
        request = Ajax.call([{
            methodname: note.id ? 'local_sidenotes_edit_note' : 'local_sidenotes_save_note',
            args: args
        }])[0];

        note.saving = Promise.resolve(request).then(function(response) {
            var savednote = normaliseNote(response);
            var currentnoteEl = getNoteElementByKey(note.clientid);

            savednote.hasquote = !!(savednote.quote && savednote.quote.trim() !== '');
            savednote.quotetext = savednote.quote;

            note.id = savednote.id;
            note.savedcontent = savednote.content;
            note.unbound = savednote.unbound;
            note.courseid = savednote.courseid;
            note.userid = savednote.userid;
            note.url = savednote.url;
            note.pagetitle = savednote.pagetitle;
            note.isglobal = savednote.isglobal;
            note.contentformat = savednote.contentformat;
            note.contenthtml = savednote.contenthtml;
            note.tagsenabled = savednote.tagsenabled;
            if (!updateTags || submittedTags.join('\n') === (note.tags || []).map(function(tag) {
                return tag.name || '';
            }).join('\n')) {
                note.tags = savednote.tags;
                note.tagschanged = false;
            }
            note.screenshots = savednote.screenshots;
            note.quote = savednote.quote;
            note.quoteurl = savednote.quoteurl;
            note.hasquote = savednote.hasquote;
            note.quotetext = savednote.quotetext;
            note.timecreated = savednote.timecreated;
            note.timemodified = savednote.timemodified;
            note.status = state.strings.savedtext;

            if (currentnoteEl) {
                setNoteStatus(currentnoteEl, note.status, note.timemodified);
                setNoteQuote(currentnoteEl, note);
                setNoteLocation(currentnoteEl, note.url, note.hasquote);
                setNoteGlobal(currentnoteEl, note);
                renderScreenshots(currentnoteEl, note);
                renderTagsAndPreview(currentnoteEl, note);
                if (savednote.unbound && !savednote.isglobal) {
                    state.notes = state.notes.filter(function(item) {return item.clientid !== note.clientid;});
                    currentnoteEl.remove();
                }
                applyFilter();

                setTimeout(function() {
                    note.status = '';
                    setNoteStatus(currentnoteEl, note.status, note.timemodified);
                }, 3000);
            }
            return response;
        }).catch(function(error) {
            note.status = state.strings.errortext;

            if (noteEl) {
                setNoteStatus(noteEl, note.status, note.timemodified);
            }

            Notification.exception(error);
            throw error;
        }).finally(function() {note.saving = null;});
        return note.saving;
    };

    // Categories save independently: never submit stale content or scope from the drawer.
    var saveTags = function(note, names, noteEl) {
        if (note.tagpending) {return;}
        note.tagpending = true;
        var form = noteEl.querySelector('[data-region="tag-form"]');
        form.querySelectorAll('input,button').forEach(function(control) {control.disabled = true;});
        renderTagsAndPreview(noteEl, note);
        var ready = note.id ? Promise.resolve(note.saving) : saveNote(note);
        note.tagrequest = Promise.resolve(ready).then(function() {
            return Ajax.call([{methodname: 'local_sidenotes_edit_note',
                args: {noteid: note.id, operation: 'tags', tags: names}}])[0];
        }).then(function(saved) {
            note.tags = saved.tags; note.timemodified = saved.timemodified;
            noteEl.querySelector(SELECTORS.tagsinput).value = '';
            setNoteStatus(noteEl, state.strings.savedtext, note.timemodified); applyFilter();
        }).catch(Notification.exception).finally(function() {
            note.tagrequest = null;
            note.tagpending = false; renderTagsAndPreview(noteEl, note);
            form.querySelectorAll('input,button').forEach(function(control) {control.disabled = false;});
        });
    };

    var scheduleSave = function(note) {
        var existingtimer = state.timers[note.clientid];

        if (existingtimer) {
            window.clearTimeout(existingtimer);
        }

        state.timers[note.clientid] = window.setTimeout(function() {
            delete state.timers[note.clientid];
            saveNote(note).catch(function() {
                // saveNote already presents the error and updates the note status.
            });
        }, SAVE_DELAY);
    };

    var loadNotes = function() {
        var request = Ajax.call([{
            methodname: 'local_sidenotes_get_notes',
            args: {
                courseid: state.courseid,
                pageurl: state.pageurl
            }
        }])[0];

        request.then(function(response) {
            state.notes = response.map(function(note) {
                return normaliseNote(note);
            });

            renderNotes();
            return response;
        }).catch(function(error) {
            Notification.exception(error);
        });
    };

    var deleteNote = function(note, noteEl) {
        var request = Ajax.call([{
            methodname: 'local_sidenotes_delete_note',
            args: {
                noteid: note.id
            }
        }])[0];

        request.then(function(response) {
            if (!response.deleted) {
                return response;
            }

            state.notes = state.notes.filter(function(item) {
                return item.clientid !== note.clientid;
            });

            if (state.timers[note.clientid]) {
                window.clearTimeout(state.timers[note.clientid]);
                delete state.timers[note.clientid];
            }

            if (noteEl) {
                noteEl.remove();
            }

            if (!state.notes.length) {
                renderEmptyState();
            }
            updateSearchVisibility();

            // Return focus to a logical element to prevent focus loss
            var addBtn = state.root.querySelector(SELECTORS.add);
            if (addBtn) {
                addBtn.focus();
            }
            return response;
        }).catch(function(error) {
            Notification.exception(error);
        });
    };

    var createHighlightNote = function(text) {
        var note = createDraftNote();
        var quoteurl = state.pageurl + '#:~:text=' + encodeURIComponent(text);

        note.content = '';
        note.url = state.pageurl;
        note.pagetitle = state.pagetitle;
        note.quote = formatQuotedNote(text);
        note.quoteurl = quoteurl;
        note.timemodified = Math.floor(Date.now() / 1000);
        note.status = state.strings.savingtext;

        prependNote(note);
        openSidebar();

        var noteEl = getNoteElementByKey(note.clientid);
        var textarea = noteEl ? noteEl.querySelector(SELECTORS.textarea) : null;
        if (textarea) {
            Editor.focus(textarea).catch(Notification.exception);
        }

        saveNote(note).catch(function() {
            // saveNote already presents the error and updates the note status.
        });
    };

    var fileToBase64 = function(file) {
        return new Promise(function(resolve, reject) {
            var reader = new FileReader();
            reader.onload = function() {
                var result = String(reader.result || '');
                var separator = result.indexOf(',');
                resolve(separator === -1 ? result : result.substring(separator + 1));
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    };

    var uploadScreenshot = function(note, file) {
        var noteEl = getNoteElementByKey(note.clientid);
        if (state.timers[note.clientid]) {
            window.clearTimeout(state.timers[note.clientid]);
            delete state.timers[note.clientid];
        }

        var ensureSaved = note.id ? Promise.resolve() : saveNote(note);
        return ensureSaved.then(function() {
            if (noteEl) {
                setNoteStatus(noteEl, state.strings.uploadingtext, note.timemodified);
            }
            return fileToBase64(file);
        }).then(function(data) {
            return Ajax.call([{
                methodname: 'local_sidenotes_upload_screenshot',
                args: {
                    noteid: note.id,
                    filename: file.name || 'screenshot.png',
                    mimetype: file.type,
                    data: data
                }
            }])[0];
        }).then(function(screenshot) {
            note.screenshots = note.screenshots || [];
            note.screenshots.push(screenshot);
            if (noteEl) {
                renderScreenshots(noteEl, note);
                setNoteStatus(noteEl, state.strings.savedtext, note.timemodified);
            }
            return screenshot;
        }).catch(function(error) {
            if (noteEl) {
                setNoteStatus(noteEl, state.strings.errortext, note.timemodified);
            }
            Notification.exception(error);
        });
    };

    var deleteScreenshot = function(note, fileid, noteEl) {
        Ajax.call([{
            methodname: 'local_sidenotes_delete_screenshot',
            args: {noteid: note.id, fileid: fileid}
        }])[0].then(function(response) {
            if (response.deleted) {
                note.screenshots = (note.screenshots || []).filter(function(screenshot) {
                    return Number(screenshot.id) !== Number(fileid);
                });
                renderScreenshots(noteEl, note);
            }
            return response;
        }).catch(Notification.exception);
    };

    var bindEvents = function() {
        document.addEventListener('local_sidenotes:archivechanged', function(event) {
            var change = event.detail || {};
            if (!change.noteid) {return;}
            var note = state.notes.find(function(item) {return Number(item.id) === Number(change.noteid);});
            if (change.archived) {
                if (!note) {return;}
                window.clearTimeout(state.timers[note.clientid]); delete state.timers[note.clientid];
                var element = getNoteElementByKey(note.clientid);
                if (note.saving || note.tagpending || note.content !== note.savedcontent) {
                    // Never discard a separately edited local draft when the overview archives its record.
                    note.archived = true;
                    Str.get_string('archive:draftblocked', 'local_sidenotes').then(function(message) {
                        if (element && element.isConnected) {setNoteStatus(element, message, note.timemodified);}
                    }).catch(Notification.exception);
                    return;
                }
                state.notes = state.notes.filter(function(item) {return item !== note;});
                if (element) {element.remove();}
                updateSearchVisibility(); applyFilter();
            } else if (note) {
                note.archived = false;
            } else {
                Promise.resolve(Ajax.call([{methodname: 'local_sidenotes_get_notes',
                    args: {courseid: state.courseid, pageurl: state.pageurl}}])[0]).then(function(notes) {
                    var restored = notes.find(function(item) {return Number(item.id) === Number(change.noteid);});
                    if (!restored || state.notes.some(function(item) {return Number(item.id) === Number(restored.id);})) {return;}
                    restored = normaliseNote(restored); state.notes.unshift(restored);
                    var group = getList().querySelector(restored.isglobal ? '[data-region="global-list"]' : '[data-region="page-group"]');
                    if (!group) {renderNotes();} else {group.prepend(createNoteElement(restored)); applyFilter(); scheduleAutogrowTextareas();}
                }).catch(Notification.exception);
            }
        });
        state.highlightbutton.addEventListener('mousedown', function(e) {
            e.preventDefault();
        });

        state.highlightbutton.addEventListener('click', function() {
            var text = state.highlightselectiontext;
            hideHighlightButton(true);

            if (!text) {
                return;
            }

            createHighlightNote(text);
        });

        // Handle text selection highlight on mouseup.
        document.addEventListener('mouseup', function(e) {
            if (e.target.closest('.' + HIGHLIGHT_BUTTON_CLASS)) {
                return;
            }
            var result = getValidSelection();
            if (result && result.rect && result.rect.width) {
                showHighlightButton(result.rect, result.text);
            } else {
                hideHighlightButton(false);
            }
        });

        document.addEventListener('keyup', function(e) {
            if (e.key === 'Escape' && state.root.classList.contains('is-open')) {
                setOpenState(false);
            }
        });

        var handleToggleClick = function() {
            setOpenState(!state.root.classList.contains('is-open'));
        };

        var handleCloseClick = function() {
            setOpenState(false);
        };

        var handleAddClick = function() {
            var note = createDraftNote();
            prependNote(note);

            var noteEl = getNoteElementByKey(note.clientid);
            var textarea = noteEl ? noteEl.querySelector(SELECTORS.textarea) : null;
            if (textarea) {
                Editor.focus(textarea).catch(Notification.exception);
            }
        };

        var handleCopyClick = function(e, copyBtn) {
            e.preventDefault();
            var icon = copyBtn.querySelector('i');
            var noteEl = copyBtn.closest(SELECTORS.note);
            var textarea = noteEl ? noteEl.querySelector(SELECTORS.textarea) : null;
            if (!textarea) {
                return;
            }

            var textToCopy = textarea.value;
            if (!textToCopy) {
                return;
            }

            navigator.clipboard.writeText(textToCopy).then(function() {
                if (icon) {
                    icon.classList.remove('fa-regular', 'fa-copy');
                    icon.classList.add('fa-solid', 'fa-check');
                }
                copyBtn.style.color = '#28a745';

                setTimeout(function() {
                    if (icon) {
                        icon.classList.remove('fa-solid', 'fa-check');
                        icon.classList.add('fa-regular', 'fa-copy');
                    }
                    copyBtn.style.color = '#6c757d';
                }, 2000);

                return true;
            }).catch(function() {
                // Ignore.
            });
        };

        var handleDeleteClick = function(deleteBtn) {
            var noteEl = deleteBtn.closest(SELECTORS.note);
            var note = noteEl ? getNoteByKey(noteEl.getAttribute('data-note-key')) : null;

            if (!note) {
                return;
            }

            if (!note.id) {
                if (state.timers[note.clientid]) {
                    window.clearTimeout(state.timers[note.clientid]);
                    delete state.timers[note.clientid];
                }

                state.notes = state.notes.filter(function(item) {
                    return item.clientid !== note.clientid;
                });

                if (noteEl) {
                    noteEl.remove();
                }

                if (!state.notes.length) {
                    renderEmptyState();
                } else {
                    applyFilter();
                }
                updateSearchVisibility();

                var rootAddBtn = state.root.querySelector(SELECTORS.add);
                if (rootAddBtn) {
                    rootAddBtn.focus();
                }
                return;
            }

            Str.get_strings([
                {key: 'confirm', component: 'core'},
                {key: 'delete', component: 'core'},
                {key: 'cancel', component: 'core'}
            ]).then(function(strings) {
                Notification.confirm(
                    strings[0],
                    state.strings.deleteconfirm,
                    strings[1],
                    strings[2],
                    function() {
                        deleteNote(note, noteEl);
                    }
                );
                return true;
            }).catch(Notification.exception);
        };

        var handleQuoteClick = function(e, quoteLink) {
            var targetUrl = quoteLink.getAttribute('href');
            var currentUrl = window.location.href.split('#')[0];

            if (targetUrl && !/^(https?:\/\/|#)/i.test(targetUrl)) {
                e.preventDefault();
                return;
            }

            if (targetUrl && (targetUrl.indexOf(currentUrl) === 0 || targetUrl.indexOf('#') === 0)) {
                e.preventDefault();

                var hashIndex = targetUrl.indexOf('#');
                if (hashIndex === -1) {
                    return;
                }

                setOpenState(false);

                var noteEl = quoteLink.closest(SELECTORS.note);
                var quoteElement = noteEl ? noteEl.querySelector(SELECTORS.quote) : null;
                var originalText = quoteElement ? quoteElement.textContent : '';
                if (quoteElement) {
                    quoteElement.textContent = '';
                }

                window.setTimeout(function() {
                    window.location.hash = targetUrl.substring(hashIndex + 1);

                    window.setTimeout(function() {
                        if (quoteElement) {
                            quoteElement.textContent = originalText;
                        }
                    }, 100);
                }, 10);
            }
        };

        state.root.addEventListener('click', function(e) {
            var removeTag = e.target.closest('[data-action="remove-tag"]');
            if (removeTag && !removeTag.disabled) {
                var tagEl = removeTag.closest(SELECTORS.note);
                var tagNote = getNoteByKey(tagEl.getAttribute('data-note-key'));
                saveTags(tagNote, tagNote.tags.map(function(tag) {return tag.name;})
                    .filter(function(name) {return name !== removeTag.dataset.tagname;}), tagEl);
                return;
            }
            var toggleBtn = e.target.closest(SELECTORS.toggle);
            if (toggleBtn) {
                handleToggleClick();
                return;
            }

            var closeBtn = e.target.closest(SELECTORS.close);
            if (closeBtn) {
                handleCloseClick();
                return;
            }

            var addBtn = e.target.closest(SELECTORS.add);
            if (addBtn) {
                handleAddClick();
                return;
            }

            var clearSearchBtn = e.target.closest(SELECTORS.clearsearch);
            if (clearSearchBtn) {
                var searchInput = state.root.querySelector(SELECTORS.search);
                if (searchInput) {
                    searchInput.value = '';
                    handleSearchInput();
                    searchInput.focus();
                }
                return;
            }

            var copyBtn = e.target.closest('[data-action="copy-note"]');
            if (copyBtn) {
                handleCopyClick(e, copyBtn);
                return;
            }

            var deleteScreenshotBtn = e.target.closest(SELECTORS.deletescreenshot);
            if (deleteScreenshotBtn) {
                var screenshotNoteEl = deleteScreenshotBtn.closest(SELECTORS.note);
                var screenshotNote = screenshotNoteEl ?
                    getNoteByKey(screenshotNoteEl.getAttribute('data-note-key')) : null;
                if (screenshotNote && screenshotNote.id) {
                    deleteScreenshot(
                        screenshotNote,
                        Number(deleteScreenshotBtn.getAttribute('data-fileid')),
                        screenshotNoteEl
                    );
                }
                return;
            }

            var deleteBtn = e.target.closest(SELECTORS.deletebutton);
            if (deleteBtn) {
                handleDeleteClick(deleteBtn);
                return;
            }

            var quoteLink = e.target.closest(SELECTORS.quotelink);
            var quoteToggle = e.target.closest('[data-action="toggle-quote"]');
            if (quoteToggle) {
                var expanded = quoteToggle.getAttribute('aria-expanded') !== 'true';
                quoteToggle.setAttribute('aria-expanded', String(expanded));
                quoteToggle.closest(SELECTORS.quotewrapper).classList.toggle('local-sidenotes-center__quote--expanded', expanded);
                quoteToggle.textContent = expanded ? quoteToggle.dataset.less : quoteToggle.dataset.more;
                return;
            }
            if (quoteLink) {
                handleQuoteClick(e, quoteLink);
                return;
            }
        });

        state.root.addEventListener('input', function(e) {
            var textarea = e.target.closest(SELECTORS.textarea);
            if (textarea) {
                var note = getNoteByKey(textarea.getAttribute('data-note-key'));
                if (!note) {
                    return;
                }

                note.content = textarea.value;
                note.timemodified = Math.floor(Date.now() / 1000);
                autogrowTextarea(textarea);

                var noteEl = textarea.closest(SELECTORS.note);
                var cBtn = noteEl ? noteEl.querySelector('[data-action="copy-note"]') : null;

                if (cBtn) {
                    if (note.content.trim().length > 0) {
                        cBtn.style.display = 'inline-flex';
                    } else {
                        cBtn.style.display = 'none';
                    }
                }

                scheduleSave(note);
                applyFilter();
                return;
            }

            var search = e.target.closest(SELECTORS.search);
            if (search) {
                handleSearchInput();
            }
        });

        state.root.addEventListener('submit', function(e) {
            if (!e.target.matches('[data-region="tag-form"]')) {return;}
            e.preventDefault();
            var noteEl = e.target.closest(SELECTORS.note), input = noteEl.querySelector(SELECTORS.tagsinput);
            var note = getNoteByKey(noteEl.getAttribute('data-note-key'));
            var names = parseTags(input.value);
            if (names.length) {saveTags(note, note.tags.map(function(tag) {return tag.name;}).concat(names), noteEl);}
        });

        state.root.addEventListener('paste', function(e) {
            var textarea = e.target.closest(SELECTORS.textarea);
            if (!textarea || !e.clipboardData || !e.clipboardData.items) {
                return;
            }

            var images = [];
            Array.prototype.forEach.call(e.clipboardData.items, function(item) {
                if (item.kind === 'file' && /^image\/(png|jpeg|webp|gif)$/i.test(item.type)) {
                    var file = item.getAsFile();
                    if (file) {
                        images.push(file);
                    }
                }
            });
            if (!images.length) {
                return;
            }

            e.preventDefault();
            var note = getNoteByKey(textarea.getAttribute('data-note-key'));
            if (!note) {
                return;
            }
            images.reduce(function(chain, file) {
                return chain.then(function() {
                    return uploadScreenshot(note, file);
                });
            }, Promise.resolve());
        });

        state.root.addEventListener('keyup', function(e) {
            var search = e.target.closest(SELECTORS.search);
            if (search) {
                handleSearchInput();
            }
        });

        var handleSearchInput = function() {
            var term = getSearchTerm();
            var clearBtn = state.root.querySelector(SELECTORS.clearsearch);

            if (clearBtn) {
                if (term) {
                    clearBtn.removeAttribute('hidden');
                } else {
                    clearBtn.setAttribute('hidden', 'hidden');
                }
            }

            if (!state.notes.length) {
                renderEmptyState();
                return;
            }

            renderNotes();

            if (!term) {
                var emptyState = getList().querySelector(SELECTORS.emptystate);
                if (emptyState) {
                    emptyState.remove();
                }

                var noteEls = getList().querySelectorAll(SELECTORS.note);
                noteEls.forEach(function(n) {
                    n.style.display = '';
                });
            }
        };

        // Listen for highlight messages triggered inside iframes.
        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }
            if (event.data && event.data.app === 'sidenotes' && event.data.action === 'iframe_highlight') {
                var text = event.data.text;
                if (text && text.length > MIN_SELECTION_LENGTH) {
                    createHighlightNote(text);
                }
            }
        });
    };

    return {
        init: function(config) {
            var rootEl = getRoot();

            if (!rootEl) {
                return;
            }

            state = {
                root: rootEl,
                courseid: Number(config.courseid || rootEl.getAttribute('data-courseid')),
                pageurl: config.pageurl || rootEl.getAttribute('data-pageurl') || window.location.href.split('#')[0],
                pagetitle: config.pagetitle || rootEl.getAttribute('data-pagetitle') || document.title,
                tagsenabled: rootEl.getAttribute('data-tagsenabled') === '1',
                notes: [],
                globalOpen: false,
                timers: {},
                strings: {
                    placeholder: rootEl.getAttribute('data-placeholder'),
                    emptytext: rootEl.getAttribute('data-emptytext'),
                    savingtext: rootEl.getAttribute('data-savingtext'),
                    savedtext: rootEl.getAttribute('data-savedtext'),
                    errortext: rootEl.getAttribute('data-errortext'),
                    updatedlabel: rootEl.getAttribute('data-updatedlabel'),
                    removetaglabel: rootEl.getAttribute('data-removetaglabel'),
                    highlightlabel: rootEl.getAttribute('data-highlightlabel'),
                    deleteconfirm: rootEl.getAttribute('data-deleteconfirm'),
                    noresultstext: rootEl.getAttribute('data-noresultstext'),
                    globalbadge: rootEl.getAttribute('data-globalbadge'),
                    globalnotes: rootEl.getAttribute('data-globalnotes'),
                    globalcount: rootEl.getAttribute('data-globalcount'),
                    pageempty: rootEl.getAttribute('data-pageempty'),
                    uploadingtext: rootEl.getAttribute('data-uploadingtext'),
                    deleteimagelabel: rootEl.getAttribute('data-deleteimagelabel')
                }
            };

            state.highlightbutton = createHighlightButton();
            state.highlightselectiontext = '';

            bindEvents();
            loadNotes();
            if (consumeOpenDrawerLink()) {
                setOpenState(true);
            }
        },

        initIframe: function(config) {
            state = {
                highlightselectiontext: '',
                timers: {},
                strings: {
                    highlightlabel: config.highlightlabel || '+'
                }
            };

            state.highlightbutton = createHighlightButton();

            // Central handler for mouseup events across contexts.
            var handleMouseUp = function(e, win) {
                if (e.target.closest('.' + HIGHLIGHT_BUTTON_CLASS)) {
                    return;
                }

                window.setTimeout(function() {
                    var result = getValidSelection(win);

                    if (result && result.rect && result.rect.width) {
                        showHighlightButton(result.rect, result.text);
                    } else {
                        hideHighlightButton(false);
                    }
                }, 10);
            };

            // Listen on the current iframe context (e.g., embed.php).
            document.addEventListener('mouseup', function(e) {
                handleMouseUp(e, window);
            }, true);

            // Attempt to locate an inner iframe (e.g., H5P) and attach the listener.
            var attachToInnerIframe = function(attempts) {
                if (attempts <= 0) {
                    return;
                }

                var h5pIframe = document.querySelector('.h5p-iframe');
                if (h5pIframe) {
                    var bindInner = function() {
                        try {
                            var innerWin = h5pIframe.contentWindow;
                            var innerDoc = h5pIframe.contentDocument;

                            if (innerDoc) {
                                innerDoc.addEventListener('mouseup', function(e) {
                                    handleMouseUp(e, innerWin);
                                }, true);
                            }
                        } catch (err) {
                            // Cross-origin boundaries may prevent attachment.
                        }
                    };

                    if (h5pIframe.contentDocument && h5pIframe.contentDocument.readyState === 'complete') {
                        bindInner();
                    } else {
                        h5pIframe.addEventListener('load', bindInner);
                    }
                } else {
                    setTimeout(function() {
                        attachToInnerIframe(attempts - 1);
                    }, 500);
                }
            };

            // Retry for up to 5 seconds (10 attempts * 500ms).
            attachToInnerIframe(10);

            state.highlightbutton.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });
            state.highlightbutton.addEventListener('click', function() {
                var text = state.highlightselectiontext;
                hideHighlightButton(true);

                if (text) {
                    window.parent.postMessage({
                        app: 'sidenotes',
                        action: 'iframe_highlight',
                        text: text
                    }, window.location.origin);
                }
            });
        }
    };
});
