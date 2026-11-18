/**
 * Hub home page (front-page-hub.php): small progressive enhancements.
 *
 * 1. Ask VANCE-Ai form and example chips. With the site-wide VANCE-Ai chat
 *    loaded (assets/js/vance-askai.js exposes window.VanceAskAI), open the chat
 *    and ask the question in place. Without it, the form and the chips are
 *    ordinary links to the VANCE-Ai page. The /ask-ai/ page takes no query
 *    parameter, so the question is never put in a URL.
 *
 * 2. [data-vhub-join] links. Open the theme's existing join modal
 *    (openGuestModal() in footer.php). Without it, the link goes to the
 *    sign-up page.
 */
(function () {
	'use strict';

	function askVance(question) {
		var api = window.VanceAskAI;
		if (!api || typeof api.open !== 'function') {
			return false;
		}
		api.open();
		question = String(question || '').trim();
		if (question && typeof api.ask === 'function') {
			api.ask(question);
		}
		return true;
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.hasAttribute || !form.hasAttribute('data-vhub-ask-form')) {
			return;
		}
		var input = form.querySelector('input[type="text"]');
		if (askVance(input ? input.value : '')) {
			event.preventDefault();
			if (input) {
				input.value = '';
			}
		}
	});

	document.addEventListener('click', function (event) {
		if (!event.target || !event.target.closest) {
			return;
		}
		var chip = event.target.closest('[data-vhub-ask]');
		if (chip) {
			if (askVance(chip.getAttribute('data-vhub-ask'))) {
				event.preventDefault();
			}
			return;
		}
		var join = event.target.closest('[data-vhub-join]');
		if (join && typeof window.openGuestModal === 'function' && document.getElementById('guest-save-modal')) {
			event.preventDefault();
			window.openGuestModal();
		}
	});
})();
