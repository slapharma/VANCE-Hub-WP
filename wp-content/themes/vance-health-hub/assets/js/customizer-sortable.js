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

	/**
	 * Setting -> list. Code that sets the setting (for example the "Reset this
	 * section" button) changes the value but not the list, so the list follows
	 * the value here: the ticked items move to the front in the order of the
	 * value, the other items go behind them, and the ticks follow the value.
	 *
	 * Loop guard: this writes the page only and never the setting. It also does
	 * nothing when the list already holds the value, which is the case after a
	 * drag or a tick (list -> setting -> this function).
	 */
	function applyValueToList($list, value) {
		var wanted = (value === null || typeof value === 'undefined') ? '' : String(value);
		if (recomputeValue($list) === wanted) {
			return;
		}
		var ids = wanted === '' ? [] : wanted.split(',').map(function (id) {
			return id.trim();
		});
		var byId = {};
		var $items = $list.children('li.vance-sortable-item');
		$items.each(function () {
			byId[String($(this).data('section-id'))] = $(this);
		});
		var used = {};
		var ordered = [];
		ids.forEach(function (id) {
			if (byId[id] && !used[id]) {
				used[id] = true;
				byId[id].find('input.vance-sortable-checkbox').prop('checked', true);
				ordered.push(byId[id]);
			}
		});
		$items.each(function () {
			var $li = $(this);
			if (!used[String($li.data('section-id'))]) {
				$li.find('input.vance-sortable-checkbox').prop('checked', false);
				ordered.push($li);
			}
		});
		ordered.forEach(function ($li) {
			$list.append($li);
			visualizeChecked($li);
		});
		$list.siblings('input.vance-sortable-value').val(recomputeValue($list));
	}

	function bindSettingToList($list) {
		var controlId = $list.data('control-id');
		if (!controlId || $list.data('vanceSettingBound') || !window.wp || !wp.customize) {
			return;
		}
		$list.data('vanceSettingBound', true);
		wp.customize.control(controlId, function (control) {
			if (!control.setting) {
				return;
			}
			control.setting.bind(function (value) {
				applyValueToList($list, value);
			});
		});
	}

	function initList($list) {
		bindSettingToList($list);

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
