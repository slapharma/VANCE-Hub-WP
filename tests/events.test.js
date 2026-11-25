// Tests for assets/js/vance-events.js. Run: node tests/events.test.js
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const src = fs.readFileSync(path.join(__dirname, '..', 'wp-content', 'themes', 'vance-health-hub', 'assets', 'js', 'vance-events.js'), 'utf8');
let fails = 0;
function check(label, ok) { if (!ok) { fails++; console.log('  x  ' + label); } }
function load(search, cookie) {
  const win = { location: { search: search || '', protocol: 'https:' } };
  const listeners = {};
  const doc = { cookie: cookie || '', written: [], addEventListener: (t, f) => { listeners[t] = f; } };
  Object.defineProperty(doc, 'cookie', { get() { return this._c || cookie || ''; }, set(v) { this.written.push(v); this._c = (this._c || '') ; } });
  vm.runInNewContext(src, { window: win, document: doc, URLSearchParams });
  win._doc = doc; win._listeners = listeners;
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
// Source cookie: written only after statistics consent.
let c = load('?utm_source=Instagram&utm_medium=bio&utm_term=x&utm_campaign=<b>');
check('no consent: no cookie is written on load', c._doc.written.length === 0);
c._listeners.cmplz_enable_category({ detail: { category: 'marketing' } });
check('consent to another category writes nothing', c._doc.written.length === 0);
c._listeners.cmplz_enable_category({ detail: { category: 'statistics' } });
check('statistics consent writes the cookie once', c._doc.written.length === 1);
const written = decodeURIComponent(c._doc.written[0]);
check('cookie holds cleaned source, medium and campaign only', written.indexOf('utm_source=instagram&utm_medium=bio&utm_campaign=b') !== -1 && written.indexOf('utm_term') === -1 && written.indexOf('<') === -1);
check('cookie is Secure, Lax, 30 days', /SameSite=Lax/.test(c._doc.written[0]) && /Secure/.test(c._doc.written[0]) && /max-age=2592000/.test(c._doc.written[0]));
c = load('?utm_source=tiktok', 'cmplz_statistics=allow');
check('consent already given: cookie is written on load', c._doc.written.length === 1);
c = load('?q=1', 'cmplz_statistics=allow');
check('no utm_source: no cookie even with consent', c._doc.written.length === 0);
if (fails) { console.log(fails + ' FAILED'); process.exit(1); }
console.log('OK');
