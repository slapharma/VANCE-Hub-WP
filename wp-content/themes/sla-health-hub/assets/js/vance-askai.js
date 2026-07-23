/**
 * Ask AI — shared chat controller.
 *
 * One engine drives every chat surface on the site:
 *   - the site-wide modal   (opened by any [data-vance-askai-open] element)
 *   - the inline mount      (#vance-askai-inline on the /ask-ai/ page)
 *   - highlight-to-ask      (select text in an article, tap the pill)
 *
 * Conversation state is shared across surfaces and kept in sessionStorage, so a
 * reader can start a question on one article and carry it to the next. For
 * logged-in users the server auto-saves each exchange against the conversation
 * id, which is what Dashboard → My AI Chats lists.
 *
 * Config comes from wp_localize_script as window.vanceAskAi.
 */
(function () {
	'use strict';

	var CFG = window.vanceAskAi || {};
	if (!CFG.endpoint) {
		return;
	}

	var STORE_KEY = 'vanceAskAiConversation';
	var STORE_TTL = 6 * 60 * 60 * 1000; // 6 hours
	var MAX_TURNS = 40;
	var MIN_SELECTION = 12;
	var MAX_SELECTION = 600;

	// Inline SVG (Lucide-style) — never emoji.
	var SVG_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
	var ICON = {
		chat: SVG_OPEN + '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
		spark: SVG_OPEN + '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/></svg>',
		close: SVG_OPEN + '<path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>',
		fresh: SVG_OPEN + '<path d="M12 5v14"/><path d="M5 12h14"/></svg>',
		send: SVG_OPEN + '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>'
	};

	var state = {
		id: null,
		messages: [],
		pending: false,
		error: ''
	};

	var surfaces = [];
	var modalEl = null;
	var modalSurface = null;
	var lastFocused = null;
	var uid = 0;

	// =====================================================================
	// Persistence
	// =====================================================================

	function newConversationId() {
		var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		var out = '';
		for (var i = 0; i < 24; i++) {
			out += chars.charAt(Math.floor(Math.random() * chars.length));
		}
		return out;
	}

	function loadState() {
		try {
			var raw = window.sessionStorage.getItem(STORE_KEY);
			if (raw) {
				var data = JSON.parse(raw);
				if (data && data.id && Array.isArray(data.messages) && (Date.now() - data.ts) < STORE_TTL) {
					state.id = data.id;
					state.messages = data.messages;
					return;
				}
			}
		} catch (e) {
			// Private browsing or storage disabled — fall through to a fresh id.
		}
		state.id = newConversationId();
	}

	function persistState() {
		try {
			window.sessionStorage.setItem(STORE_KEY, JSON.stringify({
				id: state.id,
				messages: state.messages,
				ts: Date.now()
			}));
		} catch (e) {}
	}

	function resetConversation() {
		state.id = newConversationId();
		state.messages = [];
		state.error = '';
		persistState();
		render();
		focusComposer();
	}

	// =====================================================================
	// Formatting — escape first, then decorate. Never trust model output.
	// =====================================================================

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function formatReply(raw) {
		var text = escapeHtml(raw);

		// Citations: "Read more: <title> — <url>" becomes a titled link.
		text = text.replace(
			/^[ \t]*Read more:[ \t]*(.+?)[ \t]*[—–-][ \t]*(https?:\/\/[^\s<]+?)[ \t]*$/gim,
			'<a class="vance-askai__cite" href="$2" target="_blank" rel="noopener">$1</a>'
		);

		text = text.replace(/^[ \t]*#{1,6}[ \t]*(.+)$/gm, '<strong>$1</strong>');
		text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		text = text.replace(/^[ \t]*[-*][ \t]+(.+)$/gm, '• $1');

		// Any remaining bare URL.
		text = text.replace(
			/(^|[\s(])(https?:\/\/[^\s<]+[^\s<.,;:)\]])/g,
			'$1<a href="$2" target="_blank" rel="noopener">$2</a>'
		);

		return text.replace(/\n/g, '<br>');
	}

	// =====================================================================
	// Networking
	// =====================================================================

	/**
	 * POST a turn. A cached page can carry a stale REST nonce; when that happens
	 * WordPress answers 403, so retry once anonymously — the chat still works,
	 * it just cannot be attributed to the account for saving.
	 */
	function request(payload, skipNonce) {
		var headers = { 'Content-Type': 'application/json' };
		if (CFG.nonce && !skipNonce) {
			headers['X-WP-Nonce'] = CFG.nonce;
		}

		return fetch(CFG.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: headers,
			body: JSON.stringify(payload)
		}).then(function (response) {
			return response.json().catch(function () {
				return null;
			}).then(function (data) {
				if (response.ok) {
					return data;
				}
				if (403 === response.status && !skipNonce) {
					return request(payload, true);
				}
				throw new Error(
					(data && data.message) ? data.message : (CFG.i18n && CFG.i18n.failed) || 'The assistant is unavailable right now. Please try again shortly.'
				);
			});
		});
	}

	function ask(question) {
		question = String(question || '').trim();
		if (!question || state.pending) {
			return;
		}

		state.messages.push({ role: 'user', content: question });
		if (state.messages.length > MAX_TURNS) {
			state.messages = state.messages.slice(-MAX_TURNS);
		}
		state.error = '';
		state.pending = true;
		persistState();
		render();

		request({
			messages: state.messages,
			conversation_id: state.id,
			context_post_id: CFG.postId || 0
		}).then(function (data) {
			state.pending = false;
			var reply = data && (data.reply || data.answer);
			if (reply) {
				state.messages.push({ role: 'assistant', content: reply });
			} else {
				state.error = (CFG.i18n && CFG.i18n.empty) || 'No answer came back. Please try again.';
			}
			persistState();
			render();
			focusComposer();
		}).catch(function (error) {
			state.pending = false;
			state.error = error && error.message ? error.message : 'That request failed. Please try again.';
			render();
			focusComposer();
		});
	}

	// =====================================================================
	// Surfaces
	// =====================================================================

	function autoGrow(input) {
		input.style.height = 'auto';
		input.style.height = Math.min(input.scrollHeight, 140) + 'px';
	}

	function createSurface(root, options) {
		options = options || {};
		uid += 1;
		var inputId = 'vance-askai-input-' + uid;

		root.classList.add('vance-askai');
		if (options.inline) {
			root.classList.add('vance-askai--inline');
		}

		root.innerHTML =
			'<div class="vance-askai__header">' +
				'<span class="vance-askai__badge">' + ICON.chat + '</span>' +
				'<div class="vance-askai__titles">' +
					'<h2 class="vance-askai__title">' + escapeHtml(CFG.title || 'Ask AI') + '</h2>' +
					'<p class="vance-askai__subtitle">' + escapeHtml(CFG.subtitle || '') + '</p>' +
				'</div>' +
				'<div class="vance-askai__header-actions">' +
					'<button type="button" class="vance-askai__iconbtn" data-askai-new aria-label="Start a new conversation" title="Start a new conversation">' + ICON.fresh + '</button>' +
					(options.modal ? '<button type="button" class="vance-askai__iconbtn" data-askai-close aria-label="Close" title="Close">' + ICON.close + '</button>' : '') +
				'</div>' +
			'</div>' +
			'<div class="vance-askai__log" role="log" aria-live="polite"></div>' +
			'<div class="vance-askai__composer">' +
				'<div class="vance-askai__inputrow">' +
					'<label class="screen-reader-text" for="' + inputId + '">Your question</label>' +
					'<textarea id="' + inputId + '" class="vance-askai__input" rows="1" placeholder="' + escapeHtml(CFG.placeholder || 'Ask a question…') + '"></textarea>' +
					'<button type="button" class="vance-askai__send">' + ICON.send + '<span>Send</span></button>' +
				'</div>' +
				'<p class="vance-askai__foot">' + (CFG.footNote || '') + '</p>' +
			'</div>';

		var surface = {
			root: root,
			log: root.querySelector('.vance-askai__log'),
			input: root.querySelector('.vance-askai__input'),
			send: root.querySelector('.vance-askai__send'),
			isModal: !!options.modal
		};

		surface.send.addEventListener('click', function () {
			submit(surface);
		});

		surface.input.addEventListener('keydown', function (event) {
			if ('Enter' === event.key && !event.shiftKey) {
				event.preventDefault();
				submit(surface);
			}
		});

		surface.input.addEventListener('input', function () {
			autoGrow(surface.input);
		});

		root.querySelector('[data-askai-new]').addEventListener('click', resetConversation);

		var closeBtn = root.querySelector('[data-askai-close]');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeModal);
		}

		surfaces.push(surface);
		renderSurface(surface);
		return surface;
	}

	function submit(surface) {
		var value = (surface.input.value || '').trim();
		if (!value) {
			return;
		}
		surface.input.value = '';
		autoGrow(surface.input);
		ask(value);
	}

	function buildIntro() {
		var wrap = document.createElement('div');
		wrap.className = 'vance-askai__intro';

		var lead = document.createElement('p');
		lead.textContent = CFG.intro || 'Ask a question and I will answer using articles published on this hub.';
		wrap.appendChild(lead);

		var suggestions = Array.isArray(CFG.suggestions) ? CFG.suggestions : [];
		if (suggestions.length) {
			var list = document.createElement('div');
			list.className = 'vance-askai__suggestions';
			suggestions.forEach(function (suggestion) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'vance-askai__suggestion';
				button.textContent = suggestion;
				button.addEventListener('click', function () {
					ask(suggestion);
				});
				list.appendChild(button);
			});
			wrap.appendChild(list);
		}

		return wrap;
	}

	function renderSurface(surface) {
		var log = surface.log;
		log.innerHTML = '';

		if (!state.messages.length) {
			log.appendChild(buildIntro());
		} else {
			state.messages.forEach(function (message) {
				var bubble = document.createElement('div');
				bubble.className = 'vance-askai__msg vance-askai__msg--' + ('user' === message.role ? 'user' : 'bot');
				if ('user' === message.role) {
					bubble.textContent = message.content;
				} else {
					bubble.innerHTML = formatReply(message.content);
				}
				log.appendChild(bubble);
			});
		}

		if (state.pending) {
			var thinking = document.createElement('div');
			thinking.className = 'vance-askai__msg vance-askai__msg--bot';
			thinking.innerHTML = '<span class="vance-askai__typing"><span></span><span></span><span></span></span>';
			thinking.setAttribute('aria-label', 'Searching the hub');
			log.appendChild(thinking);
		}

		if (state.error) {
			var error = document.createElement('div');
			error.className = 'vance-askai__msg vance-askai__msg--error';
			error.setAttribute('role', 'alert');
			error.textContent = state.error;
			log.appendChild(error);
		}

		surface.input.disabled = state.pending;
		surface.send.disabled = state.pending;
		log.scrollTop = log.scrollHeight;
	}

	function render() {
		surfaces.forEach(renderSurface);
	}

	function focusComposer() {
		var target = (modalEl && modalEl.classList.contains('is-open')) ? modalSurface : surfaces[0];
		if (target && !target.input.disabled) {
			target.input.focus();
		}
	}

	// =====================================================================
	// Modal
	// =====================================================================

	function ensureModal() {
		if (modalEl) {
			return;
		}
		modalEl = document.createElement('div');
		modalEl.className = 'vance-askai-modal';
		modalEl.id = 'vance-askai-modal';
		modalEl.setAttribute('role', 'dialog');
		modalEl.setAttribute('aria-modal', 'true');
		modalEl.setAttribute('aria-label', CFG.title || 'Ask AI');

		var panel = document.createElement('div');
		panel.className = 'vance-askai-modal__panel';
		modalEl.appendChild(panel);
		document.body.appendChild(modalEl);

		modalSurface = createSurface(panel, { modal: true });

		modalEl.addEventListener('mousedown', function (event) {
			if (event.target === modalEl) {
				closeModal();
			}
		});
	}

	function trapFocus(event) {
		if ('Escape' === event.key) {
			closeModal();
			return;
		}
		if ('Tab' !== event.key || !modalEl) {
			return;
		}
		var focusable = Array.prototype.filter.call(
			modalEl.querySelectorAll('button, textarea, a[href], input, select'),
			function (element) {
				return !element.disabled && null !== element.offsetParent;
			}
		);
		if (!focusable.length) {
			return;
		}
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function openModal(prefill) {
		ensureModal();
		lastFocused = document.activeElement;
		modalEl.classList.add('is-open');
		document.body.classList.add('vance-askai-open');
		document.addEventListener('keydown', trapFocus);

		renderSurface(modalSurface);
		if (prefill) {
			modalSurface.input.value = prefill;
		}

		window.setTimeout(function () {
			modalSurface.input.focus();
			autoGrow(modalSurface.input);
			modalSurface.log.scrollTop = modalSurface.log.scrollHeight;
		}, 30);
	}

	function closeModal() {
		if (!modalEl) {
			return;
		}
		modalEl.classList.remove('is-open');
		document.body.classList.remove('vance-askai-open');
		document.removeEventListener('keydown', trapFocus);
		if (lastFocused && lastFocused.focus) {
			lastFocused.focus();
		}
	}

	// =====================================================================
	// Highlight-to-ask
	// =====================================================================

	function initHighlight() {
		// The dedicated page already gives the reader a full chat surface, and
		// selecting text there should behave normally.
		if (!CFG.postId || !CFG.highlight || document.getElementById('vance-askai-inline')) {
			return;
		}

		var pill = null;
		var timer = null;

		function hidePill() {
			if (pill && pill.parentNode) {
				pill.parentNode.removeChild(pill);
			}
			pill = null;
		}

		function showPill(rect, text) {
			hidePill();

			pill = document.createElement('button');
			pill.type = 'button';
			pill.className = 'vance-askai-pill';
			pill.innerHTML = ICON.spark + '<span>' + escapeHtml((CFG.i18n && CFG.i18n.askPill) || 'Ask AI') + '</span>';

			// Keep the selection alive when the pill takes the press.
			pill.addEventListener('mousedown', function (event) {
				event.preventDefault();
			});

			pill.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				hidePill();
				openModal(
					'Please explain this from "' + (CFG.postTitle || 'this article') + '": "' + text + '"'
				);
			});

			document.body.appendChild(pill);

			var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
			var top = window.pageYOffset + rect.top - pill.offsetHeight - 10;
			// On touch the native selection handles sit above; drop below instead.
			if (coarse || top < window.pageYOffset + 8) {
				top = window.pageYOffset + rect.bottom + 12;
			}

			var left = window.pageXOffset + rect.left + (rect.width / 2) - (pill.offsetWidth / 2);
			var maxLeft = window.pageXOffset + document.documentElement.clientWidth - pill.offsetWidth - 8;
			left = Math.max(window.pageXOffset + 8, Math.min(left, maxLeft));

			pill.style.top = Math.round(top) + 'px';
			pill.style.left = Math.round(left) + 'px';
		}

		function evaluateSelection() {
			// vhh-annotations owns text selection while commenting mode is on.
			if (document.body.classList.contains('vhh-mode-on')) {
				hidePill();
				return;
			}

			var selection = window.getSelection();
			if (!selection || selection.isCollapsed || !selection.rangeCount) {
				hidePill();
				return;
			}

			var text = selection.toString().replace(/\s+/g, ' ').trim();
			if (text.length < MIN_SELECTION || text.length > MAX_SELECTION) {
				hidePill();
				return;
			}

			var range = selection.getRangeAt(0);
			var node = range.commonAncestorContainer;
			var element = (1 === node.nodeType) ? node : node.parentElement;
			if (!element || !element.closest) {
				hidePill();
				return;
			}

			if (element.closest('.vhh-ui, .vance-askai, .vance-askai-modal, input, textarea, select, button, nav, header, footer, .site-header, .site-footer')) {
				hidePill();
				return;
			}

			if (!element.closest('article, main, .entry-content, .gi-cond-main, .post-content')) {
				hidePill();
				return;
			}

			var rect = range.getBoundingClientRect();
			if (!rect || (!rect.width && !rect.height)) {
				hidePill();
				return;
			}

			showPill(rect, text);
		}

		function schedule(delay) {
			window.clearTimeout(timer);
			timer = window.setTimeout(evaluateSelection, delay);
		}

		document.addEventListener('mouseup', function () {
			schedule(10);
		});

		// Touch devices finish a selection without a usable mouseup.
		document.addEventListener('selectionchange', function () {
			if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
				schedule(350);
			}
		});

		document.addEventListener('mousedown', function (event) {
			if (pill && !pill.contains(event.target)) {
				hidePill();
			}
		});

		document.addEventListener('scroll', hidePill, true);
		window.addEventListener('resize', hidePill);
	}

	// =====================================================================
	// Boot
	// =====================================================================

	function boot() {
		loadState();

		var inline = document.getElementById('vance-askai-inline');
		if (inline) {
			createSurface(inline, { inline: true });
		}

		document.addEventListener('click', function (event) {
			if (!event.target.closest) {
				return;
			}
			var trigger = event.target.closest('[data-vance-askai-open]');
			if (trigger) {
				event.preventDefault();
				openModal();
			}
		});

		initHighlight();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.VanceAskAI = {
		open: openModal,
		close: closeModal,
		ask: ask,
		reset: resetConversation
	};
})();
