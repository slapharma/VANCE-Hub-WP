// Tests for assets/js/vance-events.js. Run: node tests/events.test.js
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const src = fs.readFileSync(path.join(__dirname, '..', 'wp-content', 'themes', 'vance-health-hub', 'assets', 'js', 'vance-events.js'), 'utf8');
let fails = 0;
function check(label, ok) { if (!ok) { fails++; console.log('  x  ' + label); } }
function load() {
  const win = {};
  vm.runInNewContext(src, { window: win });
  return win;
}
let w = load();
w.vanceTrack('ask_ai', 'start');
check('a start is pushed', w.dataLayer.length === 1 && w.dataLayer[0].event === 'vance_tool_start');
w.vanceTrack('ask_ai', 'complete');
check('a complete is a separate event', w.dataLayer.length === 2 && w.dataLayer[1].event === 'vance_tool_complete');
w.vanceTrack('ask_ai', 'start');
check('a repeat start on the same page view is not pushed again', w.dataLayer.length === 2);
check('the pushed object holds only event and vance_tool', JSON.stringify(Object.keys(w.dataLayer[0])) === JSON.stringify(['event', 'vance_tool']));
w.vanceTrack('crohns_quiz', 'start');
w.vanceTrack('ask_ai', 'answer is bloating');
w.vanceTrack('toString', 'start');
check('an unknown tool, an unknown stage and a prototype name are all ignored', w.dataLayer.length === 2);
w = load();
['meal_planner', 'self_assessment'].forEach(t => w.vanceTrack(t, 'start'));
check('each listed tool can start', w.dataLayer.length === 2 && w.dataLayer[1].vance_tool === 'self_assessment');
if (fails) { console.log(fails + ' FAILED'); process.exit(1); }
console.log('OK');
