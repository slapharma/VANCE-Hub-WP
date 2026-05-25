/* global jQuery, wp */
/**
 * Vance sortable-sections Customizer control.
 *
 * Each control instance is a <ul class="vance-sortable-sections"> with one
 * <li class="vance-sortable-item"> per available section. Sibling hidden input
 * <input class="vance-sortable-value"> holds the comma-separated CHECKED section
 * IDs in display order — which is exactly what front-page.php reads.
 *
 * We:
 *   1. Make each <ul> a jQuery UI Sortable.
 *   2. On sort end OR checkbox toggle, recompute the value from currently
 *      checked <li>s in DOM order and push it back through wp.customize.
 */
(function ($) {
	'use strict';

	function recomputeValue($list) {
		var ids = [];
		$list.children('li.vance-sortable-item').each(function () {
			var $li = $(this);
			var checked = $li.find('input.vance-sortable-checkbox').is(':checked');
			if (checked) {
				ids.push($li.data('section-id'));
			}
		});
		return ids.join(',');
	}

	function syncSettingFromControl($list) {
		var controlId = $list.data('control-id');
		if (!controlId || !window.wp || !wp.customize) {
			return;
		}
		var control = wp.customize.control(controlId);
		if (!control) {
			return;
		}
		var newValue = recomputeValue($list);
		// Update the hidden input (visible state) and the underlying setting.
		$list.siblings('input.vance-sortable-value').val(newValue);
		control.setting.set(newValue);
	}

	function visualizeChecked($li) {
		var checked = $li.find('input.vance-sortable-checkbox').is(':checked');
		$li.toggleClass('is-visible', checked).toggleClass('is-hidden', !checked);
	}

	function initList($list) {
		// jQuery UI Sortable
		$list.sortable({
			handle: '.vance-sortable-handle',
			placeholder: 'vance-sortable-placeholder',
			tolerance: 'pointer',
			axis: 'y',
			forcePlaceholderSize: true,
			update: function () {
				syncSettingFromControl($list);
			}
		});

		// Checkbox toggling
		$list.on('change', 'input.vance-sortable-checkbox', function () {
			visualizeChecked($(this).closest('li.vance-sortable-item'));
			syncSettingFromControl($list);
		});
	}

	$(document).ready(function () {
		$('.vance-sortable-sections').each(function () {
			initList($(this));
		});
	});

	// In case the control is added after initial DOM ready (rare in Customizer).
	if (window.wp && wp.customize) {
		wp.customize.bind('ready', function () {
			$('.vance-sortable-sections').each(function () {
				if (!$(this).hasClass('ui-sortable')) {
					initList($(this));
				}
			});
		});
	}
})(jQuery);
