/* global jQuery, wp, vanceHubReset */
/**
 * "Reset this section" buttons of the VanceHubClub panel.
 *
 * inc/hub-reset.php prints window.vanceHubReset:
 *   sections: { <section id>: { title: string, defaults: { <setting id>: default } } }
 *   confirm:  text with %s for the section title
 *   done:     text of the notice
 *
 * A click asks for a confirmation, then sets every setting of the section to its
 * default with setting.set( default ). That is Customizer state only. Nothing is
 * saved and nothing changes on the site until the administrator selects Publish.
 *
 * Most controls follow a change of their setting by themselves (text, number,
 * select, checkbox, radio and the colour swatch). Two parts of the page do not:
 *   - the read-only hex field of the colour control: this script writes it;
 *   - the drag list: assets/js/customizer-sortable.js follows its setting.
 */
(function ($) {
	'use strict';

	var data = window.vanceHubReset;
	if (!data || !data.sections || !window.wp || !wp.customize) {
		return;
	}
	var api = wp.customize;
	var NOTICE_CODE = 'vance_hub_reset_done';
	var HEX = /^#[0-9a-fA-F]{6}$/;

	// The colour control (Vance_Customize_HTML5_Color_Control) updates its swatch
	// through the setting link, and its hex field only on an input event. Write
	// both from the value, with the same fallback colour as the PHP render.
	function redrawColour(settingId, value) {
		api.control(settingId, function (control) {
			var $field = control.container ? control.container.find('.vance-color-field') : $();
			if (!$field.length) {
				return;
			}
			var hex = HEX.test(String(value)) ? String(value) : '#434343';
			$field.find('input[type="color"]').val(hex);
			$field.find('.vance-color-hex').val(hex.toUpperCase());
		});
	}

	function showNotice(sectionId) {
		var section = api.section(sectionId);
		var message = String(data.done || '');
		if (section && section.notifications && typeof api.Notification === 'function') {
			if (typeof section.notifications.remove === 'function') {
				section.notifications.remove(NOTICE_CODE);
			}
			section.notifications.add(new api.Notification(NOTICE_CODE, {
				type: 'info',
				message: message,
				dismissible: true
			}));
			return;
		}
		// No notifications on sections in this WordPress: print a notice by hand.
		var $content = section && section.contentContainer ? section.contentContainer : $('#sub-accordion-section-' + sectionId);
		if (!$content.length) {
			return;
		}
		$content.find('.vance-hub-reset-notice').remove();
		var $notice = $('<div class="notice notice-info inline vance-hub-reset-notice" role="status"></div>')
			.append($('<p></p>').text(message));
		var $anchor = $content.find('.customize-section-title').first();
		if ($anchor.length) {
			$anchor.after($notice);
		} else {
			$content.prepend($notice);
		}
	}

	function resetSection(sectionId) {
		var section = data.sections[sectionId];
		if (!section || !section.defaults) {
			return;
		}
		var title = section.title || sectionId;
		// A function as the replacement: a "$" in a title is then plain text.
		var message = String(data.confirm || '').replace('%s', function () {
			return title;
		});
		if (!window.confirm(message)) {
			return;
		}
		$.each(section.defaults, function (settingId, value) {
			api(settingId, function (setting) {
				setting.set(value);
				redrawColour(settingId, value);
			});
		});
		showNotice(sectionId);
	}

	$(document).on('click', '.vance-hub-reset', function (event) {
		event.preventDefault();
		resetSection(String($(this).attr('data-section') || ''));
	});
})(jQuery);
