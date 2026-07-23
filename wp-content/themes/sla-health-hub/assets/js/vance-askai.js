/**
 * VANCE-Ai — shared chat controller.
 *
 * One engine drives every chat surface on the site:
 *   - the site-wide modal   (opened by any [data-vance-askai-open] element)
 *   - the inline mount      (#vance-askai-inline on the VANCE-Ai page)
 *   - highlight-to-ask      (select text in an article, tap the pill)
 *   - the first-visit intro popup on articles
 *
 * Conversation state is shared across surfaces and kept in sessionStorage, so a
 * reader can start a question on one article and carry it to the next. For
 * logged-in users the server auto-saves each exchange against the conversation
 * id, which is what Dashboard → My VANCE-Ai Chats lists.
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
	var LEVEL_KEY = 'vanceAskAiReadingLevel';
	var INTRO_KEY = 'vanceAskAiIntroSeen';
	var STORE_TTL = 6 * 60 * 60 * 1000;      // 6 hours
	var INTRO_TTL = 30 * 24 * 60 * 60 * 1000; // 30 days
	var MAX_TURNS = 40;
	var MIN_SELECTION = 2;   // a single acronym like "IBD" must qualify
	var MAX_SELECTION = 600;
	var AUTOGROW_MAX = 220;  // px, before the reader takes over with the handle
	var REVEAL_MS = 34;      // per word-ish tick (30% slower than the original 26)

	var LEVELS = Array.isArray(CFG.levels) && CFG.levels.length
		? CFG.levels
		: [{ key: 'simple', label: 'Simple' }, { key: 'knowledgeable', label: 'Knowledgeable' }, { key: 'expert', label: 'Expert' }];

	// Inline SVG (Lucide-style) — never emoji.
	var SVG_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
	var ICON = {
		chat: SVG_OPEN + '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
		spark: SVG_OPEN + '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/></svg>',
		close: SVG_OPEN + '<path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>',
		fresh: SVG_OPEN + '<path d="M12 5v14"/><path d="M5 12h14"/></svg>',
		trash: SVG_OPEN + '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
		send: SVG_OPEN + '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>'
	};

	var state = {
		id: null,
		messages: [],
		pending: false,
		error: '',
		level: 'knowledgeable',
		reveal: null   // { index, tokens, shown }
	};

	var surfaces = [];
	var modalEl = null;
	var modalSurface = null;
	var lastFocused = null;
	var revealTimer = null;
	var uid = 0;

	function reducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function now() {
		return (window.performance && window.performance.now) ? window.performance.now() : Date.now();
	}

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
			var storedLevel = window.localStorage.getItem(LEVEL_KEY);
			if (storedLevel && LEVELS.some(function (l) { return l.key === storedLevel; })) {
				state.level = storedLevel;
			} else if (CFG.defaultLevel) {
				state.level = CFG.defaultLevel;
			}
		} catch (e) {}

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

	function persistLevel() {
		try {
			window.localStorage.setItem(LEVEL_KEY, state.level);
		} catch (e) {}
	}

	/** Start a separate conversation. The previous one stays saved. */
	function resetConversation() {
		stopReveal();
		state.id = newConversationId();
		state.messages = [];
		state.error = '';
		persistState();
		render();
		focusComposer();
	}

	/** Wipe this conversation, including the copy stored on the account. */
	function clearConversation() {
		if (state.messages.length && !window.confirm(CFG.i18n && CFG.i18n.clearConfirm ? CFG.i18n.clearConfirm : 'Clear this conversation? It will also be removed from your saved chats.')) {
			return;
		}
		var oldId = state.id;
		stopReveal();
		state.messages = [];
		state.error = '';

		if (CFG.isLoggedIn && CFG.clearEndpoint && oldId) {
			fetch(CFG.clearEndpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
				body: JSON.stringify({ conversation_id: oldId })
			}).catch(function () {});
		}

		state.id = newConversationId();
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

		// Citations: "Read more: <title> | <url>" becomes a titled link. The
		// separator class still accepts dashes so conversations saved before the
		// format changed keep rendering as links.
		//
		// The URL must carry a path segment after the host. That rejects an
		// address the model was cut off part-way through, which would otherwise
		// render as a confident link to nowhere.
		text = text.replace(
			/^[ \t]*Read more:[ \t]*(.+?)[ \t]*[|—–-][ \t]*(https?:\/\/[^\s<\/]+\/[^\s<]*?)[ \t]*$/gim,
			'<a class="vance-askai__cite" href="$2" target="_blank" rel="noopener">$1</a>'
		);

		// The "not from the library" disclosure gets its own visual treatment.
		text = text.replace(
			/^[ \t]*Note:[ \t]*(that last part is general knowledge[^\n]*)$/gim,
			'<span class="vance-askai__note">Note: $1</span>'
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

	/** Balance a half-typed **bold** run so the reveal does not flash raw asterisks. */
	function formatPartial(raw) {
		var stars = (raw.match(/\*\*/g) || []).length;
		return formatReply(stars % 2 ? raw + '**' : raw);
	}

	// =====================================================================
	// Progressive reveal
	// =====================================================================

	function stopReveal() {
		if (revealTimer) {
			window.clearTimeout(revealTimer);
			revealTimer = null;
		}
		state.reveal = null;
	}

	function startReveal(index) {
		stopReveal();
		if (reducedMotion()) {
			render();
			return;
		}
		// Split keeping the separators, so spacing and newlines survive.
		var tokens = String(state.messages[index].content).split(/(\s+)/);
		state.reveal = { index: index, tokens: tokens, shown: 0, startedAt: now() };
		render();
		tickReveal();
	}

	function finishReveal() {
		stopReveal();
		render();
		scrollLogs();
	}

	/**
	 * Advance the reveal to wherever elapsed time says it should be.
	 *
	 * Driven by the clock rather than by a fixed step per tick, because browsers
	 * clamp timers to roughly one per second in a background tab. A fixed step
	 * would leave a long answer crawling for minutes after the reader came back;
	 * this simply catches up to the correct position instead.
	 */
	function tickReveal() {
		var r = state.reveal;
		if (!r) {
			return;
		}

		// Two tokens (a word and its trailing space) per REVEAL_MS.
		var due = Math.floor(((now() - r.startedAt) / REVEAL_MS) * 2);
		r.shown = Math.min(Math.max(r.shown + 2, due), r.tokens.length);

		if (r.shown >= r.tokens.length) {
			finishReveal();
			return;
		}
		paintReveal();
		revealTimer = window.setTimeout(tickReveal, REVEAL_MS);
	}

	/** Update only the bubble being revealed — a full re-render each tick is wasteful. */
	function paintReveal() {
		var r = state.reveal;
		if (!r) {
			return;
		}
		var partial = r.tokens.slice(0, r.shown).join('');
		surfaces.forEach(function (surface) {
			var bubble = surface.log.querySelector('[data-askai-idx="' + r.index + '"]');
			if (bubble) {
				bubble.innerHTML = formatPartial(partial) + '<span class="vance-askai__caret" aria-hidden="true"></span>';
			}
		});
		scrollLogs();
	}

	function scrollLogs() {
		surfaces.forEach(function (surface) {
			surface.log.scrollTop = surface.log.scrollHeight;
		});
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

		stopReveal();
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
			context_post_id: CFG.postId || 0,
			reading_level: state.level
		}).then(function (data) {
			state.pending = false;
			var reply = data && (data.reply || data.answer);
			if (reply) {
				state.messages.push({ role: 'assistant', content: reply });
				persistState();
				startReveal(state.messages.length - 1);
			} else {
				state.error = (CFG.i18n && CFG.i18n.empty) || 'No answer came back. Please try again.';
				render();
			}
			focusComposer();
		}).catch(function (error) {
			state.pending = false;
			state.error = error && error.message ? error.message : 'That request failed. Please try again.';
			render();
			focusComposer();
		});
	}

	/**
	 * Re-answer the most recent question — used when the reading level changes
	 * after an answer is already on screen.
	 */
	function regenerate() {
		if (state.pending || !state.messages.length) {
			return;
		}
		var i = state.messages.length - 1;
		while (i >= 0 && 'assistant' === state.messages[i].role) {
			i--;
		}
		if (i < 0) {
			return;
		}
		var question = state.messages[i].content;
		state.messages = state.messages.slice(0, i); // ask() re-appends the question
		ask(question);
	}

	function hasAnswer() {
		return state.messages.some(function (m) { return 'assistant' === m.role; });
	}

	// =====================================================================
	// Reading level
	// =====================================================================

	function levelIndex(key) {
		for (var i = 0; i < LEVELS.length; i++) {
			if (LEVELS[i].key === key) {
				return i;
			}
		}
		return 1;
	}

	function setLevel(key, regenerateAfter) {
		if (!key || key === state.level) {
			return;
		}
		state.level = key;
		persistLevel();
		surfaces.forEach(syncLevelUI);
		if (regenerateAfter && hasAnswer()) {
			regenerate();
		}
	}

	function syncLevelUI(surface) {
		if (!surface.level) {
			return;
		}
		surface.level.value = String(levelIndex(state.level) + 1);
		surface.levelName.textContent = LEVELS[levelIndex(state.level)].label;
		surface.level.setAttribute('aria-valuetext', LEVELS[levelIndex(state.level)].label);
	}

	// =====================================================================
	// Surfaces
	// =====================================================================

	/**
	 * Grow the composer to fit its content, up to a limit.
	 *
	 * The textarea is also user-resizable. Once the reader drags the handle their
	 * height wins permanently, otherwise the next keystroke would snap the box
	 * back and the handle would feel broken. A drag is detected by comparing the
	 * current inline height against the last one this function applied, which is
	 * reliable even while the element is hidden (a ResizeObserver reports nothing
	 * for a display:none subtree, so it cannot be used here).
	 */
	function autoGrow(input) {
		if ('1' === input.getAttribute('data-user-resized')) {
			return;
		}

		var applied = input.getAttribute('data-auto-height');
		if (applied && input.style.height && input.style.height !== applied) {
			input.setAttribute('data-user-resized', '1');
			return;
		}

		input.style.height = 'auto';
		var height = Math.min(input.scrollHeight, AUTOGROW_MAX) + 'px';
		input.style.height = height;
		input.setAttribute('data-auto-height', height);
	}

	function levelMarkup(id) {
		var ticks = LEVELS.map(function (l) {
			return '<span>' + escapeHtml(l.label) + '</span>';
		}).join('');

		return '<div class="vance-askai__level">' +
			'<label class="vance-askai__level-label" for="' + id + '">' +
				escapeHtml((CFG.i18n && CFG.i18n.levelLabel) || 'Answer detail') +
				': <strong class="vance-askai__level-name"></strong>' +
			'</label>' +
			'<input type="range" class="vance-askai__level-input" id="' + id + '" min="1" max="' + LEVELS.length + '" step="1" value="2" ' +
				'aria-label="' + escapeHtml((CFG.i18n && CFG.i18n.levelLabel) || 'Answer detail') + '">' +
			'<div class="vance-askai__level-ticks">' + ticks + '</div>' +
		'</div>';
	}

	function createSurface(root, options) {
		options = options || {};
		uid += 1;
		var inputId = 'vance-askai-input-' + uid;
		var levelId = 'vance-askai-level-' + uid;

		root.classList.add('vance-askai');
		if (options.inline) {
			root.classList.add('vance-askai--inline');
		}

		// The inline mount has no header: the VANCE-Ai page already carries the
		// name and strapline in its own hero and agent bar, so repeating them
		// inside the chat just pushed the conversation down the page.
		var headerHtml = options.inline
			? ''
			: '<div class="vance-askai__header">' +
					'<span class="vance-askai__badge">' + ICON.chat + '</span>' +
					'<div class="vance-askai__titles">' +
						'<h2 class="vance-askai__title">' + escapeHtml(CFG.title || 'VANCE-Ai') + '</h2>' +
						'<p class="vance-askai__subtitle">' + escapeHtml(CFG.subtitle || '') + '</p>' +
					'</div>' +
					(options.modal
						? '<div class="vance-askai__header-actions">' +
							'<button type="button" class="vance-askai__iconbtn" data-askai-close aria-label="Close" title="Close">' + ICON.close + '</button>' +
						  '</div>'
						: '') +
				'</div>';

		root.innerHTML =
			headerHtml +
			'<div class="vance-askai__log" role="log" aria-live="polite"></div>' +
			'<div class="vance-askai__composer">' +
				'<label class="screen-reader-text" for="' + inputId + '">Your question</label>' +
				'<textarea id="' + inputId + '" class="vance-askai__input" rows="1" placeholder="' + escapeHtml(CFG.placeholder || 'Ask a question…') + '"></textarea>' +
				// Slider sits under the input, sharing its row with the button stack.
				'<div class="vance-askai__controls">' +
					levelMarkup(levelId) +
					// Send leads, with the two secondary actions sharing the row
					// beneath it. Three full-width buttons stacked made the whole
					// control area twice as tall as it needed to be.
					'<div class="vance-askai__actions">' +
						'<button type="button" class="vance-askai__send">' + ICON.send + '<span>' + escapeHtml((CFG.i18n && CFG.i18n.send) || 'Send') + '</span></button>' +
						'<div class="vance-askai__actions-row">' +
							'<button type="button" class="vance-askai__minibtn" data-askai-new title="Start a new conversation, this one stays saved">' + ICON.fresh + '<span>' + escapeHtml((CFG.i18n && CFG.i18n.newChat) || 'New chat') + '</span></button>' +
							'<button type="button" class="vance-askai__minibtn" data-askai-clear title="Clear this conversation and delete it">' + ICON.trash + '<span>' + escapeHtml((CFG.i18n && CFG.i18n.clearChat) || 'Clear') + '</span></button>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="vance-askai__foot">' + (CFG.footNote || '') + '</div>' +
				(CFG.disclaimer
					? '<details class="vance-askai__disclaimer"><summary>' + escapeHtml((CFG.i18n && CFG.i18n.disclaimerTitle) || 'Important: how to use this assistant') + '</summary><div>' + CFG.disclaimer + '</div></details>'
					: '') +
			'</div>';

		var surface = {
			root: root,
			log: root.querySelector('.vance-askai__log'),
			input: root.querySelector('.vance-askai__input'),
			send: root.querySelector('.vance-askai__send'),
			level: root.querySelector('.vance-askai__level-input'),
			levelName: root.querySelector('.vance-askai__level-name'),
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

		// Dragging updates the label live; releasing commits and re-answers.
		surface.level.addEventListener('input', function () {
			var picked = LEVELS[Math.min(LEVELS.length - 1, Math.max(0, this.value - 1))];
			surface.levelName.textContent = picked.label;
		});
		surface.level.addEventListener('change', function () {
			var picked = LEVELS[Math.min(LEVELS.length - 1, Math.max(0, this.value - 1))];
			setLevel(picked.key, true);
		});

		root.querySelector('[data-askai-new]').addEventListener('click', resetConversation);
		root.querySelector('[data-askai-clear]').addEventListener('click', clearConversation);

		var closeBtn = root.querySelector('[data-askai-close]');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeModal);
		}

		surfaces.push(surface);
		syncLevelUI(surface);
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
			state.messages.forEach(function (message, index) {
				var bubble = document.createElement('div');
				bubble.className = 'vance-askai__msg vance-askai__msg--' + ('user' === message.role ? 'user' : 'bot');
				bubble.setAttribute('data-askai-idx', index);

				if ('user' === message.role) {
					bubble.textContent = message.content;
				} else if (state.reveal && state.reveal.index === index) {
					var partial = state.reveal.tokens.slice(0, state.reveal.shown).join('');
					bubble.innerHTML = formatPartial(partial) + '<span class="vance-askai__caret" aria-hidden="true"></span>';
					bubble.title = 'Click to show the whole answer';
					bubble.addEventListener('click', finishReveal);
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
		modalEl.setAttribute('aria-label', CFG.title || 'VANCE-Ai');

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
			modalEl.querySelectorAll('button, textarea, a[href], input, select, summary'),
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
		syncLevelUI(modalSurface);
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
			pill.innerHTML = ICON.spark + '<span>' + escapeHtml((CFG.i18n && CFG.i18n.askPill) || 'Ask VANCE-Ai') + '</span>';

			// Keep the selection alive when the pill takes the press.
			pill.addEventListener('mousedown', function (event) {
				event.preventDefault();
			});

			pill.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				hidePill();

				// A single word or acronym reads better as "what does X mean".
				var isTerm = text.split(/\s+/).length <= 3;
				var question = isTerm
					? 'What does "' + text + '" mean? It appears in the article "' + (CFG.postTitle || 'this article') + '".'
					: 'Please explain this from "' + (CFG.postTitle || 'this article') + '": "' + text + '"';

				openModal(question);
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

			if (element.closest('.vhh-ui, .vance-askai, .vance-askai-modal, .vance-askai-intro, input, textarea, select, button, nav, header, footer, .site-header, .site-footer')) {
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
	// First-visit intro popup on articles
	// =====================================================================

	function todayStamp() {
		var d = new Date();
		return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
	}

	function readIntroRecord() {
		try {
			var raw = window.localStorage.getItem(INTRO_KEY);
			if (!raw) {
				return null;
			}
			var rec = JSON.parse(raw);
			// Older builds stored a bare timestamp; treat it as one past showing.
			return (rec && 'object' === typeof rec) ? rec : { last: Number(raw) || 0, day: '', count: 1 };
		} catch (e) {
			return null;
		}
	}

	/** Does the admin-configured frequency allow showing the popup right now? */
	function introFrequencyAllows() {
		var rec = readIntroRecord();
		if (!rec) {
			return true;
		}

		var frequency = CFG.introFrequency || 'monthly';

		if ('x_per_day' === frequency) {
			var cap = Math.max(1, parseInt(CFG.introPerDay, 10) || 1);
			return (rec.day !== todayStamp()) || ((rec.count || 0) < cap);
		}

		var days = { daily: 1, weekly: 7, monthly: 30 }[frequency] || 30;
		return (Date.now() - (rec.last || 0)) >= days * 24 * 60 * 60 * 1000;
	}

	function markIntroSeen() {
		var today = todayStamp();
		var rec = readIntroRecord();
		if (!rec || rec.day !== today) {
			rec = { day: today, count: 0 };
		}
		rec.count = (rec.count || 0) + 1;
		rec.last = Date.now();
		try {
			window.localStorage.setItem(INTRO_KEY, JSON.stringify(rec));
		} catch (e) {}
	}

	function initArticleIntro() {
		if (!CFG.postId || !CFG.introEnabled || !introFrequencyAllows()) {
			return;
		}
		if (document.getElementById('vance-askai-inline')) {
			return;
		}

		window.setTimeout(function () {
			// Don't interrupt someone who has already started chatting.
			if (document.body.classList.contains('vance-askai-open')) {
				return;
			}
			showIntro();
		}, 1400);
	}

	function showIntro() {
		markIntroSeen();

		var overlay = document.createElement('div');
		overlay.className = 'vance-askai-intro-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		overlay.setAttribute('aria-labelledby', 'vance-askai-intro-title');

		var showRegister = !CFG.isLoggedIn;

		// Two columns: copy and buttons on the left, image on the right. When no
		// image is configured the right column shows a branded placeholder so the
		// layout still reads as intended.
		var media = CFG.introImage
			? '<img src="' + escapeHtml(CFG.introImage) + '" alt="">'
			: '<div class="vance-askai-intro__placeholder">' + ICON.spark + '<span>VANCE-Ai</span></div>';

		overlay.innerHTML =
			'<div class="vance-askai-intro">' +
				'<button type="button" class="vance-askai-intro__close" aria-label="Close">' + ICON.close + '</button>' +
				'<div class="vance-askai-intro__grid">' +
					'<div class="vance-askai-intro__col">' +
						'<span class="vance-askai-intro__badge">' + ICON.spark + '</span>' +
						'<h2 class="vance-askai-intro__title" id="vance-askai-intro-title">' + escapeHtml(CFG.introTitle || 'Reading something new? Ask VANCE-Ai.') + '</h2>' +
						'<div class="vance-askai-intro__body">' + (CFG.introBody || '') + '</div>' +
						'<div class="vance-askai-intro__actions">' +
							(showRegister ? '<button type="button" class="vance-askai-intro__btn vance-askai-intro__btn--ghost" data-askai-intro-register>' + escapeHtml((CFG.i18n && CFG.i18n.register) || 'Register free') + '</button>' : '') +
							'<button type="button" class="vance-askai-intro__btn vance-askai-intro__btn--primary" data-askai-intro-try>' + escapeHtml((CFG.i18n && CFG.i18n.tryIt) || 'Try it now') + '</button>' +
						'</div>' +
					'</div>' +
					'<div class="vance-askai-intro__media">' + media + '</div>' +
				'</div>' +
			'</div>';

		document.body.appendChild(overlay);
		document.body.classList.add('vance-askai-open');

		function dismiss() {
			if (overlay.parentNode) {
				overlay.parentNode.removeChild(overlay);
			}
			document.body.classList.remove('vance-askai-open');
			document.removeEventListener('keydown', onKey);
		}

		function onKey(event) {
			if ('Escape' === event.key) {
				dismiss();
			}
		}

		overlay.addEventListener('mousedown', function (event) {
			if (event.target === overlay) {
				dismiss();
			}
		});
		overlay.querySelector('.vance-askai-intro__close').addEventListener('click', dismiss);
		document.addEventListener('keydown', onKey);

		overlay.querySelector('[data-askai-intro-try]').addEventListener('click', function () {
			dismiss();
			openModal();
		});

		var registerBtn = overlay.querySelector('[data-askai-intro-register]');
		if (registerBtn) {
			registerBtn.addEventListener('click', function () {
				dismiss();
				if (window.VanceRegisterModal && typeof window.VanceRegisterModal.open === 'function') {
					window.VanceRegisterModal.open({ tool: '', payload: {} });
				} else if (CFG.registerUrl) {
					window.location.href = CFG.registerUrl;
				}
			});
		}

		window.setTimeout(function () {
			var focusTarget = overlay.querySelector('[data-askai-intro-try]');
			if (focusTarget) {
				focusTarget.focus();
			}
		}, 40);
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
		initArticleIntro();
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
		reset: resetConversation,
		clear: clearConversation,
		setLevel: function (key) { setLevel(key, false); },
		getLevel: function () { return state.level; }
	};
})();
