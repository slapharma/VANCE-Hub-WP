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
	// The matches panel is prone to going stale the moment a member ticks
	// another box (it was rendered from whatever the folder held at page
	// load), so every save response carries fresh card markup and this
	// swaps it in — updated whether the panel is open or not, so it's
	// current the next time someone opens it.
	function vanceRenderFolderMatches($root, cardsHtml) {
		var $panel = $root.find('#vance-discount-folder-matches');
		if (!$panel.length) { return; }
		if (cardsHtml) {
			$panel.html('<div class="vance-discount-grid">' + cardsHtml + '</div>');
		} else {
			$panel.html('<p class="vance-discount-folder-matches-empty">Tick a few boxes below and matching schemes will show up here.</p>');
		}
	}

	function vanceSaveDiscountFolder($root) {
		var state = vanceDiscountFolderState($root);
		var $status = $('#vance-discount-folder-status');

		$.post(vanceDiscounts.ajaxUrl, {
			action: 'vance_save_access_folder',
			nonce: $root.attr('data-nonce'),
			signals: state.signals,
			region: state.region
		}).done(function (res) {
			if (res && res.success) {
				$status.text('Saved, likely eligible for ' + res.data.likely_count + ' schemes.');
				$root.find('#vance-discount-match-count').text(res.data.likely_count);
				vanceRenderFolderMatches($root, res.data.likely_cards);
			} else {
				$status.text('Could not save, try again.');
			}
		}).fail(function () {
			$status.text('Could not save, try again.');
		});
	}

	$(document).on('click', '#vance-discount-view-matches', function () {
		var $btn = $(this);
		var $panel = $btn.closest('.vance-discount-dashboard').find('#vance-discount-folder-matches');
		var open = $panel.prop('hidden');
		$panel.prop('hidden', !open);
		$btn.attr('aria-expanded', open ? 'true' : 'false');
		if (open) {
			$panel.get(0).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}
	});

	// Ticked-count line updates instantly from the DOM — no need to wait on
	// the save round trip just to tell a member how many boxes they've ticked.
	function vanceUpdateFolderProgress($root) {
		var $progress = $root.find('#vance-discount-folder-progress');
		if (!$progress.length) { return; }
		var total = parseInt($progress.attr('data-total'), 10) || 0;
		var ticked = $root.find('.vance-discount-folder-toggle.is-on').length;
		$progress.text(ticked + ' of ' + total + ' ticked');
	}

	$(document).on('click', '.vance-discount-folder-toggle', function () {
		var $btn = $(this);
		var on = !$btn.hasClass('is-on');
		var $root = $btn.closest('.vance-discount-dashboard');
		$btn.toggleClass('is-on', on).attr('aria-checked', on ? 'true' : 'false');
		vanceUpdateFolderProgress($root);
		vanceSaveDiscountFolder($root);
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
	// file — no /AcroForm), so this builds a fresh two-page replica of the
	// whole declaration with html2pdf.js (same library/version the recipe
	// meal-plan export already loads): page 1 is Part 1, left blank for the
	// supplier to complete, page 2 is Part 2 with the member's own details
	// filled in.
	//
	// It builds BOTH parts on purpose. An earlier version emitted Part 2
	// alone, which left the member handing a shop half a form — and made the
	// official Part 2 wording ("the goods and/or services detailed overleaf")
	// point at a page that wasn't there, so the text had to be reworded away
	// from HMRC's. Reproducing both pages restores the exact wording and
	// gives the supplier the section they are required to keep.
	//
	// Body copy below is HMRC's own (form dated March 2015, Crown copyright,
	// OGL v3.0) — keep it verbatim. Rewriting a legal declaration to sound
	// nicer is how a zero-rating claim gets refused at the till.

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

			// Layout is built from tables, not flex: html2canvas rasterises
			// tables predictably and drops flex gaps often enough to matter on
			// something a member prints and hands to a shop.
			var vatDotted = 'border-bottom:1px dotted #64748B;';
			function vatLine(text) {
				return '<div style="' + vatDotted + 'min-height:18px;padding:1px 0 0;font-size:12px;line-height:1.4;">' + (text || '&nbsp;') + '</div>';
			}
			function vatLines(n) {
				var out = '';
				for (var i = 0; i < n; i++) { out += vatLine(''); }
				return out;
			}
			// Prefilled values keep the ruled lines: the member may need to
			// correct one by hand, and a bare paragraph gives them nowhere to
			// write. Always pads out to `min` so the block keeps its shape
			// when a field was left empty.
			function vatValueLines(value, min) {
				var rows = value ? vanceEscHtml(value).split(/\n+/) : [];
				var out = '';
				for (var i = 0; i < rows.length; i++) { out += vatLine(rows[i]); }
				if (rows.length < min) { out += vatLines(min - rows.length); }
				return out;
			}
			function vatTick(label) {
				return '<tr>' +
					'<td style="width:20px;padding:0 8px 11px 0;vertical-align:top;">' +
						'<div style="width:13px;height:13px;border:1px solid #0A1929;margin-top:3px;"></div>' +
					'</td>' +
					'<td style="padding:0 0 11px;font-size:12px;line-height:1.45;vertical-align:top;">' + label + '</td>' +
				'</tr>';
			}
			var vatSignDate =
				'<table style="width:100%;border-collapse:collapse;margin-top:26px;font-size:12px;"><tr>' +
					'<td style="width:62%;padding-right:26px;vertical-align:bottom;">Signed' + vatLine('') + '</td>' +
					'<td style="vertical-align:bottom;">Date' + vatLine('') + '</td>' +
				'</tr></table>';

			var vatH1 = 'font-size:15px;font-weight:bold;margin:0 0 14px;line-height:1.35;';
			var vatH2 = 'font-size:14px;font-weight:bold;margin:0 0 6px;';
			var vatH3 = 'font-size:12px;font-weight:bold;margin:0 0 6px;';
			var vatP  = 'font-size:11px;line-height:1.5;margin:0 0 8px;';

			var vatPage1 =
				'<div style="' + vatH1 + '">VAT reliefs for disabled people &ndash; eligibility declaration by a disabled person</div>' +
				'<div style="' + vatH2 + '">Part 1. Supplier</div>' +
				'<div style="' + vatH3 + '">Note to supplier</div>' +
				'<p style="' + vatP + '">The production of this declaration does not automatically justify the zero rating of your supply. You must ensure that the goods and/or services you are supplying qualify for zero rating. Please consult Notice 701/7 VAT reliefs for disabled people, before applying VAT relief to your supplies.</p>' +
				'<p style="' + vatP + '">You must keep this declaration with your records for production to your VAT officer as required. Please do not return it to the customer or send it to HM Revenue and Customs (HMRC).</p>' +
				'<div style="font-size:12px;margin-top:14px;">I (full name)</div>' + vatLine('') +
				'<div style="font-size:12px;margin-top:8px;">of (company name and address)</div>' + vatLines(4) +
				'<p style="' + vatP + 'margin-top:14px;">I am supplying the following goods and/or services to the disabled person named overleaf. Please tick the appropriate box and give details of the goods and/or services in the space provided:</p>' +
				'<table style="width:100%;border-collapse:collapse;margin-top:4px;"><tr>' +
					'<td style="width:47%;vertical-align:top;padding-right:20px;">' +
						'<table style="width:100%;border-collapse:collapse;">' +
							vatTick('Goods which are being supplied for the customer&rsquo;s personal use') +
							vatTick('Services of adapting goods to suit the needs of the customer') +
							vatTick('Services of installation, repair or maintenance') +
							vatTick('Alterations to a private residence') +
							vatTick('Monitoring a personal alarm call system for the personal use of the disabled person') +
						'</table>' +
					'</td>' +
					'<td style="vertical-align:top;">' +
						'<div style="' + vatH3 + '">Insert details</div>' +
						vatLines(7) +
					'</td>' +
				'</tr></table>' +
				vatSignDate;

			var vatPage2 =
				'<div style="' + vatH2 + '">Part 2. Customer&rsquo;s declaration</div>' +
				'<div style="' + vatH3 + '">Note to customer</div>' +
				'<p style="' + vatP + '">You should complete this declaration if you are &lsquo;chronically sick or disabled&rsquo; and the goods or services are for your own personal or domestic use. A family member or carer can complete this on your behalf if you wish.</p>' +
				'<p style="' + vatP + '">You can find out more from the Helpsheets on the GOV.UK website or by telephoning the VAT Disabled Reliefs Helpline on Telephone: 0300 123 1073. HMRC staff cannot advise whether or not an individual is chronically sick or disabled.</p>' +
				'<p style="' + vatP + '">A person is &lsquo;chronically sick or disabled&rsquo; if he or she is a person:</p>' +
				'<ul style="' + vatP + 'padding-left:18px;margin-top:0;">' +
					'<li style="margin-bottom:4px;">with a physical or mental impairment which has a long term and substantial adverse effect upon his or her ability to carry out everyday activities</li>' +
					'<li>with a condition which the medical profession treats as a chronic sickness</li>' +
				'</ul>' +
				'<p style="' + vatP + '">It does not include an elderly person who is not disabled or chronically sick or any person who is only temporarily disabled or incapacitated, such as with a broken limb.</p>' +
				'<p style="' + vatP + '">If you are unsure, you should seek guidance from your GP or other medical professional.</p>' +
				'<p style="' + vatP + '"><strong>Please give this completed form back to the supplier. They will keep it with their VAT records. Please do not send it to HMRC.</strong></p>' +
				'<div style="font-size:12px;margin-top:14px;">I (full name)</div>' + vatValueLines(name, 1) +
				'<div style="font-size:12px;margin-top:8px;">of (address)</div>' + vatValueLines(address, 4) +
				'<div style="font-size:12px;margin-top:8px;">declare that I have the following disability or chronic sickness</div>' + vatValueLines(condition, 3) +
				'<p style="' + vatP + 'margin-top:12px;">I am receiving the goods and/or services detailed overleaf, which are being supplied to me for domestic or my personal use and I claim relief from VAT.</p>' +
				vatSignDate +
				'<p style="font-size:9px;color:#64748B;line-height:1.45;margin:26px 0 0;">Reproduces the HMRC form &ldquo;VAT reliefs for disabled people &ndash; eligibility declaration by a disabled person&rdquo; (March 2015). Crown copyright, Open Government Licence v3.0. Prepared on vancehealthhub.co.uk; not issued by HMRC.</p>';

			var $el = $('<div>').css({ fontFamily: 'Arial, Helvetica, sans-serif', color: '#0A1929' }).html(
				'<div style="padding:16px 18px;">' + vatPage1 + '</div>' +
				'<div class="html2pdf__page-break"></div>' +
				'<div style="padding:16px 18px;">' + vatPage2 + '</div>'
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
				// 'legacy' is what honours the .html2pdf__page-break divider
				// between Part 1 and Part 2 — without it the two parts run
				// together and break wherever the page happens to fill.
				pagebreak: { mode: ['css', 'legacy'] },
				html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF', logging: false, scrollX: 0, scrollY: 0 },
				jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
			}).from($el[0]).save().then(cleanup, function () {
				cleanup();
				alert('Could not build the PDF, please try again.');
			});
		});
	}

}(jQuery));
