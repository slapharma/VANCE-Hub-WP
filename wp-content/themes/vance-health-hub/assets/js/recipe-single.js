/**
 * Single recipe page — vanilla JS, no build step.
 *
 * Three independent features:
 *   1. Servings stepper — scales the displayed ingredient quantities.
 *   2. "Add to meal plan" quick-add modal — a native 7x4 mini-grid, not the
 *      generic tool modal (see inc/tool-modal.php's header comment for why).
 *      Reads/writes the exact same localStorage['vance.mealPlan.v2'] shape
 *      assets/js/recipe-planner.js uses, so a plan started here and one
 *      started on the hub page are the same plan.
 *   3. Download PDF — reuses the html2pdf.js pattern from page-dashboard.php.
 *
 * Config: window.vanceRecipeSingle (wp_localize_script(), see
 * inc/recipe-frontend.php's vance_recipe_single_script_config()).
 */
(function () {
	'use strict';

	var CFG = window.vanceRecipeSingle;
	if (!CFG) { return; }

	var STORAGE_KEY = 'vance.mealPlan.v2';
	var SLOT_KEYS = ['breakfast', 'lunch', 'dinner', 'snack'];

	// ======================================================================
	// 1. Servings stepper
	// ======================================================================

	var FRAC = { '¼': 0.25, '½': 0.5, '¾': 0.75, '⅓': 1 / 3, '⅔': 2 / 3, '⅕': 0.2, '⅖': 0.4, '⅗': 0.6, '⅘': 0.8, '⅙': 1 / 6, '⅚': 5 / 6, '⅛': 0.125, '⅜': 0.375, '⅝': 0.625, '⅞': 0.875 };
	var FRAC_CHARS = Object.keys(FRAC).join('');
	var NUM_TOKEN = '(?:\\d+\\s+\\d+\\/\\d+|\\d+\\/\\d+|\\d+[' + FRAC_CHARS + ']|[' + FRAC_CHARS + ']|\\d+(?:\\.\\d+)?)';
	var LEADING_QTY_RE = new RegExp('^(' + NUM_TOKEN + ')(\\s*[-–]\\s*(' + NUM_TOKEN + '))?');

	function parseNumberToken(tok) {
		tok = tok.trim();
		var fracOnly = tok.match(new RegExp('^(\\d+)?\\s*([' + FRAC_CHARS + '])$'));
		if (fracOnly) {
			return (fracOnly[1] ? parseFloat(fracOnly[1]) : 0) + FRAC[fracOnly[2]];
		}
		var mixed = tok.match(/^(\d+)\s+(\d+)\/(\d+)$/);
		if (mixed) {
			return parseFloat(mixed[1]) + parseFloat(mixed[2]) / parseFloat(mixed[3]);
		}
		var simpleFrac = tok.match(/^(\d+)\/(\d+)$/);
		if (simpleFrac) {
			return parseFloat(simpleFrac[1]) / parseFloat(simpleFrac[2]);
		}
		var num = parseFloat(tok);
		return isNaN(num) ? null : num;
	}

	// Snaps to the nearest quarter and renders as a unicode fraction where
	// possible — "exact" decimals like 1.33 read as an estimate anyway once
	// you're scaling a recipe, so a tidy ¼-step reads more like a real recipe.
	function formatQty(n) {
		var snapped = Math.round(n * 4) / 4;
		var whole = Math.floor(snapped);
		var frac = snapped - whole;
		var fracStr = '';
		if (frac >= 0.875) { whole += 1; }
		else if (frac >= 0.625) { fracStr = '¾'; }
		else if (frac >= 0.375) { fracStr = '½'; }
		else if (frac >= 0.125) { fracStr = '¼'; }
		if (0 === whole && fracStr) { return fracStr; }
		return fracStr ? (whole + fracStr) : String(whole);
	}

	function scaleIngredientLine(line, ratio) {
		var m = line.match(LEADING_QTY_RE);
		if (!m) { return line; } // No leading quantity — e.g. "Pinch of salt", "Optional: ..." — leave untouched.
		var startVal = parseNumberToken(m[1]);
		if (null === startVal) { return line; }
		var out = formatQty(startVal * ratio);
		if (m[3]) {
			var endVal = parseNumberToken(m[3]);
			if (null !== endVal) { out += '–' + formatQty(endVal * ratio); }
		}
		return out + line.slice(m[0].length);
	}

	var servingsInput = document.getElementById('vance-rs-servings');
	var ingredientEls = document.querySelectorAll('[data-ingredient-line]');
	var baseServings = CFG.recipe.servings || 0;

	function applyServings(target) {
		if (!baseServings || target < 1) { return; }
		var ratio = target / baseServings;
		ingredientEls.forEach(function (el) {
			if (!el.dataset.baseText) { el.dataset.baseText = el.textContent; }
			el.textContent = scaleIngredientLine(el.dataset.baseText, ratio);
		});
		var servesLabel = document.getElementById('vance-rs-serves-label');
		if (servesLabel) { servesLabel.textContent = target; }
	}

	if (servingsInput && baseServings) {
		servingsInput.value = baseServings;
		servingsInput.addEventListener('change', function () {
			var v = Math.max(1, Math.min(50, parseInt(servingsInput.value, 10) || baseServings));
			servingsInput.value = v;
			applyServings(v);
		});
		var minus = document.getElementById('vance-rs-servings-minus');
		var plus  = document.getElementById('vance-rs-servings-plus');
		if (minus) { minus.addEventListener('click', function () { servingsInput.value = Math.max(1, (parseInt(servingsInput.value, 10) || baseServings) - 1); applyServings(parseInt(servingsInput.value, 10)); }); }
		if (plus)  { plus.addEventListener('click', function () { servingsInput.value = Math.min(50, (parseInt(servingsInput.value, 10) || baseServings) + 1); applyServings(parseInt(servingsInput.value, 10)); }); }
	}

	// ======================================================================
	// 2. "Add to meal plan" quick-add modal
	// ======================================================================

	function emptyDays() {
		return CFG.days.map(function (day) {
			return { day: day, meals: { breakfast: null, lunch: null, dinner: null, snack: null } };
		});
	}
	function loadPlanState() {
		try {
			var raw = window.localStorage.getItem(STORAGE_KEY);
			if (raw) {
				var parsed = JSON.parse(raw);
				if (parsed && Array.isArray(parsed.days)) { return parsed; }
			}
		} catch (e) { /* localStorage unavailable or corrupt — start fresh */ }
		return { name: '', days: emptyDays() };
	}
	function savePlanState(state) {
		try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* quota/private mode */ }
	}

	var modal      = document.getElementById('vance-rs-modal');
	var grid       = document.getElementById('vance-rs-modal-grid');
	var openBtn    = document.getElementById('vance-rs-addplan-trigger');
	var closeBtn   = document.getElementById('vance-rs-modal-close');
	var toast      = document.getElementById('vance-rs-toast');

	function showToast(msg, ms) {
		if (!toast) { return; }
		toast.textContent = msg;
		toast.classList.add('is-visible');
		clearTimeout(showToast._t);
		showToast._t = setTimeout(function () { toast.classList.remove('is-visible'); }, ms || 3200);
	}

	function renderModalGrid() {
		if (!grid) { return; }
		var state = loadPlanState();
		grid.innerHTML = state.days.map(function (dayState) {
			var cells = SLOT_KEYS.map(function (slot) {
				var meal = dayState.meals[slot];
				var filled = meal ? ' is-filled' : '';
				var label = meal ? meal.name : '+';
				return '<button type="button" class="vance-rs-modal-cell' + filled + '" data-day="' + escapeAttr(dayState.day) + '" data-slot="' + slot + '" title="' + slot.charAt(0).toUpperCase() + slot.slice(1) + '">' + escapeHtml(label) + '</button>';
			}).join('');
			return '<div class="vance-rs-modal-day"><span>' + escapeHtml(dayState.day.slice(0, 3)) + '</span><div class="vance-rs-modal-cells">' + cells + '</div></div>';
		}).join('');
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }

	function openQuickAddModal() {
		renderModalGrid();
		if (modal) { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); }
	}
	function closeQuickAddModal() {
		if (modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); }
	}

	if (openBtn) {
		openBtn.addEventListener('click', function (e) {
			e.preventDefault();
			openQuickAddModal();
		});
	}
	if (closeBtn) { closeBtn.addEventListener('click', closeQuickAddModal); }
	if (modal) {
		modal.addEventListener('click', function (e) { if (e.target === modal) { closeQuickAddModal(); } });
	}
	if (grid) {
		grid.addEventListener('click', function (e) {
			var cell = e.target.closest('[data-day]');
			if (!cell) { return; }
			var state = loadPlanState();
			var day = cell.getAttribute('data-day');
			var slot = cell.getAttribute('data-slot');
			var dayState = state.days.filter(function (d) { return d.day === day; })[0];
			if (!dayState) { return; }
			dayState.meals[slot] = {
				slug: CFG.recipe.slug,
				name: CFG.recipe.name,
				calories: CFG.recipe.calories || 0,
				minutes: CFG.recipe.minutes || 0
			};
			savePlanState(state);
			showToast('Added to ' + day + ' ' + slot + ' ✓', 3200);
			closeQuickAddModal();
		});
	}

	// ======================================================================
	// 3. Download PDF — same html2pdf.js pattern as the dashboard's plan export.
	// ======================================================================

	var pdfBtn = document.getElementById('vance-rs-pdf');
	if (pdfBtn) {
		pdfBtn.addEventListener('click', function () {
			if (typeof window.html2pdf === 'undefined') {
				showToast('PDF export is still loading, try again in a moment.', 3500);
				return;
			}
			var label = pdfBtn.textContent;
			pdfBtn.disabled = true;
			pdfBtn.textContent = 'Building PDF…';

			// The off-screen offset MUST live on a WRAPPER, never on the element
			// handed to html2pdf. html2pdf clones that element into an
			// inline-block container and measures it; a clone that is itself
			// position:fixed contributes no in-flow height, so the container
			// measures zero and the export rasterises to a 0px-tall canvas — a
			// blank PDF, with no error thrown anywhere. Measured on the live
			// page: fixed-on-element 0x0, wrapper-holds-offset 794x<content>.
			// This is the same trap, and the same fix, as page-dashboard.php's
			// meal-plan export — see the long comment there.
			//
			// `absolute`, not `fixed`: a fixed wrapper is positioned against the
			// viewport, so its document-space box moves with the scroll offset,
			// and html2canvas captures from the document origin — which turns
			// the gap into leading blank pages.
			var holder = document.createElement('div');
			holder.style.cssText = 'position:absolute; left:-10000px; top:0; width:794px;'; // 794px ≈ A4 at 96dpi
			var el = buildPdfElement();
			holder.appendChild(el);
			document.body.appendChild(holder);

			var cleanup = function () {
				if (holder.parentNode) { holder.parentNode.removeChild(holder); }
				pdfBtn.disabled = false;
				pdfBtn.textContent = label;
			};

			window.html2pdf().set({
				margin: 10,
				filename: (CFG.recipe.slug || 'recipe') + '.pdf',
				image: { type: 'jpeg', quality: 0.95 },
				// scrollX/scrollY default to the page's current scroll offset;
				// pinning them to 0 is the second half of the blank-page fix and
				// covers html2pdf re-parenting the clone into its own overlay
				// before html2canvas ever sees it.
				html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF', logging: false, scrollX: 0, scrollY: 0 },
				jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
			}).from(el).save().then(cleanup, function () {
				cleanup();
				showToast('Could not build the PDF, please try again.', 3500);
			});
		});
	}

	function esc(s) { return escapeHtml(s == null ? '' : s); }

	function buildPdfElement() {
		var TEAL = '#008080', INK = '#0A1929', BODY = '#334155', MUTE = '#64748B';
		var r = CFG.recipe;
		var currentServings = servingsInput ? (parseInt(servingsInput.value, 10) || r.servings) : r.servings;

		var ingredientsHtml = (r.ingredients || []).map(function (sec) {
			var title = sec.section ? '<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.6px;color:' + TEAL + ';margin:10px 0 4px;">' + esc(sec.section) + '</div>' : '';
			return title + (sec.items || []).map(function (item) {
				return '<div style="font-size:11.5px;color:' + BODY + ';padding:3px 0;border-bottom:1px solid #F1F5F9;">' +
					'<span style="display:inline-block;width:8px;height:8px;border:1px solid #94A3B8;margin-right:8px;"></span>' + esc(item) + '</div>';
			}).join('');
		}).join('');

		var stepsHtml = (r.method || []).map(function (step, i) {
			return '<tr><td style="width:22px;vertical-align:top;padding:4px 8px 4px 0;">' +
				'<span style="display:inline-block;width:17px;height:17px;background:' + TEAL + ';color:#fff;font-size:10px;font-weight:800;text-align:center;line-height:17px;">' + (i + 1) + '</span></td>' +
				'<td style="font-size:11.5px;color:' + BODY + ';line-height:1.55;padding:4px 0;">' + esc(step) + '</td></tr>';
		}).join('');

		var metaBits = [];
		if (currentServings) { metaBits.push('Serves ' + currentServings); }
		if (r.prep) { metaBits.push(r.prep + ' min prep'); }
		if (r.cook) { metaBits.push(r.cook + ' min cook'); }

		var nutritionHtml = '';
		if (r.nutrition && r.nutrition.calories) {
			var n = r.nutrition;
			nutritionHtml = '<table style="width:100%;border-collapse:collapse;margin-top:14px;"><tr>' +
				['calories,kcal', 'protein,g protein', 'carbs,g carbs', 'fat,g fat', 'fibre,g fibre'].map(function (spec) {
					var parts = spec.split(',');
					var val = n[parts[0]];
					return '<td style="text-align:center;padding:8px 4px;border:1px solid #E2E8F0;"><div style="font-size:15px;font-weight:800;color:' + INK + ';">' + esc(val || 0) + '</div><div style="font-size:8.5px;color:' + MUTE + ';text-transform:uppercase;">' + parts[1] + '</div></td>';
				}).join('') + '</tr></table>';
		}

		// Deliberately NO positioning here — the caller's off-screen wrapper owns
		// that. Anything that takes this element out of normal flow makes
		// html2pdf measure it as zero-height and emit a blank PDF.
		var wrap = document.createElement('div');
		wrap.style.cssText = 'width:100%;box-sizing:border-box;background:#fff;padding:14px;font-family:Arial,sans-serif;';
		wrap.innerHTML =
			'<div style="border-bottom:3px solid ' + TEAL + ';padding-bottom:10px;margin-bottom:14px;">' +
				'<div style="font-size:10px;font-weight:700;color:' + TEAL + ';text-transform:uppercase;letter-spacing:0.6px;">Vance Medical Hub</div>' +
				'<div style="font-size:20px;font-weight:800;color:' + INK + ';margin-top:4px;">' + esc(r.name) + '</div>' +
				(metaBits.length ? '<div style="font-size:11px;color:' + MUTE + ';margin-top:4px;">' + metaBits.join(' &nbsp;·&nbsp; ') + '</div>' : '') +
			'</div>' +
			nutritionHtml +
			'<table style="width:100%;border-collapse:collapse;margin-top:16px;"><tr>' +
				'<td style="width:42%;vertical-align:top;padding-right:18px;">' +
					'<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:' + MUTE + ';margin-bottom:6px;">Ingredients' + (currentServings !== r.servings ? ' (scaled to ' + currentServings + ' servings — approximate)' : '') + '</div>' +
					ingredientsHtml +
				'</td>' +
				'<td style="vertical-align:top;">' +
					'<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:' + MUTE + ';margin-bottom:6px;">Method</div>' +
					'<table style="width:100%;border-collapse:collapse;">' + stepsHtml + '</table>' +
				'</td>' +
			'</tr></table>' +
			(r.credit ? '<div style="margin-top:18px;font-size:8.5px;color:#94A3B8;">' + esc(r.credit) + '</div>' : '');
		return wrap;
	}
})();
