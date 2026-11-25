/**
 * VanceHubClub result page for the Gastro Self-Assessment (inc/hub-tool-result.php).
 *
 * The survey template shows #results-screen at the last step. This script
 * watches for that, reads the answers from #health-quiz-form, builds the
 * "What you told us" tiles and reveals the hidden #vhub-tool-result block.
 *
 * It changes nothing of the survey itself: no ID, field name, AJAX action or
 * nonce. It never invents a tile: a question with no answer gives no tile.
 *
 * It stores nothing. A guest's answers stay on the page only, and are gone
 * when the page closes: health answers are not kept in the browser, where
 * every other script on the site could read them.
 * Members: the survey template saves the answers itself. The "Saved to My Hub"
 * card appears only after that request answers with success.
 */
(function (root) {
	'use strict';

	function list(value) {
		if (Array.isArray(value)) { return value; }
		if (value === undefined || value === null || value === '') { return []; }
		return [value];
	}

	function clean(value) {
		return String(value === undefined || value === null ? '' : value).replace(/\s+/g, ' ').trim();
	}

	function cleanList(value) {
		return list(value).map(clean).filter(Boolean);
	}

	/**
	 * Turn answers into tiles. A field with no answer gives no tile.
	 *
	 * @param {Array}  fields  Spec rows from vance_hub_tool_result_fields().
	 * @param {Object} answers Field name => string or array of strings.
	 * @return {Array} Rows of {label, value}.
	 */
	function buildItems(fields, answers) {
		var items = [];
		answers = answers || {};
		(fields || []).forEach(function (f) {
			var values = cleanList(answers[f.field]);
			if (!values.length) { return; }
			var parts = values.map(function (v) {
				var shown = (f.map && f.map[v]) || v;
				var free = f.text && f.text[v] ? clean(answers[f.text[v]]) : '';
				if (free) { return v + ': ' + free; }
				var dep = f.dep && f.dep[v];
				if (dep) {
					var inner = cleanList(answers[dep.field]).map(function (x) {
						var t = dep.text && dep.text[x] ? clean(answers[dep.text[x]]) : '';
						return t ? x + ': ' + t : x;
					});
					if (inner.length) { return shown + ': ' + inner.join(', '); }
				}
				return shown;
			});
			items.push({ label: f.label, value: parts.join(', ') });
		});
		return items;
	}

	/** Read the form into field name => array of values (the [] suffix removed). */
	function collect(form) {
		var out = {};
		if (!form || typeof FormData === 'undefined') { return out; }
		var data = new FormData(form);
		data.forEach(function (value, key) {
			var name = key.replace('[]', '');
			if (!out[name]) { out[name] = []; }
			out[name].push(String(value));
		});
		return out;
	}

	var api = { buildItems: buildItems, collect: collect };
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = api;
	}
	if (!root || !root.document) { return; }

	var doc = root.document;
	var block = doc.getElementById('vhub-tool-result');
	var screen = doc.getElementById('results-screen');
	var form = doc.getElementById('health-quiz-form');
	if (!block || !screen || !form) { return; }

	var config = {};
	try { config = JSON.parse(block.getAttribute('data-config') || '{}'); } catch (e) { config = {}; }
	var wrapper = block.parentNode;
	var shown = false;

	function renderTiles(answers) {
		var summary = doc.getElementById('vhub-tr-summary');
		var tiles = doc.getElementById('vhub-tr-tiles');
		if (!summary || !tiles) { return; }
		var items = buildItems(config.fields, answers);
		tiles.textContent = '';
		items.forEach(function (item) {
			var tile = doc.createElement('div');
			tile.className = 'vhub-tr__tile';
			var label = doc.createElement('span');
			label.className = 'vhub-tr__tile-label';
			label.textContent = item.label;
			var value = doc.createElement('span');
			value.className = 'vhub-tr__tile-value';
			value.textContent = item.value;
			tile.appendChild(label);
			tile.appendChild(value);
			tiles.appendChild(tile);
		});
		// No answers, no card.
		summary.hidden = items.length === 0;
	}

	function reveal(answers) {
		if (shown) { return; }
		shown = true;
		renderTiles(answers);
		block.hidden = false;
		wrapper.classList.add('vhub-result-on');
		if (root.scrollTo) { root.scrollTo(0, 0); }
	}

	function showSavedCard() {
		var card = doc.getElementById('vhub-tr-saved');
		if (card) { card.hidden = false; }
	}

	// A member's survey saves itself; show the card once that request succeeds.
	if (config.member && root.jQuery) {
		root.jQuery(doc).on('ajaxSuccess', function (event, xhr, settings, data) {
			var sent = settings && typeof settings.data === 'string' ? settings.data : '';
			if (sent.indexOf('action=vance_save_quiz_results') !== -1 && data && data.success) {
				showSavedCard();
			}
		});
	}

	function visible(el) {
		return el.style.display !== 'none' && el.style.display !== '';
	}

	// The survey shows its own result by setting display:block on #results-screen.
	if (root.MutationObserver) {
		var watcher = new root.MutationObserver(function () {
			if (visible(screen)) { watcher.disconnect(); reveal(collect(form)); }
		});
		watcher.observe(screen, { attributes: true, attributeFilter: ['style'] });
	}

	var printBtn = doc.getElementById('vhub-tr-print');
	if (printBtn) {
		printBtn.addEventListener('click', function () { root.print(); });
	}
}(typeof window !== 'undefined' ? window : null));
