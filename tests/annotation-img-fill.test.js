/**
 * Annotation plugin: images the theme positions absolutely (tile pictures,
 * hero photos) must keep filling their parent after image-annotator.js wraps
 * them. The wrapper has to take over the image's box (vhh-img-wrap--fill).
 *
 * Pure helper + static checks of the real files. The layout itself is
 * measured in a real browser, see the PR notes.
 *
 * Run: cd tests && node annotation-img-fill.test.js
 */
const fs = require('fs');
const path = require('path');

const base = path.join(__dirname, '..', 'wp-content', 'plugins', 'vhh-annotations', 'assets');
const jsPath = path.join(base, 'js', 'image-annotator.js');
const cssPath = path.join(base, 'css', 'annotations.css');
const lib = require(jsPath);
const js = fs.readFileSync(jsPath, 'utf8').split(' ').join('');
const css = fs.readFileSync(cssPath, 'utf8');

let pass = 0;
const fail = [];
function check(label, cond) { if (cond) { pass++; } else { fail.push(label); } }

check('exports shouldFill', typeof lib.shouldFill === 'function');
if (typeof lib.shouldFill === 'function') {
	check('absolute fills', lib.shouldFill('absolute') === true);
	check('fixed does not fill (an absolute wrapper would cover the ancestor)', lib.shouldFill('fixed') === false);
	check('static does not fill', lib.shouldFill('static') === false);
	check('relative does not fill', lib.shouldFill('relative') === false);
	check('sticky does not fill', lib.shouldFill('sticky') === false);
	check('empty does not fill', lib.shouldFill('') === false);
}

// syncFill() decides the class from the computed position; setup() calls it
// before the wrapper goes in, and a resize calls it again for every entry.
const code = js.replace(/\/\*[\s\S]*?\*\//g, ''); // without comments: a docblock must not satisfy these
check('syncFill toggles the class from shouldFill(computed position)', code.indexOf("varfill=shouldFill(imgStyle.position);") !== -1 && code.indexOf("wrap.classList.toggle('vhh-img-wrap--fill',fill);") !== -1);
check('setup calls syncFill on the new wrapper', /functionsetup\(img\)\{[^}]*\}[^}]*syncFill\(wrap,img\);/.test(code));
check('the entry keeps its wrapper for later syncs', code.indexOf('wrap:wrap,') !== -1);
check('a resize re-syncs every wrapper', code.indexOf("window.addEventListener('resize'") !== -1 && code.indexOf('syncFill(entry.wrap,entry.img)') !== -1);
check('the z-index is copied only for a fill wrapper and cleared otherwise', code.indexOf("wrap.style.zIndex=(fill&&imgStyle.zIndex!=='auto')?imgStyle.zIndex:'';") !== -1);

// CSS: the modifier rule must take over the box.
const start = css.indexOf('.vhh-img-wrap--fill {');
check('css has a .vhh-img-wrap--fill rule', start !== -1);
if (start !== -1) {
	const body = css.slice(start, css.indexOf('}', start)).split(' ').join('');
	check('fill rule is position:absolute', body.indexOf('position:absolute') !== -1);
	check('fill rule is inset:0', body.indexOf('inset:0') !== -1);
	check('fill rule is display:block', body.indexOf('display:block') !== -1);
	check('fill rule lifts max-width', body.indexOf('max-width:none') !== -1);
	// It must come after the base rule, or inline-block/relative would win.
	check('fill rule comes after the base .vhh-img-wrap rule', start > css.indexOf('.vhh-img-wrap {'));
}

console.log(pass + ' passed, ' + fail.length + ' failed');
fail.forEach((f) => console.log('FAIL: ' + f));
process.exit(fail.length ? 1 : 0);
