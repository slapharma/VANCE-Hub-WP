/**
 * Footer accordion (mobile density pass, phones only via main.css).
 *
 * Each .footer-col heading and the disclaimer toggle share the same pattern:
 * a real <button> with aria-expanded, toggling an --open modifier class that
 * main.css uses to expand/collapse the content below 768px. Above that width
 * the CSS never collapses anything, so this script is inert on desktop.
 */
(function () {
    'use strict';

    // Progressive enhancement: main.css only collapses a panel once its
    // container carries .js-ready, so a visitor with JS disabled (or a
    // request this script hasn't reached yet) always sees the full footer
    // open rather than stuck permanently collapsed.
    document.querySelectorAll('.footer-col, .footer-disclaimer').forEach(function (el) {
        el.classList.add('js-ready');
    });

    document.querySelectorAll('.footer-col-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var willOpen = btn.getAttribute('aria-expanded') !== 'true';
            btn.setAttribute('aria-expanded', String(willOpen));
            var col = btn.closest('.footer-col');
            if (col) { col.classList.toggle('is-open', willOpen); }
        });
    });

    var disclaimerToggle = document.querySelector('.footer-disclaimer-toggle');
    if (disclaimerToggle) {
        disclaimerToggle.addEventListener('click', function () {
            var willOpen = disclaimerToggle.getAttribute('aria-expanded') !== 'true';
            disclaimerToggle.setAttribute('aria-expanded', String(willOpen));
            disclaimerToggle.textContent = willOpen ? 'Show less' : 'Read full disclaimer';
            var wrap = disclaimerToggle.closest('.footer-disclaimer');
            if (wrap) { wrap.classList.toggle('is-open', willOpen); }
        });
    }
})();
