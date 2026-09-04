/**
 * IBD Discounts & Freebies — client-side filtering, Save toggle, tier-2 popup.
 *
 * Region + text search filtering only (category stays server-rendered via
 * `?cat=` for the no-JS case — see template-parts/discount-directory.php).
 * The Save button's AJAX action ('vance_toggle_discount') is registered by
 * inc/discount-dashboard.php, which does not exist yet (plan §10 step 5);
 * until then this degrades to an optimistic UI flip that reverts on the
 * "0" WordPress sends back for an unregistered ajax action, rather than
 * erroring.
 *
 * Tier-1 apply buttons are plain links for now (data-vance-discount-modal is
 * set on the markup but not yet intercepted) — the in-hub modal with its
 * origin strip is plan §10 step 6.
 */
(function ($) {
	'use strict';

	function filterCards() {
		var region = $('#vance-discount-region').val() || '';
		var search = ($('#vance-discount-search').val() || '').toLowerCase().trim();
		var visible = 0;

		$('.vance-discount-card').each(function () {
			var $card = $(this);
			var cardRegions = ' ' + ($card.attr('data-region') || '') + ' ';
			var cardSearch = $card.attr('data-search') || '';

			var regionOk = !region || cardRegions.indexOf(' ' + region + ' ') !== -1;
			var searchOk = !search || cardSearch.indexOf(search) !== -1;

			$card.toggle(regionOk && searchOk);
			if (regionOk && searchOk) { visible++; }
		});

		$('#vance-discount-empty').prop('hidden', visible !== 0);
	}

	$(document).on('change', '#vance-discount-region', filterCards);
	$(document).on('input', '#vance-discount-search', filterCards);

	$(document).on('click', '#vance-discount-clear-filters', function (e) {
		e.preventDefault();
		$('#vance-discount-region').val('');
		$('#vance-discount-search').val('');
		filterCards();
	});

	// Tier-2: open in a named popup rather than a plain new tab, so a repeat
	// click reuses the same window instead of stacking tabs.
	$(document).on('click', '[data-vance-discount-popup]', function (e) {
		e.preventDefault();
		window.open($(this).attr('href'), 'vance-discount-apply', 'popup,width=900,height=800');
	});

	// Save toggle — mirrors single.php's vaSetSaved()/click-handler pattern,
	// scoped to .vance-discount-save so it never touches an article's own
	// .vance-save-btn instances on the same page (there are none today, but
	// the featured-card renderer coming in step 4 may put both on one page).
	$(document).on('click', '.vance-discount-save', function (e) {
		e.preventDefault();
		var $btn = $(this);

		if ($btn.attr('data-logged-in') !== '1') {
			if (window.VanceRegisterModal && typeof window.VanceRegisterModal.open === 'function') {
				window.VanceRegisterModal.open({
					tool: 'discounts',
					payload: { post_id: $btn.attr('data-post-id') }
				});
			}
			return;
		}

		var wasSaved = $btn.hasClass('is-saved');
		var nowSaved = !wasSaved;

		// Optimistic flip, reverted in the .fail()/non-success branch below.
		$btn.toggleClass('is-saved', nowSaved)
			.attr('aria-pressed', nowSaved ? 'true' : 'false')
			.find('.va-save-icon').html(nowSaved ? '&#9733;' : '&#9734;');
		$btn.find('.va-save-text').text(nowSaved ? 'Saved' : 'Save');

		$.post(vanceDiscounts.ajaxUrl, {
			action: 'vance_toggle_discount',
			post_id: $btn.attr('data-post-id'),
			nonce: $btn.attr('data-nonce')
		}).done(function (res) {
			if (!res || !res.success) {
				// Handler not registered yet (plan step 5), or a real failure —
				// either way, revert rather than show a state the server never
				// confirmed.
				$btn.toggleClass('is-saved', wasSaved)
					.attr('aria-pressed', wasSaved ? 'true' : 'false')
					.find('.va-save-icon').html(wasSaved ? '&#9733;' : '&#9734;');
				$btn.find('.va-save-text').text(wasSaved ? 'Saved' : 'Save');
			}
		}).fail(function () {
			$btn.toggleClass('is-saved', wasSaved)
				.attr('aria-pressed', wasSaved ? 'true' : 'false')
				.find('.va-save-icon').html(wasSaved ? '&#9733;' : '&#9734;');
			$btn.find('.va-save-text').text(wasSaved ? 'Saved' : 'Save');
		});
	});

	// ---- Dashboard: Access Folder + saved-scheme status (plan §10 step 5) ----

	function vanceDiscountFolderState($root) {
		var signals = [];
		$root.find('.vance-discount-folder-toggle.is-on').each(function () {
			signals.push($(this).attr('data-signal'));
		});
		return {
			signals: signals,
			region: $root.find('#vance-discount-folder-region').val() || ''
		};
	}

	// The whole folder is sent on every toggle (inc/discount-dashboard.php's
	// vance_save_access_folder expects the full current state, not a diff —
	// see that file's header for why), so one function drives every trigger.
	function vanceSaveDiscountFolder($root) {
		var state = vanceDiscountFolderState($root);
		var $status = $('#vance-discount-folder-status');

		$.post(vanceDiscounts.ajaxUrl, {
			action: 'vance_save_access_folder',
			nonce: $root.attr('data-nonce'),
			signals: state.signals,
			region: state.region
		}).done(function (res) {
			$status.text((res && res.success)
				? 'Saved — likely eligible for ' + res.data.likely_count + ' schemes.'
				: 'Could not save — try again.');
		}).fail(function () {
			$status.text('Could not save — try again.');
		});
	}

	$(document).on('click', '.vance-discount-folder-toggle', function () {
		var $btn = $(this);
		var on = !$btn.hasClass('is-on');
		$btn.toggleClass('is-on', on).attr('aria-checked', on ? 'true' : 'false');
		vanceSaveDiscountFolder($btn.closest('.vance-discount-dashboard'));
	});

	$(document).on('change', '#vance-discount-folder-region', function () {
		vanceSaveDiscountFolder($(this).closest('.vance-discount-dashboard'));
	});

	$(document).on('change', '.vance-discount-status-select', function () {
		var $sel = $(this);
		$.post(vanceDiscounts.ajaxUrl, {
			action: 'vance_set_discount_status',
			nonce: $sel.closest('.vance-discount-dashboard').attr('data-nonce'),
			post_id: $sel.attr('data-post-id'),
			status: $sel.val()
		});
	});

}(jQuery));
