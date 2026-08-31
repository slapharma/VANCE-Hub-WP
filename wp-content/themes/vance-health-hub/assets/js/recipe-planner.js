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
	var autofillBtn    = document.getElementById('vance-rh-autofill');
	var clearBtn       = document.getElementById('vance-rh-clear');
	var clearModalBtn  = document.getElementById('vance-rh-clear-modal');
	var saveModal      = document.getElementById('vance-rh-savemodal');
	var saveModalClose = document.getElementById('vance-rh-savemodal-close');
	var saveOptCurrent = document.getElementById('vance-rh-saveopt-current');
	var saveOptCurSub  = document.getElementById('vance-rh-saveopt-current-sub');
	var saveCurrentBtn = document.getElementById('vance-rh-save-current');
	var saveNewBtn     = document.getElementById('vance-rh-save-new');
	var saveNewName    = document.getElementById('vance-rh-save-newname');
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

	// --- "Let Vance Create Your Plan" ---------------------------------------

	// Recipe categories are plural for snacks ("snacks") while the planner slot
	// key is singular ("snack"); everything else matches 1:1.
	var SLOT_CATEGORY = { breakfast: 'breakfast', lunch: 'lunch', dinner: 'dinner', snack: 'snacks' };

	function recipesForSlot(slot) {
		var cat = SLOT_CATEGORY[slot] || slot;
		return CFG.recipes.filter(function (r) { return r.category === cat; });
	}

	/**
	 * Fill every empty slot of the week with a random recipe of the right kind.
	 *
	 * Already-filled slots are left alone — this is "finish my plan", not "throw
	 * my plan away". Within a day, it avoids repeating a recipe across slots
	 * where the pool is big enough to allow it, so a day doesn't come back as
	 * the same meal three times.
	 */
	function autofillPlan() {
		var pools = {};
		SLOT_KEYS.forEach(function (slot) { pools[slot] = recipesForSlot(slot); });

		var filled = 0;
		state.days.forEach(function (dayState) {
			var usedToday = {};
			SLOT_KEYS.forEach(function (slot) {
				if (dayState.meals[slot]) {
					usedToday[dayState.meals[slot].slug] = true;
					return;
				}
				var pool = pools[slot];
				if (!pool.length) { return; }
				var fresh = pool.filter(function (r) { return !usedToday[r.slug]; });
				var from = fresh.length ? fresh : pool;
				var recipe = from[Math.floor(Math.random() * from.length)];
				dayState.meals[slot] = {
					slug: recipe.slug,
					name: recipe.name,
					calories: recipe.calories || 0,
					minutes: recipe.minutes || 0
				};
				usedToday[recipe.slug] = true;
				filled++;
			});
		});

		saveState();
		renderPlanner();

		if (!filled) {
			showToast('Your week is already full — clear a slot to swap something in.', 4000);
		} else {
			showToast('Filled ' + filled + ' meal' + (filled === 1 ? '' : 's') + ' for you ✓ Swap anything you like.', 4500);
		}
	}

	if (autofillBtn) {
		autofillBtn.addEventListener('click', function () {
			autofillBtn.disabled = true;
			try { autofillPlan(); } finally { autofillBtn.disabled = false; }
			var planner = document.getElementById('planner');
			if (planner) { planner.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
		});
	}

	// --- "Clear meal plan" ---------------------------------------------------

	function plannedMealCount() {
		var n = 0;
		state.days.forEach(function (dayState) {
			SLOT_KEYS.forEach(function (slot) { if (dayState.meals[slot]) { n++; } });
		});
		return n;
	}

	/**
	 * Empty the whole week and forget the stored working copy.
	 *
	 * Local only: a plan already saved to the dashboard lives in user meta and
	 * is untouched until the user saves over it, so this can't destroy anything
	 * server-side. The plan name is deliberately kept — clearing the meals is
	 * "start this week again", not "throw the whole plan away", and a user who
	 * opened a saved plan to edit it still needs its name to update it.
	 *
	 * @return {boolean} true if the week was actually cleared.
	 */
	function clearPlan() {
		var count = plannedMealCount();
		if (!count) {
			showToast('Your plan is already empty.', 3000);
			return false;
		}
		var msg = 'Clear all ' + count + ' meal' + (count === 1 ? '' : 's') + ' from this week?' +
			'\n\nPlans you have already saved to your dashboard are not affected.';
		if (!window.confirm(msg)) { return false; }

		state.days = emptyDays();
		disarm();
		saveState();
		renderPlanner();
		showToast('Meal plan cleared — your week is empty again.', 3500);
		return true;
	}

	// No scroll-into-view here (unlike autofill): this button sits in the
	// planner head already, and the emptied week is right below it.
	if (clearBtn) { clearBtn.addEventListener('click', clearPlan); }

	// Same action from inside the save dialog. Only step out of the dialog when
	// the week was really cleared — a cancelled confirm leaves it open.
	if (clearModalBtn) {
		clearModalBtn.addEventListener('click', function () {
			if (clearPlan()) { closeSaveModal(); }
		});
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

	// The saved-plan row this session is editing, if the planner was opened via
	// ?plan=<key>. Null means there is nothing to update and the save dialog
	// only offers "save as a new plan".
	var editingKey = (CFG.preloadPlan && CFG.preloadPlan.key) ? CFG.preloadPlan.key : null;

	function openSaveModal() {
		if (!buildPayload().days.length) {
			showToast('Add at least one meal before saving.', 3500);
			return;
		}
		// Anonymous users can't choose between updating and adding — there is
		// nothing saved yet. Skip straight to the register flow.
		if (!CFG.loggedIn) { doSave(); return; }
		if (!saveModal) { doSave(); return; }

		if (saveOptCurrent) {
			saveOptCurrent.hidden = !editingKey;
			if (editingKey && saveOptCurSub && CFG.preloadPlan && CFG.preloadPlan.name) {
				saveOptCurSub.textContent = 'Overwrite "' + CFG.preloadPlan.name + '" with the week below.';
			}
		}
		if (saveNewName) {
			saveNewName.value = (planNameInput && planNameInput.value) || state.name || '';
		}
		saveModal.classList.add('is-open');
		saveModal.setAttribute('aria-hidden', 'false');
		if (saveNewName) { saveNewName.focus(); }
	}

	function closeSaveModal() {
		if (!saveModal) { return; }
		saveModal.classList.remove('is-open');
		saveModal.setAttribute('aria-hidden', 'true');
	}

	if (saveModalClose) { saveModalClose.addEventListener('click', closeSaveModal); }
	if (saveModal) {
		saveModal.addEventListener('click', function (e) { if (e.target === saveModal) { closeSaveModal(); } });
	}
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && saveModal && saveModal.classList.contains('is-open')) { closeSaveModal(); }
	});

	// "Save as a new plan" — the existing append path, with the dialog's name.
	if (saveNewBtn) {
		saveNewBtn.addEventListener('click', function () {
			if (saveNewName && planNameInput) {
				planNameInput.value = saveNewName.value;
				state.name = saveNewName.value;
				saveState();
			}
			closeSaveModal();
			doSave();
		});
	}

	// "Save current plan" — overwrite the row opened for editing, in place.
	if (saveCurrentBtn) {
		saveCurrentBtn.addEventListener('click', function () {
			if (!editingKey) { return; }
			var payload = buildPayload();
			if (!payload.days.length) {
				showToast('Add at least one meal before saving.', 3500);
				return;
			}
			// Keep the plan's existing name unless the planner field has been
			// edited — "update" shouldn't silently rename it.
			if (!payload.name && CFG.preloadPlan) { payload.name = CFG.preloadPlan.name || ''; }

			saveCurrentBtn.disabled = true;
			var fd = new FormData();
			fd.append('action', 'vance_update_tool_entry');
			fd.append('nonce', CFG.dashNonce);
			fd.append('tool', CFG.toolSlug);
			fd.append('id', editingKey);
			fd.append('payload', JSON.stringify(payload));
			fetch(CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (j) {
					saveCurrentBtn.disabled = false;
					if (j && j.success) {
						closeSaveModal();
						showToast('Plan updated ✓', 3500);
					} else {
						showToast((j && j.data && j.data.message) || (typeof j.data === 'string' ? j.data : '') || 'Could not update, please try again.', 4500);
					}
				})
				.catch(function () {
					saveCurrentBtn.disabled = false;
					showToast('Network error, please try again.', 4500);
				});
		});
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

	// The save button now opens the dialog rather than saving straight away;
	// openSaveModal() falls through to doSave() when there is no choice to make
	// (anonymous, or the dialog markup isn't on the page).
	if (saveBtn) { saveBtn.addEventListener('click', openSaveModal); }
	if (planNameInput) {
		planNameInput.addEventListener('input', function () { state.name = planNameInput.value; saveState(); });
	}

	// --- Init ------------------------------------------------------------

	// The dashboard renders this app inside .dash-card, whose hover lift is a
	// CSS transform (page-dashboard.php: `transform: translateY(-2px)`). A
	// transformed ancestor becomes the containing block for its position:fixed
	// descendants, so while the card is hovered — which it always is, the
	// pointer having just clicked a button inside it — the overlays are
	// positioned against the card instead of the viewport: the dialog lands
	// wherever the card happens to be scrolled to, and the scrim covers only
	// the card rather than the page. Re-parenting them to <body> puts them
	// outside any transformed ancestor, on this host page or any other. Their
	// styles are keyed off their own classes, so nothing else changes.
	[ picker, saveModal, toast ].forEach(function (el) {
		if (el && el.parentNode !== document.body) { document.body.appendChild(el); }
	});

	renderPlanner();
	if (CFG.preloadPlan) { saveState(); } // Persist the loaded-for-editing plan as the working copy.
	if (CFG.addSlug) {
		armRecipe(CFG.addSlug);
		// Strip ?add= so a refresh doesn't re-arm it.
		var cleanUrl = window.location.pathname + window.location.search.replace(/[?&]add=[^&]*/, '').replace(/^&/, '?') + '#planner';
		window.history.replaceState(null, '', cleanUrl);
	}
})();
