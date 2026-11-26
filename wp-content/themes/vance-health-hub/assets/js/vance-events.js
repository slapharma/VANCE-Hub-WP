/**
 * Tool events for measurement: one "start" and one "complete" for each tool, for
 * each page view. Pushes to the dataLayer only.
 *
 * Privacy rule: the event carries a tool name from a fixed list and nothing
 * else. Never add a question, an answer, a result, a condition or a user id.
 * Keep the tool list in step with vance_event_tools() in inc/attribution.php.
 */
(function () {
	'use strict';

	var TOOLS = { ask_ai: 1, meal_planner: 1, self_assessment: 1 };
	var STAGES = { start: 1, complete: 1 };
	var sent = {};

	// Sign-up source. A visitor from a bio link has not met the consent banner
	// when the page loads, and a cached page never reaches PHP. So the labels are
	// held in memory and the cookie is written only once statistics consent exists
	// (Complianz). Same rules as inc/attribution.php: four utm labels, short tokens.
	var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];
	var labels = {};
	function clean(value) {
		return String(value || '').toLowerCase().replace(/[^a-z0-9_.-]/g, '').slice(0, 40);
	}
	try {
		var query = new URLSearchParams(window.location.search);
		UTM_KEYS.forEach(function (key) {
			var value = clean(query.get(key));
			if (value) { labels[key] = value; }
		});
	} catch (e) { labels = {}; }
	function writeCookie() {
		if (!labels.utm_source) { return; }
		var parts = UTM_KEYS.filter(function (k) { return labels[k]; }).map(function (k) { return k + '=' + labels[k]; });
		document.cookie = 'vance_utm=' + encodeURIComponent(parts.join('&')) + '; max-age=' + (30 * 86400) + '; path=/; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
	}
	function statisticsAllowed() {
		return /(^|; )cmplz_statistics=allow/.test(document.cookie);
	}
	if (statisticsAllowed()) {
		writeCookie();
	}
	document.addEventListener('cmplz_enable_category', function (event) {
		var detail = event && event.detail;
		if (detail && (detail.category === 'statistics' || (detail.categories && detail.categories.indexOf('statistics') !== -1))) {
			writeCookie();
		}
	});

	window.vanceTrack = function (tool, stage) {
		if (!TOOLS.hasOwnProperty(tool) || !STAGES.hasOwnProperty(stage)) {
			return;
		}
		var key = tool + ':' + stage;
		if (sent[key]) {
			return;
		}
		sent[key] = true;
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ event: 'vance_tool_' + stage, vance_tool: tool });
	};
}());
