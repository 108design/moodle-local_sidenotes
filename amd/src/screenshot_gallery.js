// SPDX-License-Identifier: GPL-3.0-or-later; 108design, 2026.
// Independent private-note viewer; PhotoSwipe is loaded locally on first use.
define(['core/str', 'core/config'], function(Str, Config) {
    var roots = new WeakSet(), pending, active, opening = false, escapePressed = false;

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && active) {escapePressed = true;}
    }, true);

    var load = function() {
        return pending || (pending = Promise.all([
            new Promise(function(resolve, reject) {
                require(['local_sidenotes/photoswipe_core', 'local_sidenotes/photoswipe_lightbox'],
                    function(core, lightbox) {resolve({core: core, lightbox: lightbox});}, reject);
            }),
            Str.get_strings(['image:close', 'image:previous', 'image:next', 'image:zoom', 'image:error',
                'image:gallery', 'image:thumbnail'].map(function(key) {return {key: key, component: 'local_sidenotes'};})),
            loadStyles()
        ]).catch(function(error) {pending = null; throw error;}));
    };

    var cssReady;
    var loadStyles = function() {
        return cssReady || (cssReady = new Promise(function(resolve, reject) {
            var stylesheet = document.createElement('link'); stylesheet.rel = 'stylesheet';
            stylesheet.href = Config.wwwroot + '/local/sidenotes/thirdparty/photoswipe/photoswipe.css';
            stylesheet.onload = resolve;
            stylesheet.onerror = function() {stylesheet.remove(); cssReady = null; reject(new Error('Viewer styles unavailable.'));};
            document.head.append(stylesheet);
        }));
    };

    var measure = function(link) {
        var preview = link.querySelector('img');
        var dimensions = function(image) {
            return {src: link.href, msrc: preview.src, width: image.naturalWidth, height: image.naturalHeight,
                alt: preview.alt, element: link};
        };
        if (preview.naturalWidth && preview.naturalHeight) {return Promise.resolve(dimensions(preview));}
        return new Promise(function(resolve) {
            var image = new Image();
            var timer = window.setTimeout(function() {finish(null);}, 15000);
            var finish = function(item) {
                window.clearTimeout(timer); image.onload = null; image.onerror = null; resolve(item);
            };
            image.onload = function() {finish(image.naturalWidth ? dimensions(image) : null);};
            image.onerror = function() {finish(null);};
            image.src = link.href;
        });
    };

    /** Focusable thumbnails and filenames, matching the standalone Support Desk viewer. */
    var addThumbnails = function(viewer, host, items, labels) {
        var caption = document.createElement('p');
        caption.className = 'sidenotes-lightbox-caption'; caption.setAttribute('aria-live', 'polite');
        var strip = document.createElement('div');
        strip.className = 'sidenotes-lightbox-strip'; strip.setAttribute('role', 'group');
        strip.setAttribute('aria-label', labels.gallery);
        var buttons = items.map(function(item, index) {
            var button = document.createElement('button'); button.type = 'button';
            button.setAttribute('aria-label', labels.thumbnail.replace('{$a}', String(index + 1)) + ': ' + item.alt);
            var image = document.createElement('img');
            image.src = item.msrc; image.alt = ''; image.loading = 'lazy'; image.draggable = false;
            button.append(image);
            button.addEventListener('click', function() {viewer.goTo(index);});
            button.addEventListener('keydown', function(event) {
                var target = {ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: items.length - 1}[event.key];
                if (target === undefined) {return;}
                event.preventDefault(); event.stopPropagation();
                var next = Math.max(0, Math.min(items.length - 1, target));
                viewer.goTo(next); buttons[next].focus();
            });
            strip.append(button); return button;
        });
        host.append(caption, strip);
        host.addEventListener('pointerdown', function(event) {event.stopPropagation();});
        var refresh = function() {
            caption.textContent = items[viewer.currIndex].alt;
            buttons.forEach(function(button, index) {button.setAttribute('aria-current', String(index === viewer.currIndex));});
            var current = buttons[viewer.currIndex];
            strip.scrollLeft = current.offsetLeft - strip.offsetLeft - (strip.clientWidth - current.offsetWidth) / 2;
        };
        viewer.on('change', refresh); refresh();
    };

    return {
        // Closing the lightbox must not also close the drawer, even when Escape is held down.
        consumeEscape: function(event) {
            if (event.key !== 'Escape') {return false;}
            var consumed = !!active || escapePressed; escapePressed = false; return consumed;
        },
        init: function(root) {
            if (!root || roots.has(root)) {return;}
            roots.add(root);
            // Delegation covers newly pasted images and replacement search/archive results.
            root.addEventListener('click', function(event) {
                var link = event.target.closest('a[data-sidenotes-image]');
                if (!link || !root.contains(link) || event.defaultPrevented || event.button !== 0
                        || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {return;}
                event.preventDefault();
                if (opening || active) {return;}
                var group = link.closest('[data-region="screenshots"], [data-region="note-screenshots"]');
                if (!group) {return;}
                opening = true;
                Promise.all([load(), Promise.all(Array.from(group.querySelectorAll('a[data-sidenotes-image]')).map(measure))])
                    .then(function(result) {
                        if (!link.isConnected) {return;}
                        var vendor = result[0][0], labels = result[0][1], items = result[1].filter(Boolean);
                        var index = items.findIndex(function(item) {return item.element === link;});
                        if (index < 0) {throw new Error('Screenshot is unavailable.');}
                        var lightbox = new vendor.lightbox.default({dataSource: items, pswpModule: vendor.core.default,
                            mainClass: 'pswp--sidenotes', closeTitle: labels[0], arrowPrevTitle: labels[1],
                            arrowNextTitle: labels[2], zoomTitle: labels[3], errorMsg: labels[4], returnFocus: true,
                            paddingFn: function(size) {return {top: 48, bottom: size.x < 600 ? 140 : 126, left: 12, right: 12};}});
                        lightbox.on('uiRegister', function() {
                            lightbox.pswp.ui.registerElement({name: 'note-images', appendTo: 'root',
                                className: 'sidenotes-lightbox-footer',
                                onInit: function(host) {addThumbnails(lightbox.pswp, host, items,
                                    {gallery: labels[5], thumbnail: labels[6]});}});
                        });
                        lightbox.on('destroy', function() {if (active === lightbox) {active = null;}});
                        lightbox.init(); link.focus({preventScroll: true}); active = lightbox;
                        lightbox.loadAndOpen(index);
                    }).catch(function() {
                        if (active) {active.destroy(); active = null;}
                        if (link.isConnected) {window.location.assign(link.href);}
                    }).finally(function() {opening = false;});
            });
        }
    };
});
