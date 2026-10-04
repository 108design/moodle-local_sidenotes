// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/**
 * Field-specific inline saves and non-destructive live search.
 * @module local_sidenotes/center
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str', 'local_sidenotes/mini_editor', 'core/user_date', 'core_filters/events',
    'local_sidenotes/screenshot_gallery'],
function(Ajax, Notification, Str, Editor, UserDate, FilterEvents, Gallery) {
    return {init: function() {
        var center = document.querySelector('.local-sidenotes-center');
        if (!center || center.dataset.initialized) {return;}
        center.dataset.initialized = '1';
        Gallery.init(center);
        var search = document.getElementById('searchterm'), form = search.form;
        var clear = document.getElementById('clearsearch');
        var results = center.querySelector('[data-region="sidenotes-results"]');
        var states = new WeakMap(), activeRequest = null, searchTimer = null, mutation = 0, strings = {};
        var composing = false, clearingArchive = false;
        var stringsReady = Promise.resolve(Str.get_strings([
            {key: 'confirm', component: 'core'}, {key: 'delete', component: 'core'},
            {key: 'cancel', component: 'core'}, {key: 'note:saving', component: 'local_sidenotes'},
            {key: 'note:saved', component: 'local_sidenotes'}, {key: 'note:error', component: 'local_sidenotes'},
            {key: 'center:discard', component: 'local_sidenotes'}, {key: 'screenshot:deleteconfirm', component: 'local_sidenotes'},
            {key: 'note:delete_confirm', component: 'local_sidenotes'}, {key: 'screenshot:uploading', component: 'local_sidenotes'},
            {key: 'screenshot:delete', component: 'local_sidenotes'}, {key: 'tags:remove', component: 'local_sidenotes', param: ''},
            {key: 'center:pendingtext', component: 'local_sidenotes'},
            {key: 'archive:deleteconfirm', component: 'local_sidenotes'},
            {key: 'archive:archived', component: 'local_sidenotes'},
            {key: 'archive:restored', component: 'local_sidenotes'},
            {key: 'archive:cleared', component: 'local_sidenotes'},
            {key: 'search:removefilter', component: 'local_sidenotes', param: '__TAG__'}
        ])).then(function(values) {
            ['confirm', 'delete', 'cancel', 'saving', 'saved', 'error', 'discard', 'deleteimage',
                'deletenote', 'uploading', 'imagelabel', 'removetag', 'pendingtext',
                'deletepermanent', 'archived', 'restored', 'cleared', 'removefilter']
                .forEach(function(key, i) {strings[key] = values[i];});
        });
        var region = function(card, name) {return card.querySelector('[data-region="' + name + '"]');};
        var stateFor = function(card) {
            if (!states.has(card)) {
                var text = region(card, 'note-text');
                states.set(card, {baseline: text ? text.value : '', editing: false, pending: false});
            }
            return states.get(card);
        };
        var call = function(method, args) {
            return Promise.resolve(Ajax.call([{methodname: 'local_sidenotes_' + method, args: args}])[0]);
        };
        var status = function(card, text, error) {
            var node = region(card, 'note-status'); node.textContent = text;
            node.classList.toggle('text-danger', !!error); node.setAttribute('role', error ? 'alert' : 'status');
        };
        var grow = function(text) {
            if (!text || !text.offsetWidth) {return;}
            text.style.height = 'auto';
            var maximum = Math.max(100, window.innerHeight * 0.45);
            text.style.height = Math.min(maximum, Math.max(100, text.scrollHeight + 2)) + 'px';
            text.style.overflowY = text.scrollHeight > maximum ? 'auto' : 'hidden';
        };
        var busy = function(card, value) {
            stateFor(card).pending = value; card.setAttribute('aria-busy', String(value));
            card.querySelectorAll('button, input, select').forEach(function(control) {
                if (value) {control.dataset.sidenotesDisabled = String(control.disabled); control.disabled = true;}
                else if (control.dataset.sidenotesDisabled !== undefined) {
                    control.disabled = control.dataset.sidenotesDisabled === 'true'; delete control.dataset.sidenotesDisabled;
                }
            });
        };
        var beginMutation = function() {mutation++; if (activeRequest) {activeRequest.abort();}};
        var archiveStatus = function(message, error) {
            var node = center.querySelector('[data-region="archive-status"]');
            if (node) {node.textContent = message; node.classList.toggle('text-danger', !!error);}
        };
        var syncCounts = function(response) {
            [['active-count', 'activecount'], ['archive-count', 'archivecount']].forEach(function(pair) {
                var node = center.querySelector('[data-region="' + pair[0] + '"]');
                if (node) {node.textContent = String(response[pair[1]]);}
            });
            var clearArchive = center.querySelector('[data-action="empty-archive"]');
            if (clearArchive) {clearArchive.disabled = clearingArchive || !response.archivecount;}
        };
        var announceArchive = function(response, message) {
            syncCounts(response); archiveStatus(strings[message], false);
            document.dispatchEvent(new CustomEvent('local_sidenotes:archivechanged', {detail: response}));
        };
        var removeCard = function(card) {
            card.parentElement.remove();
            var next = results.querySelector('[data-action="toggle-archive"]')
                || center.querySelector('[data-region="active-view"]');
            if (next) {next.focus({preventScroll: true});}
        };
        var run = function(card, action, message) {
            if (stateFor(card).pending) {return Promise.resolve();}
            beginMutation(); busy(card, true);
            return stringsReady.then(function() {status(card, strings[message || 'saving'], false); return action();})
                .then(function() {
                    if (card.isConnected) {
                        var draft = stateFor(card).editing && region(card, 'note-text').value !== stateFor(card).baseline;
                        status(card, draft ? strings.pendingtext : strings.saved, false);
                    }
                })
                .catch(function(error) {if (card.isConnected) {status(card, error.message || strings.error, true);}})
                .finally(function() {busy(card, false); beginMutation();});
        };
        var confirm = function(message, label) {
            return stringsReady.then(function() {
                return new Promise(function(resolve) {
                    Notification.confirm(strings.confirm, strings[message], strings[label || 'delete'], strings.cancel,
                        function() {resolve(true);}, function() {resolve(false);});
                });
            });
        };
        var updateDate = function(card, note) {
            var target = region(card, 'note-date'); target.dataset.timestamp = String(note.timemodified);
            Str.get_string('strftimedatetimeshort', 'langconfig').then(function(format) {
                return UserDate.get([{timestamp: note.timemodified, format: format}]);
            }).then(function(dates) {
                if (target.dataset.timestamp === String(note.timemodified)) {target.textContent = dates[0];}
            }).catch(Notification.exception);
        };
        var ensureSaved = function(card) {
            if (Number(card.dataset.noteid)) {return Promise.resolve(Number(card.dataset.noteid));}
            // Create a container only: media/tag actions do not implicitly commit a text draft.
            return call('edit_note', {noteid: 0, operation: 'create'}).then(function(note) {
                card.dataset.noteid = String(note.id);
                var complete = card.querySelector('[data-action="toggle-archive"]');
                if (complete) {
                    complete.hidden = false;
                    complete.dataset.sidenotesDisabled = 'false';
                    FilterEvents.notifyFilterContentUpdated([card]);
                }
                card.querySelectorAll('[id], label[for]').forEach(function(node) {
                    ['id', 'for'].forEach(function(attr) {
                        if (node.hasAttribute(attr)) {node.setAttribute(attr, node.getAttribute(attr).replace(/-0$/, '-' + note.id));}
                    });
                });
                updateDate(card, note); return note.id;
            });
        };
        var tagNames = function(card) {
            return Array.from(region(card, 'note-tags').querySelectorAll('[data-tagname]'))
                .map(function(tag) {return tag.dataset.tagname;});
        };
        var updateTags = function(card, tags) {
            var list = region(card, 'note-tags'); list.replaceChildren();
            var suggestions = document.getElementById('sidenotes-private-tags');
            tags.forEach(function(tag) {
                var chip = document.createElement('span');
                chip.className = 'badge local-sidenotes-center__tag';
                chip.style.setProperty('--sidenotes-tag-bg', tag.background);
                chip.style.setProperty('--sidenotes-tag-fg', tag.foreground);
                chip.dataset.tagname = tag.name;
                var name = document.createElement('span'); name.textContent = tag.name;
                var remove = document.createElement('button'); remove.type = 'button';
                remove.dataset.action = 'remove-tag'; remove.textContent = '×';
                remove.setAttribute('aria-label', strings.removetag + tag.name);
                chip.append(name, remove); list.append(chip);
                if (!Array.from(suggestions.options).some(function(option) {return option.value === tag.name;})) {
                    suggestions.append(new Option(tag.name, tag.name));
                }
                var filter = document.getElementById('tagfilter');
                if (filter && !Array.from(filter.querySelectorAll('input')).some(function(option) {
                    return option.value === String(tag.id);
                })) {
                    var option = document.createElement('input'); option.type = 'checkbox'; option.name = 'tags[]';
                    option.value = String(tag.id); option.dataset.name = tag.name;
                    option.dataset.background = tag.background; option.dataset.foreground = tag.foreground;
                    var label = document.createElement('label'), caption = document.createElement('span');
                    caption.textContent = tag.name; label.append(option, caption);
                    filter.querySelector('.local-plugin-search-bar-tag-options').append(label);
                }
            });
        };
        var saveTags = function(card, tags) {
            return run(card, function() {
                return ensureSaved(card).then(function(id) {return call('edit_note', {noteid: id, operation: 'tags', tags: tags});})
                    .then(function(note) {
                        updateTags(card, note.tags); updateDate(card, note);
                        var input = region(card, 'tag-input');
                        if (input) {input.value = ''; requestAnimationFrame(function() {input.focus();});}
                    });
            });
        };
        var openEditor = function(card) {
            if (stateFor(card).editing) {Editor.focus(region(card, 'note-text')).catch(Notification.exception); return;}
            stateFor(card).editing = true;
            region(card, 'note-content').hidden = true; region(card, 'note-editor').hidden = false;
            region(card, 'note-scope').hidden = false;
            card.querySelector('[data-action="edit-text"]').hidden = true;
            requestAnimationFrame(function() {Editor.focus(region(card, 'note-text')).catch(Notification.exception);});
        };
        var closeEditor = function(card) {
            stateFor(card).editing = false; region(card, 'note-text').value = stateFor(card).baseline;
            var editor = Editor.get(region(card, 'note-text'));
            if (editor && editor.reset) {editor.reset(stateFor(card).baseline, region(card, 'note-text').dataset.contentformat);}
            region(card, 'note-editor').hidden = true; region(card, 'note-scope').hidden = true;
            region(card, 'note-content').hidden = false;
            var pencil = card.querySelector('[data-action="edit-text"]'); pencil.hidden = false;
            // Saving disables buttons temporarily, so restore focus after the busy state clears.
            requestAnimationFrame(function() {pencil.focus();});
        };
        var saveText = function(card) {
            var text = region(card, 'note-text'), submitted = text.value;
            return run(card, function() {
                return ensureSaved(card).then(function(id) {
                    return call('edit_note', {noteid: id, operation: 'content', content: submitted,
                        expectedcontent: stateFor(card).baseline});
                }).then(function(note) {
                    stateFor(card).baseline = note.content; region(card, 'note-content').innerHTML = note.contenthtml;
                    text.dataset.contentformat = String(note.contentformat);
                    updateDate(card, note);
                    if (text.value === submitted) {closeEditor(card);}
                });
            });
        };
        var appendImage = function(card, screenshot) {
            var image = document.createElement('div'); image.className = 'local-sidenotes-center__image';
            image.dataset.fileid = String(screenshot.id);
            var link = document.createElement('a'); link.href = screenshot.url; link.target = '_blank'; link.rel = 'noopener';
            link.setAttribute('data-sidenotes-image', '1');
            var img = document.createElement('img'); img.src = screenshot.url; img.alt = screenshot.filename; link.append(img);
            var remove = document.createElement('button'); remove.type = 'button';
            remove.className = 'local-sidenotes-center__image-delete'; remove.dataset.action = 'delete-image';
            remove.title = strings.imagelabel; remove.setAttribute('aria-label', strings.imagelabel);
            remove.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i>';
            image.append(link, remove); region(card, 'note-screenshots').append(image);
        };
        var readImage = function(file) {
            return new Promise(function(resolve, reject) {
                var reader = new FileReader(); reader.onload = function() {resolve(String(reader.result).split(',')[1]);};
                reader.onerror = reject; reader.readAsDataURL(file);
            });
        };
        center.addEventListener('click', function(event) {
            var button = event.target.closest('[data-action]'); if (!button || button.disabled) {return;}
            var card = button.closest('[data-noteid]'), action = button.dataset.action;
            if (action === 'remove-filter-tag' && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
                event.preventDefault(); beginMutation();
                var value = button.dataset.filterTag;
                form.querySelectorAll('[name="tags[]"]').forEach(function(input) {
                    if (input.value === value) {
                        if (input.type === 'hidden') {input.remove();} else {input.checked = false;}
                    }
                });
                document.querySelector('#tagfilter summary').focus();
                submitSearch(searchUrl());
            } else if (action === 'remove-filter-course' || action === 'remove-filter-search') {
                event.preventDefault(); beginMutation(); window.clearTimeout(searchTimer);
                var field = action === 'remove-filter-course' ? document.getElementById('coursefilter') : search;
                field.value = field === search ? '' : '0'; field.focus();
                submitSearch(searchUrl());
            } else if (action === 'toggle-quote') {
                var expanded = button.getAttribute('aria-expanded') !== 'true';
                button.setAttribute('aria-expanded', String(expanded));
                button.closest('blockquote').classList.toggle('local-sidenotes-center__quote--expanded', expanded);
                button.textContent = expanded ? button.dataset.less : button.dataset.more;
            } else if (action === 'empty-archive') {
                if (clearingArchive) {return;}
                clearingArchive = true; button.disabled = true; beginMutation();
                var preview;
                stringsReady.then(function() {return call('manage_archive', {operation: 'preview'});})
                    .then(function(response) {
                        preview = response;
                        if (!preview.archivecount) {return false;}
                        return Str.get_string('archive:clearconfirm', 'local_sidenotes', preview.archivecount)
                            .then(function(message) {return new Promise(function(resolve) {
                                Notification.confirm(strings.confirm, message, strings.delete, strings.cancel,
                                    function() {resolve(true);}, function() {resolve(false);});
                            });});
                    }).then(function(yes) {
                        if (!yes) {return;}
                        return call('manage_archive', {operation: 'empty', revision: preview.revision})
                            .then(function(response) {announceArchive(response, 'cleared');});
                    }).catch(function(error) {archiveStatus(error.message, true);})
                    .finally(function() {clearingArchive = false; button.disabled = false; beginMutation(); submitSearch(searchUrl());});
            } else if (action === 'add-note') {
                var existing = results.querySelector('[data-noteid="0"]'); if (existing) {openEditor(existing); return;}
                beginMutation();
                results.prepend(document.importNode(center.querySelector('[data-region="new-note-template"]').content, true));
                results.querySelectorAll(':scope > .col-12 > .alert').forEach(function(alert) {alert.parentElement.remove();});
                openEditor(results.querySelector('[data-noteid="0"]'));
            } else if (!card) {return;}
            else if (action === 'edit-text') {openEditor(card);}
            else if (action === 'save-text') {saveText(card);}
            else if (action === 'cancel-text') {
                var discard = function() {
                    if (!Number(card.dataset.noteid)) {
                        card.parentElement.remove(); center.querySelector('[data-action="add-note"]').focus();
                    } else {closeEditor(card); status(card, '', false);}
                };
                if (region(card, 'note-text').value !== stateFor(card).baseline) {
                    confirm('discard', 'confirm').then(function(yes) {if (yes) {discard();}});
                } else {discard();}
            } else if (action === 'remove-tag') {
                var removed = button.closest('[data-tagname]').dataset.tagname;
                saveTags(card, tagNames(card).filter(function(name) {return name !== removed;}));
            } else if (action === 'delete-image') {
                confirm('deleteimage').then(function(yes) {
                    if (yes) {run(card, function() {
                        var image = button.closest('[data-fileid]');
                        return call('delete_screenshot', {noteid: Number(card.dataset.noteid), fileid: Number(image.dataset.fileid)})
                            .then(function() {image.remove();});
                    });}
                });
            } else if (action === 'delete-note') {
                confirm('deletepermanent').then(function(yes) {
                    var deleted = false;
                    if (yes) {run(card, function() {
                        if (!Number(card.dataset.noteid)) {card.parentElement.remove(); deleted = true; return Promise.resolve();}
                        return call('delete_note', {noteid: Number(card.dataset.noteid), expectedarchived: Number(card.dataset.archived || 0)}).then(function() {
                            removeCard(card);
                            deleted = true;
                            document.dispatchEvent(new CustomEvent('local_sidenotes:archivechanged',
                                {detail: {noteid: Number(card.dataset.noteid), archived: true, removed: true}}));
                        });
                    }).then(function() {if (deleted) {submitSearch(searchUrl());}});}
                });
            }
        });
        center.addEventListener('submit', function(event) {
            if (!event.target.matches('[data-region="tag-form"]')) {return;}
            event.preventDefault(); var card = event.target.closest('[data-noteid]');
            var name = region(card, 'tag-input').value.trim(); if (name) {saveTags(card, tagNames(card).concat([name]));}
        });
        center.addEventListener('input', function(event) {
            if (event.target.matches('[data-region="note-text"]')) {grow(event.target);}
        });
        center.addEventListener('change', function(event) {
            if (event.target.matches('[data-action="toggle-archive"]')) {
                var archiveInput = event.target, archiveCard = archiveInput.closest('[data-noteid]');
                var requested = archiveInput.checked, archived = false;
                run(archiveCard, function() {
                    var text = region(archiveCard, 'note-text'), state = stateFor(archiveCard);
                    // Completing an actively edited note commits its text first; failure preserves the draft/card.
                    var save = requested && state.editing && text && text.value !== state.baseline
                        ? call('edit_note', {noteid: Number(archiveCard.dataset.noteid), operation: 'content',
                            content: text.value, expectedcontent: state.baseline}).then(function(note) {state.baseline = note.content;})
                        : Promise.resolve();
                    return save.then(function() {return call('manage_archive', {
                        operation: requested ? 'archive' : 'restore', noteid: Number(archiveCard.dataset.noteid)});
                    }).then(function(response) {
                        archived = true; removeCard(archiveCard); announceArchive(response, requested ? 'archived' : 'restored');
                    }).catch(function(error) {archiveInput.checked = !requested; throw error;});
                }).then(function() {if (archived) {submitSearch(searchUrl());}});
                return;
            }
            if (event.target.matches('input[data-taskline]')) {
                var checkbox = event.target, taskCard = checkbox.closest('[data-noteid]'), checked = checkbox.checked;
                run(taskCard, function() {
                    return call('edit_note', {noteid: Number(taskCard.dataset.noteid), operation: 'task',
                        expectedcontent: stateFor(taskCard).baseline, taskline: Number(checkbox.dataset.taskline), checked: checked})
                        .then(function(note) {
                            stateFor(taskCard).baseline = note.content; region(taskCard, 'note-text').value = note.content;
                            region(taskCard, 'note-content').innerHTML = note.contenthtml; updateDate(taskCard, note);
                            var editor = Editor.get(region(taskCard, 'note-text'));
                            if (editor && editor.reset) {editor.reset(note.content, note.contentformat);}
                        }).catch(function(error) {checkbox.checked = !checked; throw error;});
                }); return;
            }
            if (!event.target.matches('[data-action="toggle-global"]')) {return;}
            var input = event.target, card = input.closest('[data-noteid]'), value = input.checked;
            run(card, function() {
                return ensureSaved(card).then(function(id) {return call('edit_note', {noteid: id, operation: 'global', isglobal: value});})
                    .then(function(note) {region(card, 'global-badge').hidden = !note.isglobal; updateDate(card, note);})
                    .catch(function(error) {input.checked = !value; throw error;});
            });
        });
        center.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && event.target.matches('[data-region="note-text"]')) {
                event.preventDefault(); saveText(event.target.closest('[data-noteid]'));
            }
        });
        center.addEventListener('paste', function(event) {
            if (!event.target.matches('[data-region="note-text"]') || !event.clipboardData) {return;}
            var files = Array.from(event.clipboardData.items).filter(function(item) {return item.type.indexOf('image/') === 0;})
                .map(function(item) {return item.getAsFile();}).filter(Boolean);
            if (!files.length) {return;}
            event.preventDefault(); var card = event.target.closest('[data-noteid]');
            run(card, function() {
                return ensureSaved(card).then(function(id) {
                    return files.reduce(function(chain, file) {
                        return chain.then(function() {return readImage(file).then(function(data) {
                            return call('upload_screenshot', {noteid: id, filename: file.name || 'screenshot.png',
                                mimetype: file.type, data: data});
                        }).then(function(image) {appendImage(card, image);});});
                    }, Promise.resolve());
                });
            }, 'uploading');
        });

        // Keep active editors and pending requests intact when live search replaces the other cards.
        var submitSearch = function(url) {
            if (activeRequest) {activeRequest.abort();}
            var request = new AbortController(); activeRequest = request; var generation = mutation;
            center.setAttribute('aria-busy', 'true');
            fetch(url, {credentials: 'same-origin', signal: request.signal, headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function(response) {
                    if (!response.ok) {throw new Error('Side Notes search failed (' + response.status + ').');}
                    return response.text();
                }).then(function(html) {
                    if (activeRequest !== request || generation !== mutation) {return;}
                    var next = new DOMParser().parseFromString(html, 'text/html');
                    var nextResults = next.querySelector('[data-region="sidenotes-results"]');
                    if (!nextResults) {throw new Error('Side Notes search returned an invalid response.');}
                    var pinned = Array.from(results.querySelectorAll('[data-noteid]')).filter(function(card) {
                        return stateFor(card).editing || stateFor(card).pending;
                    });
                    pinned.forEach(function(card) {
                        var duplicate = nextResults.querySelector('[data-noteid="' + Number(card.dataset.noteid) + '"]');
                        if (duplicate) {duplicate.parentElement.remove();}
                    });
                    var focused = document.activeElement;
                    var focusCard = focused && focused.closest('[data-noteid]');
                    var focusNoteid = focusCard ? Number(focusCard.dataset.noteid) : null;
                    var focusAction = focused && focused.dataset.action;
                    var focusRegion = focused && focused.dataset.region;
                    var selection = focused && typeof focused.selectionStart === 'number'
                        ? [focused.selectionStart, focused.selectionEnd] : null;
                    var wrapper = focused && focused.closest('[data-sidenotes-editor]');
                    var rich = wrapper ? Editor.get(wrapper._sidenotesText) : null;
                    var richSelection = rich && !rich.source && rich.editor
                        ? {from: rich.editor.state.selection.from, to: rich.editor.state.selection.to} : null;
                    var columns = pinned.map(function(card) {return card.parentElement;});
                    columns.forEach(function(column) {column.remove();});
                    var incoming = Array.from(nextResults.children);
                    results.replaceChildren.apply(results, columns.concat(incoming));
                    updateQuotes();
                    // Native Moodle/Bootstrap initialisation also applies to newly loaded search cards.
                    FilterEvents.notifyFilterContentUpdated(incoming);
                    ['sidenotes-pagination', 'sidenotes-exports', 'archive-navigation'].forEach(function(name) {
                        var current = center.querySelector('[data-region="' + name + '"]');
                        var updated = next.querySelector('[data-region="' + name + '"]');
                        if (updated && current) {current.innerHTML = updated.innerHTML; current.hidden = updated.hidden;}
                    });
                    var emptyArchive = center.querySelector('[data-action="empty-archive"]');
                    var nextEmpty = next.querySelector('[data-action="empty-archive"]');
                    if (emptyArchive && nextEmpty) {emptyArchive.disabled = nextEmpty.disabled;}
                    var focusTarget = focused && focused.isConnected ? focused : null;
                    if (!focusTarget && focusAction) {
                        var scope = focusNoteid !== null ? results.querySelector('[data-noteid="' + focusNoteid + '"]') : center;
                        if (scope) {focusTarget = Array.from(scope.querySelectorAll('[data-action]')).find(function(control) {
                            return control.dataset.action === focusAction;
                        });}
                    }
                    if (!focusTarget && focusRegion) {
                        focusTarget = Array.from(center.querySelectorAll('[data-region]')).find(function(node) {
                            return node.dataset.region === focusRegion;
                        });
                    }
                    if (focusTarget) {
                        focusTarget.focus({preventScroll: true});
                        if (selection && focusTarget === focused) {focusTarget.setSelectionRange(selection[0], selection[1]);}
                        if (richSelection) {rich.editor.chain().setTextSelection(richSelection).focus().run();}
                    }
                    window.history.replaceState({}, '', url);
                }).catch(function(error) {
                    // Never navigate on a fetch error: that would discard the local draft.
                    if (error.name !== 'AbortError') {Notification.exception(error);}
                }).finally(function() {
                    if (activeRequest === request) {center.removeAttribute('aria-busy'); activeRequest = null;}
                });
        };
        var searchUrl = function() {
            var url = new URL(form.action, window.location.href);
            new FormData(form).forEach(function(value, name) {
                if (String(value).trim() && String(value) !== '0') {url.searchParams.append(name, String(value).trim());}
            });
            updateFilterChips(url);
            return url.toString();
        };
        var updateFilterChips = function(url) {
            var chip = function(container, label, action, removed, input) {
                var link = document.createElement('a'); link.className = 'badge badge-light';
                link.href = removed.toString(); link.dataset.action = action;
                var caption = document.createElement('span'); caption.className = 'local-plugin-search-bar-chip-label';
                caption.textContent = label;
                var cross = document.createElement('span'); cross.className = 'local-plugin-search-bar-chip-remove';
                cross.textContent = '×'; cross.setAttribute('aria-hidden', 'true');
                if (input) {
                    link.dataset.filterTag = input.value;
                    link.style.setProperty('--search-bar-chip-bg', input.dataset.background || '#e9ecef');
                    link.style.setProperty('--search-bar-chip-fg', input.dataset.foreground || '#000000');
                }
                stringsReady.then(function() {link.setAttribute('aria-label', strings.removefilter.replace('__TAG__', label));});
                link.append(caption, cross); container.append(link);
            };
            var tagChips = form.querySelector('[data-region="filter-chips"]');
            if (tagChips) {
                tagChips.replaceChildren();
                var selected = Array.from(form.querySelectorAll('[name="tags[]"]')).filter(function(input) {
                    return input.checked || input.type === 'hidden';
                });
                selected.forEach(function(input) {
                    var removed = new URL(url); removed.searchParams.delete('tags[]');
                    selected.filter(function(other) {return other !== input;}).forEach(function(other) {
                        removed.searchParams.append('tags[]', other.value);
                    });
                    chip(tagChips, input.dataset.name, 'remove-filter-tag', removed, input);
                });
                var count = document.getElementById('tagfilter-count');
                count.textContent = selected.length ? count.dataset.selected.replace('{$a}', String(selected.length)) : count.dataset.alltags;
            }
            var course = document.getElementById('coursefilter'), courseChips = form.querySelector('[data-region="course-chips"]');
            courseChips.replaceChildren();
            if (course.value !== '0') {
                var removedCourse = new URL(url); removedCourse.searchParams.delete('coursefilter');
                chip(courseChips, course.selectedOptions[0].textContent, 'remove-filter-course', removedCourse);
            }
            var textChips = form.querySelector('[data-region="search-chips"]'); textChips.replaceChildren();
            if (search.value.trim()) {
                var removedSearch = new URL(url); removedSearch.searchParams.delete('searchterm');
                chip(textChips, search.value.trim(), 'remove-filter-search', removedSearch);
            }
            clear.hidden = !search.value.trim();
        };
        var scheduleSearch = function() {
            beginMutation();
            clear.hidden = !search.value.trim(); window.clearTimeout(searchTimer);
            searchUrl();
            if (!composing) {searchTimer = window.setTimeout(function() {submitSearch(searchUrl());}, 300);}
        };
        search.addEventListener('input', scheduleSearch);
        search.addEventListener('compositionstart', function() {composing = true; beginMutation(); window.clearTimeout(searchTimer);});
        search.addEventListener('compositionend', function() {composing = false; scheduleSearch();});
        clear.addEventListener('click', function() {
            beginMutation(); window.clearTimeout(searchTimer); search.value = ''; clear.hidden = true; submitSearch(searchUrl()); search.focus();
        });
        form.addEventListener('submit', function(event) {
            event.preventDefault(); if (composing) {return;} window.clearTimeout(searchTimer); submitSearch(searchUrl());
        });
        form.addEventListener('change', function(event) {
            if (!event.target.matches('select, input[type="checkbox"][name="tags[]"]')) {return;}
            beginMutation(); window.clearTimeout(searchTimer); submitSearch(searchUrl());
        });
        var tagMenu = document.getElementById('tagfilter');
        document.addEventListener('click', function(event) {
            if (tagMenu && !tagMenu.contains(event.target)) {tagMenu.open = false;}
        });
        if (tagMenu) {tagMenu.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {tagMenu.open = false; tagMenu.querySelector('summary').focus();}
        });}
        var updateQuotes = function() {
            center.querySelectorAll('.local-sidenotes-center__quote').forEach(function(quote) {
                var text = quote.querySelector('.local-sidenotes-center__quote-text');
                var button = quote.querySelector('[data-action="toggle-quote"]');
                if (!text || !button || !text.offsetWidth) {return;}
                var line = parseFloat(window.getComputedStyle(text).lineHeight);
                button.hidden = text.scrollHeight <= line * 3 + 1;
                quote.classList.toggle('local-sidenotes-center__quote--collapsible', !button.hidden);
                if (button.hidden) {
                    quote.classList.remove('local-sidenotes-center__quote--expanded');
                    button.setAttribute('aria-expanded', 'false'); button.textContent = button.dataset.more;
                }
            });
        };
        searchUrl(); updateQuotes();
        if (document.fonts) {document.fonts.ready.then(updateQuotes);}
        center.addEventListener('click', function(event) {
            var link = event.target.closest('[data-region="sidenotes-pagination"] a');
            if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) {event.preventDefault(); submitSearch(link.href);}
        });
        window.addEventListener('beforeunload', function(event) {
            var unsaved = Array.from(results.querySelectorAll('[data-noteid]')).some(function(card) {
                var state = stateFor(card);
                return state.pending || (state.editing && region(card, 'note-text').value !== state.baseline);
            }); if (unsaved) {event.preventDefault(); event.returnValue = '';}
        });
        window.addEventListener('resize', function() {
            results.querySelectorAll('[data-region="note-text"]').forEach(grow); updateQuotes();
        });
    }};
});
