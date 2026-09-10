/**
 * Ingredient quantity scaling and consolidation — the one implementation.
 *
 * Lifted out of assets/js/recipe-single.js, which had the scaler inside its own
 * IIFE. The dashboard's shopping list and the meal-plan PDF need exactly the
 * same arithmetic and rounding: copies would drift, and the first symptom would
 * be a recipe page and a shopping list disagreeing about how much flour to buy,
 * which is the kind of bug nobody reports and everybody notices.
 *
 * Exposed as window.VanceRecipeScale. No build step; enqueue before consumers.
 */
(function (root) {
	'use strict';

	var FRAC = {
		'¼': 0.25, '½': 0.5, '¾': 0.75,
		'⅓': 1 / 3, '⅔': 2 / 3,
		'⅕': 0.2, '⅖': 0.4, '⅗': 0.6, '⅘': 0.8,
		'⅙': 1 / 6, '⅚': 5 / 6,
		'⅛': 0.125, '⅜': 0.375, '⅝': 0.625, '⅞': 0.875
	};
	var FRAC_CHARS = Object.keys(FRAC).join('');
	var NUM_TOKEN = '(?:\\d+\\s+\\d+\\/\\d+|\\d+\\/\\d+|\\d+[' + FRAC_CHARS + ']|[' + FRAC_CHARS + ']|\\d+(?:\\.\\d+)?)';
	var LEADING_QTY_RE = new RegExp('^(' + NUM_TOKEN + ')(\\s*[-–]\\s*(' + NUM_TOKEN + '))?');
	// The quantity is not always leading: "Juice of 1 lemon", "Zest of ½ orange".
	// This finds the first quantity anywhere, keeping what sits either side.
	var ANY_QTY_RE = new RegExp('^(.*?)(' + NUM_TOKEN + ')(\\s*[-–]\\s*(?:' + NUM_TOKEN + '))?(.*)$');
	// Same, but capturing the far end of a range too, so the scaler moves both.
	var ANY_QTY_RE_RANGE = new RegExp('^(.*?)(' + NUM_TOKEN + ')(\\s*[-–]\\s*(' + NUM_TOKEN + '))?(.*)$');

	/**
	 * Words that must never take an "s" when a total goes above one. Metric and
	 * imperial abbreviations are invariant — "500 gs flour" is nonsense — while
	 * counted nouns like tin, clove and lemon do pluralise. Getting this wrong
	 * in the safe direction (not pluralising) reads as terse; getting it wrong
	 * the other way reads as broken.
	 */
	var NO_PLURAL = {
		g: 1, kg: 1, mg: 1, ml: 1, l: 1, cl: 1, dl: 1,
		oz: 1, lb: 1, lbs: 1, tbsp: 1, tsp: 1, tbs: 1,
		cm: 1, inch: 1, x: 1
	};

	/**
	 * Words that ARE the measure, so they take the plural even when more words
	 * follow: "2 cups cooked lentils", not "2 cup cooked lentils". Anything not
	 * listed and followed by another word is treated as a modifier, and the head
	 * noun at the end takes the plural instead ("2 salmon fillets").
	 */
	var MEASURE_WORDS = {
		cup: 1, tin: 1, can: 1, jar: 1, bottle: 1, packet: 1, pack: 1, punnet: 1,
		clove: 1, slice: 1, sprig: 1, stick: 1, stalk: 1, bunch: 1, handful: 1,
		head: 1, bulb: 1, sheet: 1, piece: 1, pinch: 1, dash: 1, knob: 1,
		splash: 1, glass: 1, scoop: 1, square: 1, strip: 1, wedge: 1, rasher: 1,
		block: 1, bag: 1, box: 1, carton: 1, sachet: 1, tub: 1
	};

	function parseNumberToken(tok) {
		tok = String(tok).trim();
		var fracOnly = tok.match(new RegExp('^(\\d+)?\\s*([' + FRAC_CHARS + '])$'));
		if (fracOnly) {
			return (fracOnly[1] ? parseFloat(fracOnly[1]) : 0) + FRAC[fracOnly[2]];
		}
		var mixed = tok.match(/^(\d+)\s+(\d+)\/(\d+)$/);
		if (mixed) {
			return parseFloat(mixed[1]) + parseFloat(mixed[2]) / parseFloat(mixed[3]);
		}
		var simpleFrac = tok.match(/^(\d+)\/(\d+)$/);
		if (simpleFrac) {
			return parseFloat(simpleFrac[1]) / parseFloat(simpleFrac[2]);
		}
		var num = parseFloat(tok);
		return isNaN(num) ? null : num;
	}

	// Snaps to the nearest quarter and renders as a unicode fraction where
	// possible — "exact" decimals like 1.33 read as an estimate anyway once
	// you're scaling a recipe, so a tidy ¼-step reads more like a real recipe.
	function formatQty(n) {
		var snapped = Math.round(n * 4) / 4;
		var whole = Math.floor(snapped);
		var frac = snapped - whole;
		var fracStr = '';
		if (frac >= 0.875) { whole += 1; }
		else if (frac >= 0.625) { fracStr = '¾'; }
		else if (frac >= 0.375) { fracStr = '½'; }
		else if (frac >= 0.125) { fracStr = '¼'; }
		if (0 === whole && fracStr) { return fracStr; }
		return fracStr ? (whole + fracStr) : String(whole);
	}

	/**
	 * Scale the quantity in a line.
	 *
	 * Matches the FIRST quantity wherever it sits, not only a leading one.
	 * Recipes write "Juice of 1 lemon" and "Zest of 1 orange" as often as
	 * "2 carrots", and a leading-only match left those untouched when the
	 * servings stepper moved — silently, so a doubled recipe still asked for one
	 * lemon. That was live on the single recipe page too, not just here.
	 */
	function scaleIngredientLine(line, ratio) {
		if (!ratio || 1 === ratio) { return line; }
		var m = String(line).match(ANY_QTY_RE_RANGE);
		if (!m) { return line; } // No quantity anywhere — "Pinch of salt" — leave it.
		var startVal = parseNumberToken(m[2]);
		if (null === startVal) { return line; }
		var scaled = startVal * ratio;
		var out = formatQty(scaled);
		var upper = scaled;
		if (m[4]) {
			var endVal = parseNumberToken(m[4]);
			if (null !== endVal) {
				upper = endVal * ratio;
				out += '–' + formatQty(upper);
			}
		}
		// "2 lemon" and "1 lemons" both read as bugs. The noun follows the
		// quantity, so agree it with the new figure — the consolidator does the
		// same, and the two must not disagree on the same line.
		return m[1] + out + agreeNoun(m[5], upper);
	}

	/**
	 * Which word carries the number.
	 *
	 * "1 cup cooked lentils" doubles to "2 cups cooked lentils" — the measure
	 * word takes it. "2 salmon fillets" halves to "1 salmon fillet" — here the
	 * first word is a modifier and the LAST word is the head noun. Blindly
	 * agreeing the first word gave "4 salmons fillets", which is how this rule
	 * was found.
	 */
	function agreeNoun(suffix, total) {
		var str = String(suffix);
		var lead = str.match(/^(\s*)([A-Za-z]+)/);
		if (!lead) { return str; }
		if (NO_PLURAL[lead[2].toLowerCase()]) { return str; }

		var rest = str.slice(lead[0].length);
		// Look the measure up in its SINGULAR form: the line may already read
		// "2 cups fresh spinach", and keying on "cups" missed it, so the head-noun
		// branch ran instead and produced "4 cups fresh spinaches".
		var isMeasure = !!MEASURE_WORDS[singularise(lead[2])];
		var moreWords = /[A-Za-z]/.test(rest);

		if (isMeasure || !moreWords) {
			return lead[1] + agreeWord(lead[2], total) + rest;
		}
		// Modifier + head noun: the head is the last word of the noun phrase, and
		// the noun phrase ends at the first comma or bracket — everything after
		// that is a preparation note. Without this, "1 large leek, thoroughly
		// washed and sliced" agreed the final word and produced "sliceds".
		var head = str.split(/[,(;]/)[0];
		var tail = head.match(/([A-Za-z]+)(\W*)$/);
		if (!tail) { return str; }
		return str.slice(0, tail.index) + agreeWord(tail[1], total) + str.slice(tail.index + tail[1].length);
	}

	function agreeWord(word, total) {
		if (MASS_NOUNS[singularise(word)]) { return word; }
		var out = total > 1 ? pluralise(word) : singularise(word);
		// singularise() lowercases; keep the original casing when the word is
		// unchanged in substance, so "Lemons" does not come back as "lemon".
		if (out.toLowerCase() === word.toLowerCase()) { return word; }
		return /^[A-Z]/.test(word) ? out.charAt(0).toUpperCase() + out.slice(1) : out;
	}

	/** Split a line into { prefix, qty, suffix } around its first quantity. */
	function splitQuantity(line) {
		var m = String(line).match(ANY_QTY_RE);
		if (!m) { return null; }
		// A range ("1–2 lemons") is not summable without inventing a figure, so
		// it is left alone rather than silently collapsed to one end of it.
		if (m[3]) { return null; }
		var val = parseNumberToken(m[2]);
		if (null === val) { return null; }
		return { prefix: m[1], qty: val, suffix: m[4] };
	}

	// Nouns ending in -o that take -es. English is split here — potatoes and
	// tomatoes, but avocados and kilos — so this is a list rather than a rule.
	var O_TAKES_ES = { potato: 1, tomato: 1, mango: 1, hero: 1, echo: 1, volcano: 1 };
	// Nouns ending -f/-fe that take -ves. "1 bay leaf" doubling to "2 bay leafs"
	// is what put this list here; bay leaf is in a lot of these recipes.
	var F_TAKES_VES = { leaf: 1, loaf: 1, half: 1, knife: 1, shelf: 1, calf: 1 };
	// Mass nouns: you cannot have two spinaches. Only consulted for the head
	// noun - a measure in front of one ("2 cups spinach") pluralises the
	// measure instead, which is handled before this is reached.
	var MASS_NOUNS = {
		spinach: 1, rice: 1, flour: 1, sugar: 1, salt: 1, pepper: 1, oil: 1,
		water: 1, milk: 1, yoghurt: 1, yogurt: 1, cheese: 1, butter: 1,
		honey: 1, quinoa: 1, couscous: 1, kale: 1, broccoli: 1, rocket: 1,
		coriander: 1, parsley: 1, garlic: 1, ginger: 1, hummus: 1, tahini: 1
	};

	function singularise(word) {
		var w = word.toLowerCase();
		if (NO_PLURAL[w]) { return w; }
		if (/ies$/.test(w)) { return w.slice(0, -3) + 'y'; }        // berries -> berry
		if (/ves$/.test(w) && F_TAKES_VES[w.slice(0, -3) + 'f']) { return w.slice(0, -3) + 'f'; }   // leaves -> leaf
		if (/ves$/.test(w) && F_TAKES_VES[w.slice(0, -3) + 'fe']) { return w.slice(0, -3) + 'fe'; } // knives -> knife
		if (/oes$/.test(w) && O_TAKES_ES[w.slice(0, -2)]) { return w.slice(0, -2); } // potatoes -> potato
		if (/(ch|sh|s|x|z)es$/.test(w)) { return w.slice(0, -2); }  // dashes -> dash
		if (/s$/.test(w) && !/ss$/.test(w)) { return w.slice(0, -1); }
		return w;
	}

	function pluralise(word) {
		var lower = word.toLowerCase();
		if (NO_PLURAL[lower]) { return word; }
		if (/s$/.test(lower) && !/ss$/.test(lower)) { return word; } // already plural
		if (O_TAKES_ES[lower]) { return word + 'es'; }
		if (F_TAKES_VES[lower]) {
			return word.replace(/fe?$/i, 'ves');
		}
		if (/y$/.test(lower) && !/[aeiou]y$/.test(lower)) { return word.slice(0, -1) + 'ies'; }
		if (/(ch|sh|s|x|z)$/.test(lower)) { return word + 'es'; }
		return word + 's';
	}

	/**
	 * Group key for two lines that describe the same purchase.
	 *
	 * Everything either side of the quantity has to match, with the first word
	 * after it compared singular-insensitively so "1 lemon" and "½ lemon" meet
	 * while "1 tbsp olive oil" and "50 ml olive oil" stay apart — different
	 * units are exactly what must NOT be summed.
	 */
	function groupKey(parts) {
		var suffix = parts.suffix.replace(/^\s+/, '');
		var first = suffix.match(/^[A-Za-z]+/);
		var rest = first ? suffix.slice(first[0].length) : suffix;
		var head = first ? singularise(first[0]) : '';
		return (parts.prefix.trim().toLowerCase() + '|' + head + '|' + rest.trim().toLowerCase());
	}

	function renderTotal(parts, total) {
		var qty = formatQty(total);
		var suffix = parts.suffix;
		var lead = suffix.match(/^(\s*)([A-Za-z]+)/);
		if (lead && total > 1) {
			suffix = lead[1] + pluralise(lead[2]) + suffix.slice(lead[0].length);
		}
		return parts.prefix + qty + suffix;
	}

	/**
	 * Consolidate ingredient lines into one row per item.
	 *
	 * Takes [{ line, times }] — `times` being how many times that line appears
	 * across a plan — and returns [{ text, count }].
	 *
	 * Where a line carries a parseable quantity, the quantities are SUMMED and
	 * `count` comes back 1: "Juice of 1 lemon" plus "Juice of ½ lemon" five
	 * times is "Juice of 3½ lemons", not two rows and a multiplier. Where a line
	 * has no quantity, or is a range, or its unit differs from another line's,
	 * it stays its own row and `count` carries the ×N — because "½ cup" and
	 * "100 g" of the same thing genuinely cannot be added.
	 */
	function consolidate(entries) {
		var groups = {};
		var order = [];
		(entries || []).forEach(function (e) {
			var line = e.line;
			var times = e.times || 1;
			var parts = splitQuantity(line);
			var key = parts ? ('#' + groupKey(parts)) : ('=' + line);
			if (!groups[key]) {
				groups[key] = { parts: parts, total: 0, count: 0, line: line };
				order.push(key);
			}
			if (parts) { groups[key].total += parts.qty * times; }
			groups[key].count += times;
		});
		return order.map(function (key) {
			var g = groups[key];
			return g.parts
				? { text: renderTotal(g.parts, g.total), count: 1 }
				: { text: g.line, count: g.count };
		}).sort(function (a, b) {
			return a.text.toLowerCase().localeCompare(b.text.toLowerCase());
		});
	}

	root.VanceRecipeScale = {
		line: scaleIngredientLine,
		consolidate: consolidate,
		formatQty: formatQty,
		parseNumberToken: parseNumberToken,
		splitQuantity: splitQuantity
	};
}(typeof window !== 'undefined' ? window : this));

if (typeof module !== 'undefined' && module.exports) { module.exports = this.VanceRecipeScale; }
