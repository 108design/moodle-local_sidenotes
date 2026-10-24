/** Automatically advance the visible administrator workflow by one bounded POST at a time. */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.

export const init = () => {
    const form = document.querySelector('[data-sidenotes-move-advance]');
    if (!form || form.dataset.initialised) return;
    form.dataset.initialised = '1';
    const timer = window.setTimeout(() => {
        if (document.visibilityState === 'visible') form.requestSubmit();
    }, 1500);
    // A manual pause/stop/resume action wins over the next automatic batch.
    document.addEventListener('submit', () => window.clearTimeout(timer), {capture: true, once: true});
    document.addEventListener('visibilitychange', () => {
        window.clearTimeout(timer);
        if (document.visibilityState === 'visible') location.reload();
    });
};
