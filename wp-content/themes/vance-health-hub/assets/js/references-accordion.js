/* Vance Medical Hub — References accordion
   Progressive enhancement: turns the "References & further reading" block into
   an animated, accessible disclosure. Vanilla JS, no dependencies.

   Two contexts:
     1. GI condition pages — each .gi-references block.
     2. Single articles    — a "References…" heading inside .oped-article-body
                             (references are authored in the post body).

   The block is rendered fully visible by the server; we collapse it here, so
   the content stays crawlable and degrades gracefully without JS. Collapsed by
   default (per design decision); honours prefers-reduced-motion. */
(function () {
    'use strict';

    if (window.__vanceRefsInit) { return; }
    window.__vanceRefsInit = true;

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var uid = 0;

    var ICON_SVG =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>' +
        '<path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>';

    var CHEV_SVG =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<polyline points="6 9 12 15 18 9"></polyline></svg>';

    /* ---- animation helpers ---- */
    function clearTransitionEnd(panel) {
        if (panel._vte) {
            panel.removeEventListener('transitionend', panel._vte);
            panel._vte = null;
        }
    }

    function openPanel(panel, btn, instant) {
        clearTransitionEnd(panel);
        panel.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        if (instant || reduceMotion) { panel.style.height = 'auto'; return; }
        var target = panel.scrollHeight;
        panel.style.height = '0px';
        panel.getBoundingClientRect(); // force reflow so the next set animates
        panel.style.height = target + 'px';
        panel._vte = function (e) {
            if (e.propertyName !== 'height') { return; }
            panel.style.height = 'auto'; // let it reflow responsively once open
            clearTransitionEnd(panel);
        };
        panel.addEventListener('transitionend', panel._vte);
    }

    function closePanel(panel, btn, instant) {
        clearTransitionEnd(panel);
        btn.setAttribute('aria-expanded', 'false');
        if (instant || reduceMotion) { panel.style.height = '0px'; panel.hidden = true; return; }
        panel.style.height = panel.scrollHeight + 'px';
        panel.getBoundingClientRect();
        panel.style.height = '0px';
        panel._vte = function (e) {
            if (e.propertyName !== 'height') { return; }
            panel.hidden = true; // drop from tab order / a11y tree once closed
            clearTransitionEnd(panel);
        };
        panel.addEventListener('transitionend', panel._vte);
    }

    /* Merge runs of adjacent <ol> siblings into the first one. References are
       often authored as several single-item ordered lists, each of which
       restarts at 1 — so every citation renders as "1.". Folding the <li>s into
       one <ol> restores continuous 1..n numbering. "Adjacent" ignores whitespace
       but stops at any real element, so lists genuinely separated by a heading
       or paragraph are left alone. */
    function mergeAdjacentOrderedLists(root) {
        var el = root.firstElementChild;
        while (el) {
            if (el.tagName === 'OL') {
                var sib = el.nextElementSibling;
                while (sib && sib.tagName === 'OL') {
                    while (sib.firstElementChild) { el.appendChild(sib.firstElementChild); }
                    var dead = sib;
                    sib = sib.nextElementSibling;
                    dead.parentNode.removeChild(dead);
                }
            }
            el = el.nextElementSibling;
        }
    }

    /* ---- build the disclosure ----
       container : element that will wrap the trigger + panel
       heading   : element whose text becomes the trigger label (then removed)
       nodes     : the content nodes moved into the collapsible panel */
    function buildAccordion(container, heading, nodes) {
        if (!heading || !nodes.length || container.getAttribute('data-vref') === '1') { return; }
        container.setAttribute('data-vref', '1');
        container.classList.add('vref');

        var id = 'vref-panel-' + (++uid);
        var title = (heading.textContent || 'References').trim();

        var liCount = 0;
        nodes.forEach(function (n) {
            if (n.nodeType === 1 && n.querySelectorAll) {
                liCount += n.querySelectorAll('li').length;
            }
        });

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'vref__toggle';
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', id);
        btn.innerHTML =
            '<span class="vref__ic" aria-hidden="true">' + ICON_SVG + '</span>' +
            '<span class="vref__title"></span>' +
            (liCount ? '<span class="vref__count">' + liCount + '</span>' : '') +
            '<span class="vref__chev" aria-hidden="true">' + CHEV_SVG + '</span>';
        btn.querySelector('.vref__title').textContent = title;

        var panel = document.createElement('div');
        panel.className = 'vref__panel';
        panel.id = id;
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-label', title);
        panel.hidden = true;

        var inner = document.createElement('div');
        inner.className = 'vref__inner';
        nodes.forEach(function (n) { inner.appendChild(n); }); // moves nodes out of the DOM
        mergeAdjacentOrderedLists(inner); // fix references split into separate single-item <ol> (all "1.")
        panel.appendChild(inner);

        container.appendChild(btn);
        container.appendChild(panel);
        if (heading.parentNode) { heading.parentNode.removeChild(heading); }

        btn.addEventListener('click', function () {
            if (btn.getAttribute('aria-expanded') === 'true') { closePanel(panel, btn, false); }
            else { openPanel(panel, btn, false); }
        });

        closePanel(panel, btn, true); // collapsed by default
    }

    /* ---- 1. GI condition pages ---- */
    function enhanceGiReferences() {
        var blocks = document.querySelectorAll('.gi-references');
        Array.prototype.forEach.call(blocks, function (block) {
            var heading = block.querySelector('h2, h3, h4');
            if (!heading) { return; }
            var nodes = [];
            Array.prototype.forEach.call(block.childNodes, function (n) {
                if (n !== heading) { nodes.push(n); }
            });
            buildAccordion(block, heading, nodes);
        });
    }

    /* ---- 2. Single articles (references live in the post body) ---- */
    var HEADING_RE = /^\s*(references|sources|citations|further\s+reading)\b/i;

    function enhanceArticleReferences() {
        var body = document.querySelector('.oped-article-body');
        if (!body) { return; }

        var headings = body.querySelectorAll('h2, h3, h4');
        var heading = null;
        for (var i = 0; i < headings.length; i++) {
            if (HEADING_RE.test(headings[i].textContent || '')) { heading = headings[i]; break; }
        }
        if (!heading) { return; }

        // Collect only the citation content after the heading: ordered/unordered
        // lists and paragraphs. Stop at the first element that isn't one of those
        // — the next section heading, a Jetpack/share <div>, a figure, etc. — so
        // unrelated end-of-article widgets are never swallowed into the panel.
        var ALLOWED = { OL: 1, UL: 1, P: 1 };
        var nodes = [];
        var n = heading.nextSibling;
        while (n) {
            if (n.nodeType === 1 && !ALLOWED[n.tagName]) { break; }
            var next = n.nextSibling;
            nodes.push(n);
            n = next;
        }
        // Drop trailing whitespace-only text nodes so the panel has no stray gap.
        while (nodes.length && nodes[nodes.length - 1].nodeType === 3 &&
               !/\S/.test(nodes[nodes.length - 1].textContent)) {
            nodes.pop();
        }
        if (!nodes.length) { return; }

        var wrap = document.createElement('div');
        body.insertBefore(wrap, heading);
        buildAccordion(wrap, heading, nodes);
    }

    function init() {
        enhanceGiReferences();
        enhanceArticleReferences();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
