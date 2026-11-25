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
