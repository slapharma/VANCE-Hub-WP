/**
 * Gastro Recipes & Meal Planner — vanilla JS, no build step.
 *
 * Drives the recipe grid filter/search, the 7x4 weekly planner (click an
 * empty slot -> picker modal, or "+" on a recipe card -> click any slot to
 * place it directly), localStorage persistence between visits, and the save
 * flow (anonymous -> register modal, logged-in -> AJAX), mirroring the exact
 * save contract inc/tool-page-shell.php's doSave() used for the old iframe
 * tool, so page-dashboard.php needed no server-side changes.
 *
 * Config: window.vanceRecipePlanner (wp_localize_script(), see
 * inc/recipe-frontend.php's vance_recipe_planner_script_config()).
 */
(function () {
	'use strict';

	var CFG = window.vanceRecipePlanner;
	if (!CFG) { return; }

	var STORAGE_KEY = 'vance.mealPlan.v2';
	var SLOT_KEYS = ['breakfast', 'lunch', 'dinner', 'snack'];

	// --- State -----------------------------------------------------------

	function emptyDays() {
		return CFG.days.map(function (day) {
			return { day: day, meals: { breakfast: null, lunch: null, dinner: null, snack: null } };
		});
	}

	function loadState() {
		if (CFG.preloadPlan) {
			return { name: CFG.preloadPlan.name || '', days: CFG.preloadPlan.days };
		}
		try {
			var raw = window.localStorage.getItem(STORAGE_KEY);
			if (raw) {
				var parsed = JSON.parse(raw);
				if (parsed && Array.isArray(parsed.days)) { return parsed; }
			}
		} catch (e) { /* localStorage unavailable or corrupt — start fresh */ }
		return { name: '', days: emptyDays() };
	}

	var state = loadState();

	function saveState() {
		try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* quota/private mode — state still works this session */ }
	}

	// --- DOM refs ----------------------------------------------------------

	var grid          = document.getElementById('vance-rh-grid');
	var searchInput    = document.getElementById('vance-rh-search');
	var chips          = document.querySelectorAll('.vance-rh-chip');
	var planNameInput  = document.getElementById('vance-rh-plan-name');
	var saveBtn        = document.getElementById('vance-rh-save');
	var totalMealsEl   = document.getElementById('vance-rh-total-meals');
	var totalKcalEl    = document.getElementById('vance-rh-total-kcal');
	var armedBar       = document.getElementById('vance-rh-armed');
	var armedText      = document.getElementById('vance-rh-armed-text');
	var armedCancel    = document.getElementById('vance-rh-armed-cancel');
	var picker         = document.getElementById('vance-rh-picker');
	var pickerTitle    = document.getElementById('vance-rh-picker-title');
	var pickerSearch   = document.getElementById('vance-rh-picker-search');
	var pickerList     = document.getElementById('vance-rh-picker-list');
	var pickerClose    = document.getElementById('vance-rh-picker-close');
	var toast          = document.getElementById('vance-rh-toast');

	var armedSlug = null; // Recipe slug waiting to be dropped into the next slot clicked.
	var pickerTarget = null; // { day, slot } the picker modal is filling.

	if (planNameInput) { planNameInput.value = state.name || ''; }

	// --- Toast ---------------------------------------------------------------

	function showToast(msg, ms) {
		if (!toast) { return; }
		toast.textContent = msg;
		toast.classList.add('is-visible');
		clearTimeout(showToast._t);
		showToast._t = setTimeout(function () { toast.classList.remove('is-visible'); }, ms || 3500);
	}

	// --- Recipe lookup -------------------------------------------------------

	var recipesBySlug = {};
	CFG.recipes.forEach(function (r) { recipesBySlug[r.slug] = r; });

	// --- Planner rendering -----------------------------------------------

	function renderPlanner() {
		var totalMeals = 0;
		var totalKcal = 0;

		state.days.forEach(function (dayState) {
			var dayEl = document.querySelector('.vance-rh-day[data-day="' + cssEscape(dayState.day) + '"]');
			if (!dayEl) { return; }
			var dayKcal = 0;
			var dayMeals = 0;

			SLOT_KEYS.forEach(function (slot) {
				var meal = dayState.meals[slot];
				var slotEl = dayEl.querySelector('.vance-rh-slot[data-slot="' + slot + '"] .vance-rh-slot-body');
				if (!slotEl) { return; }

				if (meal) {
					dayKcal += meal.calories || 0;
					dayMeals++;
					totalKcal += meal.calories || 0;
					totalMeals++;
					slotEl.innerHTML =
						'<div class="vance-rh-slot-filled">' +
							'<div><div class="vance-rh-slot-meal-name">' + escapeHtml(meal.name) + '</div>' +
							'<div class="vance-rh-slot-meal-facts">' + (meal.calories ? meal.calories + ' kcal' : '') + '</div></div>' +
							'<button type="button" class="vance-rh-slot-remove" data-remove-day="' + escapeAttr(dayState.day) + '" data-remove-slot="' + slot + '">' + (CFG.i18nRemove || 'Remove') + '</button>' +
						'</div>';
				} else {
					slotEl.innerHTML = '<button type="button" class="vance-rh-slot-add" data-empty-day="' + escapeAttr(dayState.day) + '" data-empty-slot="' + slot + '">+ Add</button>';
				}
			});

			var kcalEl = dayEl.querySelector('[data-day-kcal]');
			if (kcalEl) { kcalEl.textContent = dayMeals ? (dayKcal + ' kcal') : ''; }
		});

		if (totalMealsEl) { totalMealsEl.textContent = String(totalMeals); }
		if (totalKcalEl) { totalKcalEl.textContent = String(totalKcal); }
	}

	function cssEscape(s) { return String(s).replace(/"/g, '\\"'); }
	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }

	function placeMeal(day, slot, slug) {
		var recipe = recipesBySlug[slug];
		if (!recipe) { return; }
		var dayState = state.days.filter(function (d) { return d.day === day; })[0];
		if (!dayState) { return; }
		dayState.meals[slot] = {
			slug: recipe.slug,
			name: recipe.name,
			calories: recipe.calories || 0,
			minutes: recipe.minutes || 0
		};
		saveState();
		renderPlanner();
	}

	function removeMeal(day, slot) {
		var dayState = state.days.filter(function (d) { return d.day === day; })[0];
		if (!dayState) { return; }
		dayState.meals[slot] = null;
		saveState();
		renderPlanner();
	}

	// --- Arming a recipe from a card's "+" button (or ?add=) -----------------

	function armRecipe(slug) {
		var recipe = recipesBySlug[slug];
		if (!recipe) { return; }
		armedSlug = slug;
		if (armedText) { armedText.textContent = 'Adding "' + recipe.name + '" — click an empty slot below to place it.'; }
		if (armedBar) { armedBar.classList.add('is-visible'); }
		var planner = document.getElementById('planner');
		if (planner) { planner.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
	}

	function disarm() {
		armedSlug = null;
		if (armedBar) { armedBar.classList.remove('is-visible'); }
	}

	if (armedCancel) { armedCancel.addEventListener('click', disarm); }

	// --- Picker modal ------------------------------------------------------

	function renderPickerList(filter) {
		if (!pickerList) { return; }
		var q = (filter || '').trim().toLowerCase();
		var items = CFG.recipes.filter(function (r) { return !q || r.name.toLowerCase().indexOf(q) !== -1; });
		pickerList.innerHTML = items.map(function (r) {
			return '<button type="button" class="vance-rh-picker-item" data-pick-slug="' + escapeAttr(r.slug) + '">' +
				'<span class="vance-rh-picker-thumb" style="background-image:url(\'' + escapeAttr(r.image) + '\');"></span>' +
				'<span><span class="vance-rh-picker-name">' + escapeHtml(r.name) + '</span><br>' +
				'<span class="vance-rh-picker-facts">' + (r.calories ? r.calories + ' kcal' : '') + '</span></span>' +
				'</button>';
		}).join('');
	}

	function openPicker(day, slot) {
		pickerTarget = { day: day, slot: slot };
		if (pickerTitle) { pickerTitle.textContent = 'Choose a recipe — ' + day + ', ' + slot.charAt(0).toUpperCase() + slot.slice(1); }
		if (pickerSearch) { pickerSearch.value = ''; }
		renderPickerList('');
		if (picker) { picker.classList.add('is-open'); picker.setAttribute('aria-hidden', 'false'); }
	}

	function closePicker() {
		pickerTarget = null;
		if (picker) { picker.classList.remove('is-open'); picker.setAttribute('aria-hidden', 'true'); }
	}

	if (pickerClose) { pickerClose.addEventListener('click', closePicker); }
	if (picker) {
		picker.addEventListener('click', function (e) { if (e.target === picker) { closePicker(); } });
	}
	if (pickerSearch) {
		pickerSearch.addEventListener('input', function () { renderPickerList(pickerSearch.value); });
	}
	if (pickerList) {
		pickerList.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-pick-slug]');
			if (!btn || !pickerTarget) { return; }
			placeMeal(pickerTarget.day, pickerTarget.slot, btn.getAttribute('data-pick-slug'));
			closePicker();
		});
	}

	// --- Slot clicks (empty "+ Add" and filled "Remove") --------------------

	var daysContainer = document.getElementById('vance-rh-days');
	if (daysContainer) {
		daysContainer.addEventListener('click', function (e) {
			var addBtn = e.target.closest('[data-empty-day]');
			if (addBtn) {
				var day = addBtn.getAttribute('data-empty-day');
				var slot = addBtn.getAttribute('data-empty-slot');
				if (armedSlug) {
					placeMeal(day, slot, armedSlug);
					disarm();
				} else {
					openPicker(day, slot);
				}
				return;
			}
			var removeBtn = e.target.closest('[data-remove-day]');
			if (removeBtn) {
				removeMeal(removeBtn.getAttribute('data-remove-day'), removeBtn.getAttribute('data-remove-slot'));
			}
		});
	}

	// --- Recipe grid: quick-add, search, category filter --------------------

	if (grid) {
		grid.addEventListener('click', function (e) {
			var addBtn = e.target.closest('[data-quick-add]');
			if (!addBtn) { return; }
			e.preventDefault();
			armRecipe(addBtn.getAttribute('data-quick-add'));
		});
	}

	function applyGridFilter(category, query) {
		if (!grid) { return; }
		var q = (query || '').trim().toLowerCase();
		Array.prototype.forEach.call(grid.querySelectorAll('.vance-rh-card'), function (card) {
			var matchesCat = !category || card.getAttribute('data-recipe-category') === category;
			var matchesQuery = !q || card.getAttribute('data-recipe-name').indexOf(q) !== -1;
			card.style.display = (matchesCat && matchesQuery) ? '' : 'none';
		});
	}

	var activeCategory = '';
	Array.prototype.forEach.call(chips, function (chip) {
		chip.addEventListener('click', function (e) {
			e.preventDefault();
			var url = new URL(chip.href, window.location.href);
			activeCategory = url.searchParams.get('cat') || '';
			Array.prototype.forEach.call(chips, function (c) { c.classList.toggle('is-active', c === chip); });
			applyGridFilter(activeCategory, searchInput ? searchInput.value : '');
			var newUrl = window.location.pathname + (activeCategory ? '?cat=' + encodeURIComponent(activeCategory) : '') + '#recipes';
			window.history.replaceState(null, '', newUrl);
		});
	});
	if (searchInput) {
		searchInput.addEventListener('input', function () { applyGridFilter(activeCategory, searchInput.value); });
	}

	// --- Save ----------------------------------------------------------------

	function buildPayload() {
		var days = [];
		var totalMeals = 0;
		var totalKcal = 0;

		state.days.forEach(function (dayState) {
			var meals = [];
			SLOT_KEYS.forEach(function (slot) {
				var m = dayState.meals[slot];
				if (!m) { return; }
				meals.push({ slot: slot, slug: m.slug, name: m.name, calories: m.calories, minutes: m.minutes });
				totalMeals++;
				totalKcal += m.calories || 0;
			});
			if (meals.length) { days.push({ day: dayState.day, meals: meals }); }
		});

		return {
			kind: 'meal-plan',
			version: 4,
			name: planNameInput ? planNameInput.value : (state.name || ''),
			days: days,
			totals: { days: days.length, meals: totalMeals, calories: totalKcal },
			capturedAt: new Date().toISOString()
		};
	}

	function doSave() {
		var payload = buildPayload();
		if (!payload.days.length) {
			showToast('Add at least one meal before saving.', 3500);
			return;
		}

		if (!CFG.loggedIn) {
			if (window.VanceRegisterModal && typeof window.VanceRegisterModal.open === 'function') {
				window.VanceRegisterModal.open({
					tool: CFG.toolSlug,
					payload: payload,
					onSuccess: function (resp) {
						showToast('Account created, opening your dashboard…', 4000);
						setTimeout(function () {
							window.location.href = (resp && resp.redirect) || CFG.dashboardUrl;
						}, 600);
					}
				});
				showToast('Create your free account to save this plan.', 4000);
			} else {
				window.location.href = '/register/?from_tool=' + encodeURIComponent(CFG.toolSlug);
			}
			return;
		}

		if (saveBtn) { saveBtn.disabled = true; }
		var fd = new FormData();
		fd.append('action', 'vance_save_tool_result');
		fd.append('nonce', CFG.nonce);
		fd.append('tool', CFG.toolSlug);
		fd.append('payload', JSON.stringify(payload));
		fetch(CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (saveBtn) { saveBtn.disabled = false; }
				if (j && j.success) {
					showToast('Saved to your dashboard ✓', 3500);
				} else {
					showToast((j && j.data && j.data.message) || 'Could not save, please try again.', 4500);
				}
			})
			.catch(function () {
				if (saveBtn) { saveBtn.disabled = false; }
				showToast('Network error, please try again.', 4500);
			});
	}

	if (saveBtn) { saveBtn.addEventListener('click', doSave); }
	if (planNameInput) {
		planNameInput.addEventListener('input', function () { state.name = planNameInput.value; saveState(); });
	}

	// --- Init ------------------------------------------------------------

	renderPlanner();
	if (CFG.preloadPlan) { saveState(); } // Persist the loaded-for-editing plan as the working copy.
	if (CFG.addSlug) {
		armRecipe(CFG.addSlug);
		// Strip ?add= so a refresh doesn't re-arm it.
		var cleanUrl = window.location.pathname + window.location.search.replace(/[?&]add=[^&]*/, '').replace(/^&/, '?') + '#planner';
		window.history.replaceState(null, '', cleanUrl);
	}
})();
