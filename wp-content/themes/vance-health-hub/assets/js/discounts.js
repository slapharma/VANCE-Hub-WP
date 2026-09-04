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
				? 'Saved, likely eligible for ' + res.data.likely_count + ' schemes.'
				: 'Could not save, try again.');
		}).fail(function () {
			$status.text('Could not save, try again.');
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

	// ---- VAT relief declaration (plan §10 step 7) --------------------------
	//
	// The HMRC PDF has no fillable fields at all (confirmed by inspecting the
	// file), and Part 1 is the supplier's own section — this only ever builds
	// Part 2, the customer's declaration, as a fresh PDF via html2pdf.js (same
	// library/version the recipe meal-plan export already loads).

	var $vatModal = $('#vance-vat-modal');
	if ($vatModal.length) {
		function vanceEscHtml(s) {
			return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		}

		function openVatModal($trigger) {
			$('#vance-vat-blank-link').attr('href', $trigger.attr('href') || '#');
			var $name = $('#vance-vat-name');
			if (vanceDiscounts.displayName && !$name.val()) { $name.val(vanceDiscounts.displayName); }
			$vatModal.prop('hidden', false);
		}
		function closeVatModal() { $vatModal.prop('hidden', true); }

		$(document).on('click', '[data-vance-vat-declaration]', function (e) {
			e.preventDefault();
			openVatModal($(this));
		});
		$(document).on('click', '#vance-vat-modal-close', closeVatModal);
		$vatModal.on('click', function (e) { if (e.target === this) { closeVatModal(); } });
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && !$vatModal.prop('hidden')) { closeVatModal(); }
		});

		$('#vance-vat-generate').on('click', function () {
			if (typeof window.html2pdf === 'undefined') {
				alert('PDF export is still loading, please try again in a moment.');
				return;
			}

			var name = $('#vance-vat-name').val().trim();
			var address = $('#vance-vat-address').val().trim();
			var condition = $('#vance-vat-condition').val().trim();
			var addressHtml = address ? vanceEscHtml(address).replace(/\n/g, '<br>') : '……………………………';

			var $el = $('<div>').css({ fontFamily: 'Arial, sans-serif', padding: '32px', color: '#0A1929' }).html(
				'<h2 style="font-size:16px;margin:0 0 4px;">VAT reliefs for disabled people</h2>' +
				'<p style="font-size:13px;color:#475569;margin:0 0 20px;">Eligibility declaration by a disabled person, Part 2, Customer\'s declaration</p>' +
				'<p style="font-size:11px;color:#94A3B8;margin:0 0 24px;">Give this completed section to your supplier, they keep it with their VAT records. Part 1 (overleaf on the official form) is completed by the supplier, not you.</p>' +
				'<p style="font-size:13px;line-height:1.7;"><strong>I (full name)</strong> ' + (vanceEscHtml(name) || '……………………………') + '</p>' +
				'<p style="font-size:13px;line-height:1.7;"><strong>of (address)</strong><br>' + addressHtml + '</p>' +
				'<p style="font-size:13px;line-height:1.7;margin-top:16px;"><strong>I declare that I have the following disability or chronic sickness:</strong><br>' + (vanceEscHtml(condition) || '……………………………') + '</p>' +
				'<p style="font-size:13px;line-height:1.7;margin-top:16px;">I am receiving the goods and/or services detailed by the supplier, which are being supplied to me for domestic or my personal use, and I claim relief from VAT.</p>' +
				'<div style="display:flex;gap:40px;margin-top:56px;font-size:13px;">' +
					'<div style="flex:1;border-top:1px solid #94A3B8;padding-top:4px;">Signed</div>' +
					'<div style="flex:1;border-top:1px solid #94A3B8;padding-top:4px;">Date</div>' +
				'</div>'
			);

			// Same offscreen-wrapper pattern as recipe-single.js's PDF export:
			// the wrapper carries the offset, never the element html2pdf reads,
			// or html2canvas measures a 0-height clone and rasterises a blank
			// page with no error thrown anywhere.
			var $holder = $('<div>').css({ position: 'absolute', left: '-10000px', top: '0', width: '794px' }).append($el);
			$('body').append($holder);

			var $btn = $('#vance-vat-generate');
			var label = $btn.text();
			$btn.prop('disabled', true).text('Building PDF…');
			var cleanup = function () {
				$holder.remove();
				$btn.prop('disabled', false).text(label);
			};

			window.html2pdf().set({
				margin: 10,
				filename: 'vat-relief-declaration.pdf',
				image: { type: 'jpeg', quality: 0.95 },
				html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF', logging: false, scrollX: 0, scrollY: 0 },
				jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
			}).from($el[0]).save().then(cleanup, function () {
				cleanup();
				alert('Could not build the PDF, please try again.');
			});
		});
	}

}(jQuery));
