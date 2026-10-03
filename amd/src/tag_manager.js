// This file is part of Moodle - https://moodle.org/
// GNU GPL v3 or later.
/** @module local_sidenotes/tag_manager
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str', 'core/templates'], function(Ajax, Notification, Str, Templates) {
    return {init: function() {
        var root = document.querySelector('.local-sidenotes-tagmanager');
        if (!root) {return;}
        var pending = false, status = root.querySelector('[data-region="tag-manager-status"]');
        var strings = Promise.resolve(Str.get_strings([{key: 'confirm', component: 'core'}, {key: 'delete', component: 'core'},
            {key: 'cancel', component: 'core'}, {key: 'tags:deleteconfirm', component: 'local_sidenotes'},
            {key: 'note:saving', component: 'local_sidenotes'}, {key: 'note:saved', component: 'local_sidenotes'}]));
        var contrast = function(hex) {
            var values = [1, 3, 5].map(function(offset) {
                var c = parseInt(hex.slice(offset, offset + 2), 16) / 255;
                return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
            });
            var luma = .2126 * values[0] + .7152 * values[1] + .0722 * values[2];
            return (luma + .05) / .05 >= 1.05 / (luma + .05) ? '#000000' : '#ffffff';
        };
        var save = function(row, operation) {
            if (pending) {return;}
            var drafts = Array.from(root.querySelectorAll('[data-tagid]')).filter(function(other) {return other !== row;})
                .map(function(other) {
                    return {id: other.dataset.tagid, name: other.querySelector('[data-region="tag-name"]').value,
                        colour: other.querySelector('[data-region="tag-colour"]').value,
                        automatic: other.querySelector('[data-region="tag-auto"]').checked};
                });
            pending = true; root.setAttribute('aria-busy', 'true');
            var controls = Array.from(root.querySelectorAll('button,input'));
            controls.forEach(function(control) {control.dataset.wasDisabled = String(control.disabled); control.disabled = true;});
            return strings.then(function(labels) {
                status.classList.remove('text-danger'); status.textContent = labels[4];
                return Ajax.call([{methodname: 'local_sidenotes_manage_tags', args: {
                    operation: operation, tagid: Number(row.dataset.tagid),
                    name: row.querySelector('[data-region="tag-name"]').value,
                    colour: row.querySelector('[data-region="tag-auto"]').checked ? ''
                        : row.querySelector('[data-region="tag-colour"]').value
                }}])[0].then(function(result) {return Templates.renderForPromise('local_sidenotes/tag_manager_list', result);})
                    .then(function(result) {
                        root.querySelector('[data-region="tag-manager-list"]').innerHTML = result.html;
                        Templates.runTemplateJS(result.js); status.textContent = labels[5];
                        drafts.forEach(function(draft) {
                            var other = root.querySelector('[data-tagid="' + Number(draft.id) + '"]');
                            if (!other) {return;}
                            other.querySelector('[data-region="tag-name"]').value = draft.name;
                            other.querySelector('[data-region="tag-colour"]').value = draft.colour;
                            other.querySelector('[data-region="tag-auto"]').checked = draft.automatic;
                            other.querySelector('[data-region="tag-colour"]').dispatchEvent(new Event('input', {bubbles: true}));
                        });
                        var focus = root.querySelector('[data-tagid="' + Number(row.dataset.tagid) + '"] input')
                            || root.querySelector('input, a');
                        if (focus) {focus.focus();}
                    });
            }).catch(function(error) {status.classList.add('text-danger'); status.textContent = error.message;})
                .finally(function() {
                    pending = false; root.setAttribute('aria-busy', 'false');
                    controls.forEach(function(control) {control.disabled = control.dataset.wasDisabled === 'true';});
                });
        };
        root.addEventListener('input', function(event) {
            var row = event.target.closest('[data-tagid]'); if (!row) {return;}
            var colour = row.querySelector('[data-region="tag-colour"]');
            var auto = row.querySelector('[data-region="tag-auto"]');
            colour.disabled = auto.checked;
            var value = auto.checked ? colour.dataset.automatic : colour.value;
            var preview = row.querySelector('[data-region="tag-preview"]');
            preview.textContent = row.querySelector('[data-region="tag-name"]').value;
            preview.style.setProperty('--sidenotes-tag-bg', value);
            preview.style.setProperty('--sidenotes-tag-fg', contrast(value));
        });
        root.addEventListener('submit', function(event) {
            var row = event.target.closest('[data-tagid]'); if (!row) {return;}
            event.preventDefault(); save(row, 'update');
        });
        root.addEventListener('click', function(event) {
            var button = event.target.closest('[data-action="delete-tag"]'); if (!button || button.disabled) {return;}
            strings.then(function(labels) {
                Notification.confirm(labels[0], labels[3], labels[1], labels[2], function() {save(button.closest('[data-tagid]'), 'delete');});
            });
        });
        window.addEventListener('beforeunload', function(event) {
            var dirty = Array.from(root.querySelectorAll('[data-tagid]')).some(function(row) {
                var name = row.querySelector('[data-region="tag-name"]'), auto = row.querySelector('[data-region="tag-auto"]');
                var colour = row.querySelector('[data-region="tag-colour"]');
                return name.value !== name.defaultValue || auto.checked !== auto.defaultChecked
                    || (!auto.checked && colour.value !== colour.defaultValue);
            });
            if (pending || dirty) {event.preventDefault(); event.returnValue = '';}
        });
    }};
});
