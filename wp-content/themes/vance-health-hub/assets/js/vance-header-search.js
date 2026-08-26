/*
 * Header search toggle.
 *
 * Expands/collapses the header's search field (header.php,
 * .vance-header-search) and keeps the collapsed state honest for keyboard and
 * screen-reader users: a 0-width input is invisible but still focusable, so
 * tabbing past the header would silently strand the caret inside it. The field
 * is therefore pulled out of the tab order while closed and put back on open.
 *
 * The open/closed look itself is CSS (.is-open on the wrapper) — this file only
 * owns the state, focus, and the outside-click / Escape dismissals.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var wrap = document.querySelector('[data-vance-search]');
        if (!wrap) return;

        var toggle = wrap.querySelector('.vance-header-search__toggle');
        var form   = wrap.querySelector('.vance-header-search__form');
        var field  = wrap.querySelector('.vance-header-search__field');

        if (!toggle || !form || !field) return;

        var actions    = wrap.closest ? wrap.closest('.header-actions') : wrap.parentNode;
        var labelOpen  = toggle.getAttribute('aria-label') || 'Open search';
        var labelClose = 'Close search';

        function setOpen(open, moveFocus) {
            wrap.classList.toggle('is-open', open);
            // The expanded field is 260px wide and lands on top of the social
            // icons / VANCE-Ai / My Dashboard. Fading those out (they keep their
            // space, so the header never reflows) is what stops the open field
            // from sitting across a half-covered button.
            if (actions) actions.classList.toggle('vance-search-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? labelClose : labelOpen);
            field.setAttribute('tabindex', open ? '0' : '-1');
            if (open && moveFocus) field.focus();
        }

        // Sync JS state to whatever the server rendered (header.php opens the
        // field on a search results page) WITHOUT stealing focus on page load.
        setOpen(wrap.classList.contains('is-open'), false);

        toggle.addEventListener('click', function () {
            setOpen(!wrap.classList.contains('is-open'), true);
        });

        // An empty query would send the visitor to the site root's full archive,
        // which reads as "search did nothing". Keep them in the field instead.
        form.addEventListener('submit', function (e) {
            if (!field.value.trim()) {
                e.preventDefault();
                field.focus();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (!wrap.classList.contains('is-open')) return;
            setOpen(false, false);
            toggle.focus();
        });

        // Click-away closes, but never while there is an unsubmitted query —
        // losing typed text to a stray click is worse than a header that stays
        // open a moment too long.
        document.addEventListener('click', function (e) {
            if (!wrap.classList.contains('is-open')) return;
            if (wrap.contains(e.target)) return;
            if (field.value.trim()) return;
            setOpen(false, false);
        });
    });
})();
