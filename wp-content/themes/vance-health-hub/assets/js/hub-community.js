/**
 * VanceHubClub Community hub (inc/hub-community.php, inc/hub-community-view.php).
 *
 * Everyone: the space tabs swap the feed in place (initSpaceTabs).
 *
 * Members only: sends the ask, reply, report and name forms, and the
 * "Me too", "Helpful" and poll buttons, to admin-ajax with the page nonce.
 * Visitors get no handlers: their buttons are plain links to the join page.
 *
 * AJAX actions: vance_community_ask, vance_community_reply,
 * vance_community_metoo, vance_community_helpful, vance_community_vote,
 * vance_community_report, vance_community_profile. Nonce field: "nonce".
 *
 * The server builds every piece of HTML it returns (the poll block) and escapes
 * it there. Messages from the server are written with textContent.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-vcom]');
	if (!root) {
		return;
	}
	var config = {};
	try {
		config = JSON.parse(root.getAttribute('data-config') || '{}');
	} catch (e) {
		config = {};
	}

	initSpaceTabs();

	/**
	 * Space tabs, for everyone. Each tab is a plain ?space= link, so with no JS
	 * the browser loads the page and opens it at the top. Here a click fetches
	 * that same URL, takes its feed section and puts it in place of this one,
	 * so the reader keeps the scroll position. The URL changes with pushState,
	 * and Back/Forward load the feed of that URL the same way. If the fetch
	 * fails, the browser follows the link as normal.
	 */
	function initSpaceTabs() {
		var nav = root.querySelector('[data-vcom-spaces]');
		var feed = root.querySelector('[data-vcom-feed]');
		if (!nav || !feed || !window.fetch || !window.DOMParser || !window.history || !history.pushState) {
			return;
		}
		var track = nav.querySelector('.vcom-spaces__track') || nav;
		var links = nav.querySelectorAll('[data-vcom-space]');
		var status = document.createElement('span');
		status.className = 'vcom-sr';
		status.setAttribute('role', 'status');
		nav.appendChild(status);
		var shownUrl = window.location.href;
		var request = 0;
		var shownSlug = currentSlug();
		var syncedSpace = shownSlug;

		function linkFor(slug) {
			for (var i = 0; i < links.length; i++) {
				if (links[i].getAttribute('data-vcom-space') === slug) {
					return links[i];
				}
			}
			return null;
		}

		function currentSlug() {
			var cur = nav.querySelector('[aria-current="page"]');
			return cur ? cur.getAttribute('data-vcom-space') || '' : '';
		}

		function slugFromUrl(url) {
			var slug = '';
			try {
				slug = new URL(url, window.location.href).searchParams.get('space') || '';
			} catch (e) {
				slug = '';
			}
			// As sanitize_key() on the server. This is only the first guess while
			// the feed loads; apply() takes the real tab from the server's answer.
			slug = slug.toLowerCase().replace(/[^a-z0-9_-]/g, '');
			// An unknown slug shows all spaces on the server, so match that here.
			return linkFor(slug) ? slug : '';
		}

		function markCurrent(slug) {
			for (var i = 0; i < links.length; i++) {
				var on = links[i].getAttribute('data-vcom-space') === slug;
				links[i].classList.toggle('is-current', on);
				if (on) {
					links[i].setAttribute('aria-current', 'page');
				} else {
					links[i].removeAttribute('aria-current');
				}
			}
		}

		// Phones: the strip scrolls sideways. Bring the current tab into view
		// by moving the strip only, never the page.
		function revealCurrent() {
			var cur = nav.querySelector('[aria-current="page"]');
			if (!cur || track.scrollWidth <= track.clientWidth) {
				return;
			}
			var left = cur.offsetLeft;
			var right = left + cur.offsetWidth;
			if (left < track.scrollLeft || right > track.scrollLeft + track.clientWidth) {
				track.scrollLeft = Math.max(0, left - (track.clientWidth - cur.offsetWidth) / 2);
			}
		}

		// Keep the ask form's space in step with the feed, unless the reader
		// chose another space in it. "All spaces" sets it back to "Choose".
		function syncAskSpace(to) {
			var select = root.querySelector('#vcom-ask-space');
			if (select && select.value === syncedSpace && select.querySelector('option[value="' + to + '"]')) {
				select.value = to;
			}
			syncedSpace = to;
		}

		// A change of #hash alone also fires popstate: the feed is the same then.
		function noHash(u) {
			return String(u).split('#')[0];
		}

		// Stop any pending load and put the tabs back to what the feed shows.
		function settle() {
			request++;
			markCurrent(shownSlug);
			feed.removeAttribute('aria-busy');
		}

		// Put the fetched feed in place. Runs outside the fetch chain's error
		// handler, so a fault here never turns into a full reload.
		function apply(doc, fresh, url, push) {
			// Same-origin markup, built and escaped by vance_community_render_page().
			// The nodes move across as parsed (DOMParser does not run scripts).
			while (feed.firstChild) {
				feed.removeChild(feed.firstChild);
			}
			while (fresh.firstChild) {
				feed.appendChild(document.adoptNode(fresh.firstChild));
			}
			feed.removeAttribute('aria-busy');
			if (push) {
				history.pushState({ vcomSpace: true }, '', url);
			}
			shownUrl = window.location.href;
			// The server decides which space it showed: take the tab from its answer.
			var served = doc.querySelector('[data-vcom-spaces] [aria-current="page"]');
			shownSlug = served ? served.getAttribute('data-vcom-space') || '' : slugFromUrl(url);
			markCurrent(shownSlug);
			revealCurrent();
			syncAskSpace(shownSlug);
			var link = linkFor(shownSlug);
			status.textContent = link ? link.textContent : '';
		}

		function show(url, push) {
			var mine = ++request;
			markCurrent(slugFromUrl(url));
			revealCurrent();
			feed.setAttribute('aria-busy', 'true');
			fetch(url, { credentials: 'same-origin' }).then(function (res) {
				if (!res.ok) {
					throw new Error('HTTP ' + res.status);
				}
				return res.text();
			}).then(function (text) {
				if (mine !== request) {
					return null;
				}
				var doc = new DOMParser().parseFromString(text, 'text/html');
				var fresh = doc.querySelector('[data-vcom-feed]');
				if (!fresh) {
					throw new Error('no feed');
				}
				return { doc: doc, fresh: fresh };
			}).then(function (got) {
				if (got && mine === request) {
					apply(got.doc, got.fresh, url, push);
				}
			}, function () {
				// The fetch or the page failed: let the browser load the link.
				if (mine === request) {
					window.location.href = url;
				}
			});
		}

		revealCurrent();

		nav.addEventListener('click', function (ev) {
			var link = ev.target && ev.target.closest ? ev.target.closest('a[data-vcom-space]') : null;
			if (!link || ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
				return;
			}
			ev.preventDefault();
			if (link.href === noHash(shownUrl)) {
				// Already on screen: only cancel a load that is still running.
				settle();
				return;
			}
			show(link.href, true);
		});

		window.addEventListener('popstate', function () {
			if (noHash(window.location.href) === noHash(shownUrl)) {
				// Back to what is on screen: a pending click must not land after it.
				settle();
				return;
			}
			show(window.location.href, false);
		});
	}

	if (!config.member || !config.ajaxUrl || !config.nonce) {
		return;
	}
	var fallbackError = config.msgError || 'Something went wrong. Please try again.';

	var formActions = {
		ask: 'vance_community_ask',
		reply: 'vance_community_reply',
		report: 'vance_community_report',
		profile: 'vance_community_profile'
	};
	var toggleActions = {
		metoo: 'vance_community_metoo',
		helpful: 'vance_community_helpful'
	};

	/**
	 * POST to admin-ajax. Resolves with { ok, data } for any JSON answer.
	 */
	function send(action, body) {
		body.append('action', action);
		body.append('nonce', config.nonce);
		return fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (res) {
			return res.json().catch(function () {
				return { success: false, data: {} };
			});
		}).then(function (json) {
			var data = (json && json.data) || {};
			if (json && json.success) {
				return { ok: true, data: data };
			}
			if (data.code === 'login' && data.join) {
				window.location.href = data.join;
			}
			return { ok: false, data: data };
		}).catch(function () {
			return { ok: false, data: {} };
		});
	}

	function say(scope, text, isError) {
		var el = scope && scope.querySelector('[data-vcom-msg]');
		if (!el) {
			return;
		}
		el.textContent = text || '';
		el.classList.toggle('is-error', !!isError);
	}

	function setBusy(el, busy) {
		var buttons = el.querySelectorAll('button');
		for (var i = 0; i < buttons.length; i++) {
			buttons[i].disabled = busy;
		}
		el.setAttribute('aria-busy', busy ? 'true' : 'false');
	}

	// Forms: ask, reply, report, profile.
	root.addEventListener('submit', function (ev) {
		var form = ev.target;
		if (!form || !form.getAttribute) {
			return;
		}
		var kind = form.getAttribute('data-vcom-form');
		if (!kind || !formActions[kind]) {
			return;
		}
		ev.preventDefault();
		if (form.getAttribute('aria-busy') === 'true') {
			return;
		}
		setBusy(form, true);
		say(form, '', false);
		send(formActions[kind], new FormData(form)).then(function (res) {
			setBusy(form, false);
			var msg = (res.data && res.data.message) || (res.ok ? '' : fallbackError);
			if (!res.ok) {
				say(form, msg, true);
				return;
			}
			if (kind === 'report') {
				// The post is hidden now: replace it with the thank-you line.
				var item = form.closest('[data-vcom-item]');
				if (item) {
					var note = document.createElement('p');
					note.className = 'vcom-msg vcom-hidden-note';
					note.setAttribute('role', 'status');
					note.textContent = msg;
					item.innerHTML = '';
					item.appendChild(note);
					return;
				}
			}
			if (kind === 'profile' && res.data && res.data.name) {
				var names = root.querySelectorAll('[data-vcom-name]');
				for (var i = 0; i < names.length; i++) {
					names[i].textContent = res.data.name;
				}
			}
			if (kind === 'ask' || kind === 'reply') {
				form.reset();
			}
			say(form, msg, false);
		});
	});

	// Buttons: Me too, Helpful, poll answers.
	root.addEventListener('click', function (ev) {
		var target = ev.target && ev.target.closest ? ev.target : null;
		if (!target) {
			return;
		}

		var toggle = target.closest('[data-vcom-toggle]');
		if (toggle) {
			var what = toggle.getAttribute('data-vcom-toggle');
			if (!toggleActions[what] || toggle.disabled) {
				return;
			}
			ev.preventDefault();
			var body = new FormData();
			body.append('id', toggle.getAttribute('data-id') || '');
			toggle.disabled = true;
			send(toggleActions[what], body).then(function (res) {
				toggle.disabled = false;
				if (!res.ok) {
					return;
				}
				toggle.setAttribute('aria-pressed', res.data.active ? 'true' : 'false');
				var count = toggle.querySelector('[data-vcom-count]');
				if (count && typeof res.data.count === 'number') {
					count.textContent = String(res.data.count);
				}
			});
			return;
		}

		var vote = target.closest('[data-vcom-vote]');
		if (vote) {
			var poll = vote.closest('[data-vcom-poll]');
			if (!poll || poll.getAttribute('aria-busy') === 'true') {
				return;
			}
			ev.preventDefault();
			var data = new FormData();
			data.append('poll', poll.getAttribute('data-vcom-poll') || '');
			data.append('answer', vote.getAttribute('data-vcom-vote') || '');
			setBusy(poll, true);
			send('vance_community_vote', data).then(function (res) {
				if (res.ok && res.data && typeof res.data.html === 'string' && res.data.html) {
					// Markup built and escaped by vance_community_poll_html().
					poll.outerHTML = res.data.html;
					return;
				}
				setBusy(poll, false);
				say(poll, (res.data && res.data.message) || fallbackError, true);
			});
		}
	});
})();
