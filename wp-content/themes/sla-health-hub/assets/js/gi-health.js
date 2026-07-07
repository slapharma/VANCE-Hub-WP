/* Vance Health Hub — GI Health section interactions
   Vanilla JS, no dependencies. Respects prefers-reduced-motion. */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---- 1. Reveal-on-scroll ---- */
  function initReveal() {
    var items = document.querySelectorAll('.reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  /* ---- 2. Stat count-up ---- */
  function animateCount(el) {
    var target = parseFloat(el.getAttribute('data-count'));
    var decimals = (el.getAttribute('data-decimals') | 0);
    if (reduceMotion) { el.textContent = target.toFixed(decimals); return; }
    var dur = 1400, start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
      el.textContent = (target * eased).toFixed(decimals);
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = target.toFixed(decimals);
    }
    requestAnimationFrame(step);
  }

  function initCounters() {
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length) return;
    if (!('IntersectionObserver' in window)) {
      nums.forEach(animateCount); return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateCount(e.target); io.unobserve(e.target); }
      });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io.observe(el); });
  }

  /* ---- 3. Sidebar scrollspy (in-page "On this page") ---- */
  function initScrollSpy() {
    var links = document.querySelectorAll('.toc a[href^="#"]');
    if (!links.length) return;
    var map = {};
    links.forEach(function (a) {
      var id = a.getAttribute('href').slice(1);
      var sec = document.getElementById(id);
      if (sec) map[id] = a;
    });
    var sections = Object.keys(map).map(function (id) { return document.getElementById(id); });
    if (!('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (l) { l.classList.remove('active'); });
          if (map[e.target.id]) map[e.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
    sections.forEach(function (s) { io.observe(s); });
  }

  /* ---- 4. Mobile nav toggle (mirrors vancehealthhub.co.uk header.php) ---- */
  function initNav() {
    var btn = document.querySelector('.mobile-menu-toggle');
    var nav = document.querySelector('.main-nav');
    var actions = document.querySelector('.header-actions');
    if (!btn || !nav) return;

    function setOpen(open) {
      nav.classList.toggle('active', open);
      if (actions) actions.classList.toggle('active', open);
      btn.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.style.overflow = open ? 'hidden' : '';
    }

    btn.setAttribute('aria-expanded', 'false');
    btn.addEventListener('click', function () { setOpen(!nav.classList.contains('active')); });

    // Close drawer when any nav link is tapped.
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (nav.classList.contains('active')) setOpen(false);
      });
    });

    // Reset when crossing back to desktop width.
    var mq = window.matchMedia('(min-width: 768px)');
    function onResize(e) { if (e.matches && nav.classList.contains('active')) setOpen(false); }
    if (mq.addEventListener) mq.addEventListener('change', onResize);
    else if (mq.addListener) mq.addListener(onResize);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('active')) setOpen(false);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    initCounters();
    initScrollSpy();
    initNav();
  });
})();
