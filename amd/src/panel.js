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
 * Nonmodal notes panel; retain editor DOM while another tool owns the right region.
 * @module local_sidenotes/panel
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {publish, subscribe} from 'core/pubsub';
import DrawerEvents from 'core/drawer_events';
import Drawers from 'theme_boost/drawers';
import MessageEvents from 'core_message/message_drawer_events';

/** The messaging drawer retains its native DOM and unsent text when another tool opens. */
let messageHandover = Promise.resolve();
export const hideMessageDrawer = () => {
    if (!document.querySelector('[data-region="right-hand-drawer"]:not(.hidden)')) return messageHandover;
    const page = document.getElementById('page');
    // In supported Core versions, the message module releases its focus lock, restores its
    // opener, hides the backdrop and finally sets #page overflow to visible. Observe that
    // completion instead of racing its legacy deferred callbacks with an arbitrary delay.
    messageHandover = new Promise(resolve => {
        let timeout;
        const observer = new MutationObserver(() => {
            if (page?.style.overflow === 'visible') done();
        });
        const done = () => {observer.disconnect(); window.clearTimeout(timeout); resolve();};
        if (page) observer.observe(page, {attributes: true, attributeFilter: ['style']});
        timeout = window.setTimeout(done, 2000);
        publish(MessageEvents.HIDE, {});
        if (!page) done();
    });
    return messageHandover;
};

/** On mobile a left-hand native drawer occupies the same usable screen space. */
export const nativeDrawerRequested = event => {
    const toggle = event.target.closest('[data-toggler="drawers"]');
    const target = toggle && document.getElementById(toggle.dataset.target);
    return target && (target.classList.contains('drawer-right') || window.matchMedia('(max-width: 991.98px)').matches);
};

/** Bind only the notes instance and the existing contextual-help/drawer surfaces. */
export const create = (panel, onHide) => {
    const native = Boolean(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED && DrawerEvents.DRAWER_EXCLUSIVE_RELEASED);
    let ownsSpace = false;
    let focusReady = Promise.resolve();
    const helpPanels = () => [...document.querySelectorAll('[id$="-context-help"], [id$="-setup-help"]')]
        .filter(node => !node.hidden && node.getAttribute('aria-hidden') !== 'true');
    const position = () => {
        const nav = document.querySelector('.navbar.fixed-top');
        panel.style.setProperty('--local-sidenotes-panel-top', `${nav ? Math.max(0, nav.getBoundingClientRect().bottom) + 12 : 12}px`);
    };
    const release = (restore = true) => {
        const owned = ownsSpace;
        ownsSpace = false;
        document.body.classList.remove('local-sidenotes-panel-open', 'local-sidenotes-panel-legacy');
        if (owned && native && restore) publish(DrawerEvents.DRAWER_EXCLUSIVE_RELEASED, {region: 'right'});
    };
    const hide = (restore = true) => {
        if (!ownsSpace) return;
        release(restore);
        onHide();
    };
    // Release before help requests space. Releasing from its request subscriber would release
    // the new owner; repeated native requests would also lose Core's saved drawer list.
    document.addEventListener('click', event => {
        if (nativeDrawerRequested(event)) {hide(false); return;}
        const trigger = event.target.closest('a, button');
        if (!trigger || panel.contains(trigger)) return;
        const opensHelp = [...trigger.attributes].some(attr => /help-open$/.test(attr.name))
            || (trigger.tagName === 'A' && /[?&](?:[a-z]*help)=/.test(trigger.getAttribute('href') || ''));
        if (opensHelp) hide();
    }, true);
    document.addEventListener(Drawers.eventTypes.drawerShow, event => {
        if (event.target.classList.contains('drawer-right') || window.matchMedia('(max-width: 991.98px)').matches) hide(false);
    });
    subscribe(DrawerEvents.DRAWER_SHOWN, drawer => {
        if (drawer?.[0]?.matches('[data-region="right-hand-drawer"]')) hide(false);
    });
    if (native) subscribe(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, ({region} = {}) => {
        if (region === 'right') hide(false);
    });
    // Legacy help providers have no exclusive API. Their existing close action removes their
    // own margins; observing visibility also covers topic links and multiple providers.
    new MutationObserver(() => {
        if (ownsSpace && helpPanels().length) hide();
    }).observe(document.body, {subtree: true, attributes: true, attributeFilter: ['hidden', 'aria-hidden']});
    window.addEventListener('resize', position);
    panel.hidden = true;
    return {
        show: () => {
            if (ownsSpace) return focusReady;
            helpPanels().forEach(help => {
                const close = [...help.querySelectorAll('button')].find(button =>
                    [...button.attributes].some(attr => /help-close$/.test(attr.name)));
                close?.click();
            });
            position();
            focusReady = hideMessageDrawer();
            if (native) publish(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, {region: 'right'});
            ownsSpace = true;
            document.body.classList.add('local-sidenotes-panel-open');
            document.body.classList.toggle('local-sidenotes-panel-legacy', !native);
            return focusReady;
        },
        release,
    };
};
