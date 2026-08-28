/**
 * Runs the real .gi-reveal script out of page-gi-health.php against a fake DOM,
 * under each condition that previously left the page permanently invisible.
 *
 * The script is extracted from the template rather than retyped, so the test
 * cannot pass against a version of the code that is not shipped.
 */
const fs = require('fs');
const path = require('path');

const THEME = path.join(__dirname, '..', 'wp-content', 'themes', 'vance-health-hub');

function extractScript(file, customizer) {
  const src = fs.readFileSync(path.join(THEME, file), 'utf8');
  // The last <script> block in the template holds the reveal logic.
  const blocks = [...src.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m => m[1]);
  let js = blocks.find(b => b.includes(".gi-reveal"));
  if (!js) throw new Error('no .gi-reveal script found in ' + file);
  // Resolve the one PHP echo inside it, the way WordPress would.
  js = js.replace(/<\?php echo is_customize_preview\(\) \? 'true' : 'false'; \?>/g,
                  customizer ? 'true' : 'false');
  if (js.includes('<?php')) throw new Error('unresolved PHP left in extracted script');
  return js;
}

/* ---- the smallest DOM these scripts touch ---------------------------- */
function makeEnv({ items = 4, hasIO = true, ioFires = true, readyState = 'complete',
                   reduceMotion = false, innerHeight = 800 } = {}) {
  const timers = [];
  const els = Array.from({ length: items }, (_, i) => ({
    _c: new Set(),
    classList: { add(c) { this._c.add(c); }, contains(c) { return this._c.has(c); } },
    // Half the elements sit below the fold, so the 1.2s in-viewport failsafe
    // cannot rescue them — only the 5s net can.
    getBoundingClientRect: () => (i < 2 ? { top: 10, bottom: 200 } : { top: 5000, bottom: 5200 }),
    getAttribute: () => null,
    querySelectorAll: () => [],
    addEventListener() {},
  }));
  els.forEach(e => { e.classList.add = e.classList.add.bind(e); e.classList.contains = e.classList.contains.bind(e); });

  const observed = [];
  const env = {
    document: {
      readyState,
      // Selector-aware: page-gi-condition.php's block also drives counters
      // and other widgets off their own selectors, and handing those the
      // reveal nodes makes the harness fail on its own fiction.
      querySelectorAll: sel => (String(sel).indexOf('.gi-reveal') !== -1 ? els : []),
      documentElement: { classList: { add() {} } },
      _listeners: {},
      addEventListener(ev, fn) { (this._listeners[ev] ||= []).push(fn); },
    },
    window: {
      innerHeight,
      matchMedia: () => ({ matches: reduceMotion }),
    },
    setTimeout: (fn, ms) => { timers.push({ fn, ms }); return timers.length; },
  };
  if (hasIO) {
    env.window.IntersectionObserver = function (cb) {
      this.observe = el => { observed.push(el); if (ioFires) cb([{ isIntersecting: true, target: el }]); };
      this.unobserve = () => {};
    };
  }
  return { env, els, timers, observed,
           runTimersUpTo: ms => timers.filter(t => t.ms <= ms).forEach(t => t.fn()) };
}

function run(js, cfg) {
  const h = makeEnv(cfg);
  const { env } = h;
  // 'window' must be a real object with `in` support for the IO feature test.
  // The script refers to bare `IntersectionObserver`, which in a browser
  // resolves off the global object; the sandbox has to supply it explicitly.
  const fn = new Function('window', 'document', 'setTimeout', 'Array', 'IntersectionObserver', js);
  fn(env.window, env.document, env.setTimeout, Array, env.window.IntersectionObserver);
  // Fire DOMContentLoaded if the script chose to wait for it.
  (env.document._listeners['DOMContentLoaded'] || []).forEach(f => f());
  return h;
}

let pass = 0, fail = 0;
function check(name, got, want = true) {
  const ok = got === want;
  if (ok) { pass++; console.log('  ok   ' + name); }
  else { fail++; console.log('  FAIL ' + name + '\n       expected: ' + want + '\n       got     : ' + got); }
}
const allVisible = els => els.every(e => e.classList.contains('is-visible'));
const noneVisible = els => els.every(e => !e.classList.contains('is-visible'));

for (const file of ['page-gi-health.php', 'page-gi-condition.php']) {
  console.log('\n===== ' + file + ' =====');

  console.log('\n-- Customizer preview: show everything at once, never observe --');
  {
    const h = run(extractScript(file, true), {});
    check('all revealed immediately', allVisible(h.els));
    check('observer never used', h.observed.length, 0);
  }

  console.log('\n-- Observer exists but NEVER reports (the Customizer iframe case) --');
  {
    const h = run(extractScript(file, false), { ioFires: false });
    check('nothing revealed up front', noneVisible(h.els));
    h.runTimersUpTo(1200);
    check('1.2s failsafe reveals the two on screen',
          h.els.filter(e => e.classList.contains('is-visible')).length, 2);
    h.runTimersUpTo(5000);
    check('5s net reveals every last one', allVisible(h.els));
  }

  console.log('\n-- No IntersectionObserver at all --');
  {
    const h = run(extractScript(file, false), { hasIO: false });
    check('all revealed immediately', allVisible(h.els));
  }

  console.log('\n-- prefers-reduced-motion --');
  {
    const h = run(extractScript(file, false), { reduceMotion: true });
    check('all revealed immediately', allVisible(h.els));
    check('observer never used', h.observed.length, 0);
  }

  console.log('\n-- Script parsed AFTER DOMContentLoaded (Boost defers the bundle) --');
  {
    const h = run(extractScript(file, false), { readyState: 'complete' });
    check('reveal still ran', allVisible(h.els));
  }

  console.log('\n-- Normal load: observer reports as it should --');
  {
    const h = run(extractScript(file, false), { readyState: 'loading' });
    check('all revealed', allVisible(h.els));
    check('every item was observed', h.observed.length, 4);
  }
}

console.log('\n' + '-'.repeat(58));
console.log('  PASSED ' + pass + '   FAILED ' + fail);
process.exit(fail ? 1 : 0);
