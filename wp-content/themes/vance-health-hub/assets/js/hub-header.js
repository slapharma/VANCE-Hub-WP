/*
 * VanceHubClub header: the phone menu.
 *
 * The menu button (.vhh-menu-btn) opens the navigation (#vhh-nav) as a panel
 * under the bar. The button keeps aria-expanded honest, Escape closes the panel
 * and puts focus back on the button, and so does a tap outside the header, a tap
 * on a link in the panel, and a resize to the desktop width.
 *
 * Without this script the header still works: the class vhh-js is added to <html>
 * by the small inline script in template-parts/header-hub.php, before the header
 * paints. The stylesheet hides the panel and shows the menu button only under that
 * class, so with no JavaScript the navigation stays visible and the button is
 * hidden. This file adds the class again as a safeguard.
 *
 * Search is not handled here: assets/js/vance-header-search.js owns it.
 */
(function () {
	'use strict';

	function init() {
		var header = document.querySelector('[data-vhh-header]');
		if (!header) { return; }

		var button = header.querySelector('.vhh-menu-btn');
		var panel = header.querySelector('#vhh-nav');
		if (!button || !panel) { return; }

		document.documentElement.classList.add('vhh-js');

		function isOpen() {
			return header.classList.contains('is-open');
		}

		function setOpen(open, returnFocus) {
			header.classList.toggle('is-open', open);
			button.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (!open && returnFocus) { button.focus(); }
		}

		setOpen(false, false);

		button.addEventListener('click', function () {
			setOpen(!isOpen(), false);
		});

		// A tap on a link or a button inside the panel closes it.
		panel.addEventListener('click', function (event) {
			var target = event.target;
			if (target && target.closest && target.closest('a, button') && isOpen()) {
				setOpen(false, false);
			}
		});

		document.addEventListener('keydown', function (event) {
			if ((event.key === 'Escape' || event.key === 'Esc') && isOpen()) {
				setOpen(false, true);
			}
		});

		document.addEventListener('click', function (event) {
			if (isOpen() && !header.contains(event.target)) {
				setOpen(false, false);
			}
		});

		// Past the phone breakpoint the panel is the desktop nav: reset the state.
		var query = window.matchMedia ? window.matchMedia('(min-width: 768px)') : null;
		if (query) {
			var reset = function (event) {
				if (event.matches && isOpen()) { setOpen(false, false); }
			};
			if (query.addEventListener) { query.addEventListener('change', reset); }
			else if (query.addListener) { query.addListener(reset); }
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
