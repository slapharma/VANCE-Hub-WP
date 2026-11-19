/**
 * Tests for the tile logic in assets/js/hub-tool-result.js (the part that turns
 * survey answers into "What you told us" tiles). Pure functions, no DOM.
 *
 * The field spec is made by PHP (vance_hub_tool_result_fields). It is written
 * out here in the same shape, from the real survey questions.
 *
 * Run: cd tests && node hub-tool-result.test.js
 */
const path = require('path');
const lib = require(path.join(__dirname, '..', 'wp-content', 'themes', 'vance-health-hub', 'assets', 'js', 'hub-tool-result.js'));

const FIELDS = [
	{ field: 'age', label: 'Age range' },
	{ field: 'gastro_condition', label: 'Gastro condition' },
	{ field: 'condition_type', label: 'Primary concern', text: { Other: 'condition_type_other' } },
	{ field: 'duration', label: 'Duration of interest', map: { Recently: 'Recently (less than 6 months)' } },
	{ field: 'seeing_specialist', label: 'Seeing a specialist', text: { Yes: 'specialist_type' } },
	{ field: 'use_supplements', label: 'Food supplements', dep: { Yes: { field: 'supplement_types', text: { Other: 'supplement_other' } } } }
];

let pass = 0;
const fail = [];
function check(label, cond) { if (cond) { pass++; } else { fail.push(label); } }
const find = (items, label) => items.filter((i) => i.label === label)[0];

// No answers, no tiles.
check('no answers give no tiles', lib.buildItems(FIELDS, {}).length === 0);
check('null answers give no tiles', lib.buildItems(FIELDS, null).length === 0);
check('no fields give no tiles', lib.buildItems([], { age: ['25-34'] }).length === 0);
check('blank and whitespace answers give no tile', lib.buildItems(FIELDS, { age: ['   '], gastro_condition: [''] }).length === 0);

// One answer, one tile, in field order.
let items = lib.buildItems(FIELDS, { gastro_condition: ['Diagnosed'], age: ['25-34'] });
check('two answers give two tiles', items.length === 2);
check('tiles follow the survey order, not the answer order', items[0].label === 'Age range' && items[1].label === 'Gastro condition');
check('a tile holds the answer text', items[0].value === '25-34');
check('an unanswered question makes no tile', !find(items, 'Primary concern'));

// Multi-answer and free text.
items = lib.buildItems(FIELDS, { condition_type: ["Crohn's", 'UC'] });
check('multi-answer values are joined', find(items, 'Primary concern').value === "Crohn's, UC");
items = lib.buildItems(FIELDS, { condition_type: ['Other'], condition_type_other: 'Coeliac' });
check('"Other" with its free text shows the text', find(items, 'Primary concern').value === 'Other: Coeliac');
items = lib.buildItems(FIELDS, { condition_type: ['Other'], condition_type_other: '' });
check('"Other" with no text shows just "Other"', find(items, 'Primary concern').value === 'Other');
items = lib.buildItems(FIELDS, { condition_type: ["Crohn's"], condition_type_other: 'Left over text' });
check('free text for an option that was not chosen is not shown', find(items, 'Primary concern').value === "Crohn's");

// Display map.
items = lib.buildItems(FIELDS, { duration: ['Recently'] });
check('a mapped value shows its display text', find(items, 'Duration of interest').value === 'Recently (less than 6 months)');
items = lib.buildItems(FIELDS, { duration: ['3+ Years'] });
check('an unmapped value shows as stored', find(items, 'Duration of interest').value === '3+ Years');

// Follow-up fields.
items = lib.buildItems(FIELDS, { seeing_specialist: ['Yes'], specialist_type: ' Dietitian ' });
check('"Yes" with a specialist type shows it, trimmed', find(items, 'Seeing a specialist').value === 'Yes: Dietitian');
items = lib.buildItems(FIELDS, { seeing_specialist: ['No'], specialist_type: 'Dietitian' });
check('"No" ignores a leftover specialist type', find(items, 'Seeing a specialist').value === 'No');
items = lib.buildItems(FIELDS, { use_supplements: ['Yes'], supplement_types: ['Omega 3', 'Other'], supplement_other: 'Magnesium' });
check('supplements list the nested choices and the Other text', find(items, 'Food supplements').value === 'Yes: Omega 3, Other: Magnesium');
items = lib.buildItems(FIELDS, { use_supplements: ['Yes'] });
check('"Yes" with no nested choices shows just "Yes"', find(items, 'Food supplements').value === 'Yes');
items = lib.buildItems(FIELDS, { use_supplements: ['No'], supplement_types: ['Zinc'] });
check('"No" ignores leftover nested choices', find(items, 'Food supplements').value === 'No');

// Strings, not arrays.
items = lib.buildItems(FIELDS, { age: '18-24' });
check('a plain string answer works', items.length === 1 && items[0].value === '18-24');

// Hostile text stays text: the script sets textContent, so the tile value is data.
items = lib.buildItems(FIELDS, { condition_type: ['Other'], condition_type_other: '<img src=x onerror=alert(1)>' });
check('markup in free text is kept as plain text for textContent', find(items, 'Primary concern').value === 'Other: <img src=x onerror=alert(1)>');

// The script never posts or stores answers: no save helper is exported.
check('no save helper exported (the script posts nothing itself)', typeof lib.toPost === 'undefined' && Object.keys(lib).sort().join(',') === 'buildItems,collect');

if (fail.length) {
	console.log(fail.length + ' FAILED of ' + (pass + fail.length) + ':');
	fail.forEach((f) => console.log('  x  ' + f));
	process.exit(1);
}
console.log('OK — ' + pass + ' checks passed.');
