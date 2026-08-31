<?php
/**
 * Renders the category-archive heroes outside WordPress and asserts on the
 * real output.
 *
 * Same shape as legal-hero.test.php and gi-hero.test.php: WP stubs, a
 * controllable bag of theme mods, a controllable set of terms and posts, and
 * assertions against the emitted HTML.
 *
 * WHAT THIS SUITE IS FOR
 *
 * inc/category-hero.php replaced three separate copies of the old dark band —
 * archive.php, template-parts/subcategory-grouped-archive.php and
 * category-content-healthcare-news.php. Two things could quietly break as a
 * result and neither would show up as a PHP error:
 *
 *   1. a saved Customizer value stops being honoured, and only on the live
 *      site, because the Customizer preview serves each setting's registered
 *      default and looks perfectly fine either way;
 *   2. a template stops calling the renderer, or keeps calling it but also
 *      still emits the dark band underneath.
 *
 * Section 9 covers (2) by reading the three templates. It is a SOURCE
 * assertion, not an execution one, and is labelled as such: an archive
 * template cannot be included here the way legal-hero.test.php includes its
 * five policy pages, because it needs a live query, get_header() and a post
 * loop. What it can prove is that the call is present, is not commented out,
 * and that the 350px band is gone from every category path.
 *
 * Every check here must be able to go red. `python mutate-category.py` breaks
 * the source on purpose and confirms each one does.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';
$GLOBALS['THEME_DIR'] = $THEME;

/* ---- the bags the stubs read ----------------------------------------- */
$GLOBALS['MODS']  = array();
$GLOBALS['TERMS'] = array();   // term_id => WP_Term
$GLOBALS['POSTS'] = array();   // term_id => array( 'total' => int, 'time' => unix|null )
$GLOBALS['DESCS'] = array();   // term_id => description string

function set_mods( array $m )  { $GLOBALS['MODS']  = $m; }
function set_descs( array $d ) { $GLOBALS['DESCS'] = $d; }
function set_posts( array $p ) { $GLOBALS['POSTS'] = $p; }

/* ---- WordPress stubs -------------------------------------------------- */

class WP_Term {
	public $term_id, $name, $slug, $parent, $taxonomy, $count;
	public function __construct( $id, $name, $slug, $parent = 0, $taxonomy = 'category', $count = 0 ) {
		$this->term_id  = $id;
		$this->name     = $name;
		$this->slug     = $slug;
		$this->parent   = $parent;
		$this->taxonomy = $taxonomy;
		$this->count    = $count;
	}
}

/**
 * Only the two properties the renderer touches. `found_posts` is read for the
 * total and `posts` for the newest id, which is exactly the pair the real
 * WP_Query gives back for these arguments.
 */
class WP_Query {
	public $posts = array();
	public $found_posts = 0;
	public $args = array();
	public function __construct( $args ) {
		$this->args = $args;
		$GLOBALS['LAST_QUERY'] = $args;
		$tid  = isset( $args['cat'] ) ? (int) $args['cat'] : 0;
		$data = isset( $GLOBALS['POSTS'][ $tid ] ) ? $GLOBALS['POSTS'][ $tid ] : array( 'total' => 0, 'time' => null );
		$this->found_posts = (int) $data['total'];
		if ( $this->found_posts > 0 ) {
			$this->posts = array( 900 + $tid );
			$GLOBALS['POST_TIME'][ 900 + $tid ] = $data['time'];
		}
	}
}

function vance_get_theme_mod( $key, $default = '' ) {
	return array_key_exists( $key, $GLOBALS['MODS'] ) ? $GLOBALS['MODS'][ $key ] : $default;
}
function get_theme_mod( $key, $default = '' ) { return vance_get_theme_mod( $key, $default ); }
function get_template_directory() { return $GLOBALS['THEME_DIR']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/vance-health-hub'; }
function get_queried_object() { return isset( $GLOBALS['QUERIED'] ) ? $GLOBALS['QUERIED'] : null; }

function get_term( $id, $tax = 'category' ) {
	return isset( $GLOBALS['TERMS'][ $id ] ) ? $GLOBALS['TERMS'][ $id ] : null;
}
function get_category_link( $id ) { return 'https://example.test/category/' . $GLOBALS['TERMS'][ $id ]->slug . '/'; }
function term_description( $t ) {
	$id = is_object( $t ) ? $t->term_id : (int) $t;
	return isset( $GLOBALS['DESCS'][ $id ] ) ? $GLOBALS['DESCS'][ $id ] : '';
}
function get_categories( $args ) {
	$out = array();
	foreach ( $GLOBALS['TERMS'] as $t ) {
		if ( (int) $t->parent !== (int) $args['parent'] ) { continue; }
		if ( ! empty( $args['hide_empty'] ) ) {
			$p = isset( $GLOBALS['POSTS'][ $t->term_id ] ) ? $GLOBALS['POSTS'][ $t->term_id ]['total'] : 0;
			if ( $p < 1 ) { continue; }
		}
		$out[] = ( isset( $args['fields'] ) && $args['fields'] === 'ids' ) ? $t->term_id : $t;
	}
	return $out;
}
function get_post_time( $fmt, $gmt = false, $post = 0 ) {
	return isset( $GLOBALS['POST_TIME'][ $post ] ) ? $GLOBALS['POST_TIME'][ $post ] : 0;
}
function date_i18n( $fmt, $ts ) { return gmdate( $fmt, (int) $ts ); }
function number_format_i18n( $n ) { return number_format( (float) $n ); }
function wp_reset_postdata() {}
function wp_strip_all_tags( $t ) { return strip_tags( (string) $t ); }
function sanitize_html_class( $c ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c ); }
function apply_filters( $tag, $value ) { return $value; }
function _n( $s, $p, $n, $d = '' ) { return $n === 1 ? $s : $p; }
function __( $t, $d = '' ) { return $t; }
function esc_attr( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html_e( $t, $d = '' ) { echo esc_html( $t ); }
function esc_html__( $t, $d = '' ) { return esc_html( $t ); }
function esc_attr_e( $t, $d = '' ) { echo esc_attr( $t ); }
function wp_kses_post( $t ) { return $t; }
function add_query_arg( $k, $v, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $k . '=' . rawurlencode( $v );
}

/* ---- the term fixture ------------------------------------------------- */
/*
 * The live taxonomy as of 2026-08-31, trimmed to what the assertions need:
 * one grouped parent with children, one childless parent, one parent that is
 * in the registry but empty, and one that is not in the registry at all.
 */
function seed_terms() {
	$GLOBALS['TERMS'] = array();
	foreach ( array(
		array( 17, 'Clinical Reviews',       'content-clinical-reviews',   0 ),
		array( 85, 'Clinical Trial Abstracts','clinical-trial-abstracts', 17 ),
		array( 75, 'Gastro Living',          'content-gastro-living',      0 ),
		// Stored HTML-encoded, which is how they really sit in wp_terms — see
		// the wp term list output in CLAUDE.md's taxonomy notes. A fixture with
		// the bare ampersand would hide the double-escaping bug entirely.
		array( 87, 'Food &amp; Nutrition',   'food-nutrition',            75 ),
		array( 88, 'Tests &amp; Treatments', 'tests-treatments',          75 ),
		array( 15, 'Healthcare News',        'content-healthcare-news',    0 ),
		array( 21, 'White Papers',           'content-white-papers',       0 ),
		array( 66, 'Brand New Section',      'brand-new-section',          0 ),
	) as $r ) {
		$GLOBALS['TERMS'][ $r[0] ] = new WP_Term( $r[0], $r[1], $r[2], $r[3] );
	}
}

/**
 * The rendered <section> alone, with the inline <style> stripped.
 *
 * Every markup assertion uses this. The stylesheet's comments quote section
 * names ("Clinical Reviews") and class names (.vhh-hero-spotlight__crumb), so
 * a naive strpos over the whole output finds a breadcrumb on a page that has
 * none — which is exactly what two checks here did on the first run.
 */
function body( $html ) {
	$html = preg_replace( '#<style.*?</style>#s', '', $html );
	$at   = strpos( $html, '<section' );
	return $at === false ? $html : substr( $html, $at );
}

/**
 * Does this stylesheet carry a rule for exactly this class?
 *
 * `strpos( $css, '.my-class' )` is NOT that question: it is also satisfied by
 * `.my-class-DISABLED`, which is exactly how a class can be renamed out of
 * existence while every check for it stays green. Four of this suite's checks
 * did precisely that until `python mutate-category.py` caught them. The class
 * must be followed by something that ends a class name.
 *
 * @param string $css       Stylesheet text.
 * @param string $class     Class name, no leading dot.
 * @param bool   $block     True to require it opens a rule block (`{`) rather
 *                          than merely appearing in a compound selector.
 */
function css_has( $css, $class, $block = false ) {
	$tail = $block ? '\s*(?:,[^{]*)?\{' : '[^A-Za-z0-9_-]';
	return preg_match( '/\.' . preg_quote( $class, '/' ) . $tail . '/', $css ) === 1;
}

/** Render one term's hero and hand back the HTML. */
function render( $term_id, array $mods = array(), array $descs = array(), array $posts = null ) {
	set_mods( $mods );
	set_descs( $descs );
	if ( $posts !== null ) { set_posts( $posts ); }
	$GLOBALS['QUERIED'] = isset( $GLOBALS['TERMS'][ $term_id ] ) ? $GLOBALS['TERMS'][ $term_id ] : null;
	ob_start();
	vance_render_category_hero();
	return ob_get_clean();
}

/* ---- the runner ------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

require_once $THEME . '/inc/category-hero.php';
seed_terms();

/*
 * Post counts. Gastro Living's 89 is the parent's own total INCLUDING its
 * children, which is what `cat` returns and what the band is meant to show.
 */
$DEFAULT_POSTS = array(
	17 => array( 'total' => 28, 'time' => 1754006400 ),  // 2025-08-01
	85 => array( 'total' => 23, 'time' => 1753920000 ),
	75 => array( 'total' => 89, 'time' => 1756598400 ),  // 2025-08-31
	87 => array( 'total' => 16, 'time' => 1756512000 ),
	88 => array( 'total' => 21, 'time' => 1756512000 ),
	15 => array( 'total' => 32, 'time' => 1756598400 ),
	21 => array( 'total' => 0,  'time' => null ),
	66 => array( 'total' => 4,  'time' => 1756598400 ),
);
set_posts( $DEFAULT_POSTS );


/* ===================================================================== */
section( '1. A top-level section renders the spotlight band' );
/* ===================================================================== */

$h = render( 17 );

$b = body( $h );

check( '1a  emits the shared .vhh-hero-spotlight section, not the .hero band',
	strpos( $h, 'class="vhh-hero-spotlight ' ) !== false
	&& strpos( $h, 'height: 350px' ) === false );

check( '1b  carries the set class and a per-section class',
	strpos( $h, 'vhh-hero-spotlight--cat' ) !== false
	&& strpos( $h, 'vhh-hero-spotlight--cat-content-clinical-reviews' ) !== false );

check( '1c  headline is the term name',
	strpos( $h, '<h1 class="vhh-hero-spotlight__title">Clinical Reviews</h1>' ) !== false );

check( '1d  eyebrow is the registry KIND, not the section name again',
	strpos( $b, '__eyebrow">The evidence<' ) !== false
	&& substr_count( $b, 'Clinical Reviews' ) === 1 );

check( '1e  lede falls back to the registry when the term has no description',
	strpos( $h, 'Trial data and peer-reviewed papers' ) !== false );

check( '1f  the section-specific card renders with its own icon and copy',
	strpos( $h, 'Every review points back at the paper' ) !== false );

check( '1g  no breadcrumb on a top-level section',
	strpos( $b, '__crumb' ) === false );

check( '1h  the inline stylesheet is emitted',
	strpos( $h, 'id="vhh-category-hero-css"' ) !== false );


/* ===================================================================== */
section( '2. The band of facts' );
/* ===================================================================== */

$h = render( 75 );   // Gastro Living: 89 posts, 2 populated children

check( '2a  the total is the parent + descendants figure, not $term->count',
	strpos( $h, '__line-v">89<' ) !== false );

check( '2b  it asks for that with `cat`, which includes descendants',
	isset( $GLOBALS['LAST_QUERY']['cat'] ) && $GLOBALS['LAST_QUERY']['cat'] === 75
	&& ! isset( $GLOBALS['LAST_QUERY']['category__in'] ) );

check( '2c  populated topics are counted',
	strpos( $h, '__line-v">2<' ) !== false );

check( '2d  the newest post date is shown as a month',
	strpos( $h, 'August 2025' ) !== false );

check( '2e  cells are <div>, so they inherit none of the link affordances',
	strpos( $h, '<div class="vhh-hero-spotlight__line">' ) !== false
	&& strpos( $h, '<a class="vhh-hero-spotlight__line"' ) === false );

check( '2f  the band carries the shared lines class plus its own modifier',
	strpos( $h, '__slot--lines vhh-hero-spotlight__slot--facts' ) !== false );

// Healthcare News has posts but no child terms.
$h = render( 15 );
check( '2g  a section with no sub-sections drops the Topics cell rather than showing 0',
	substr_count( $h, '__line-ico' ) === 2
	&& strpos( $h, '>Topics<' ) === false );

// White Papers is in the registry but has nothing in it.
$h = render( 21 );
check( '2h  an empty section drops the band entirely, never "0 articles"',
	strpos( $h, '__slot--facts' ) === false
	&& strpos( $h, '__slot-label' ) === false );

check( '2i  ...but still renders its headline, lede and card',
	strpos( $h, '>White Papers</h1>' ) !== false
	&& strpos( $h, 'Longer, on purpose' ) !== false );

// Singular/plural, which a bare "Articles" label would get wrong.
$h = render( 66, array(), array(), array( 66 => array( 'total' => 1, 'time' => 1756598400 ) ) + $DEFAULT_POSTS );
check( '2j  one article reads "Article", not "Articles"',
	strpos( $h, '__line-k">Article<' ) !== false
	&& strpos( $h, '__line-k">Articles<' ) === false );
set_posts( $DEFAULT_POSTS );


/* ===================================================================== */
section( '3. Sub-categories' );
/* ===================================================================== */

$h = render( 87 );   // Food & Nutrition, child of Gastro Living

/*
 * The eyebrow inherits the parent's KIND, not its NAME. Naming it here put
 * "Gastro Living" in the crumb and "GASTRO LIVING" in the pill directly below
 * — one fact, two labels, forty pixels apart. Only visible by looking at it.
 */
check( '3a  the eyebrow inherits the family label from the parent section',
	strpos( $h, '__eyebrow">Living with it<' ) !== false );

check( '3a2 ...and does not repeat the parent name the breadcrumb already carries',
	substr_count( body( $h ), 'Gastro Living' ) === 1 );

/*
 * The name is stored as `Food &amp; Nutrition`. Decoded once and escaped once
 * it comes back out as `Food &amp; Nutrition` and draws as "Food & Nutrition".
 * Escaping the stored form directly would emit `Food &amp;amp; Nutrition`,
 * which draws the entity on the page — the bug this check exists for.
 */
check( '3b  the headline is the child term, correctly escaped exactly once',
	strpos( $h, '<h1 class="vhh-hero-spotlight__title">Food &amp; Nutrition</h1>' ) !== false
	&& strpos( $h, '&amp;amp;' ) === false );

check( '3b2 the breadcrumb escapes it the same way the headline does',
	strpos( $h, 'aria-current="page">Food &amp; Nutrition</span>' ) !== false );

check( '3c  a breadcrumb back to the parent, as a real <nav> with a name',
	strpos( $h, '<nav class="vhh-hero-spotlight__crumb" aria-label="Breadcrumb">' ) !== false
	&& strpos( $h, 'href="https://example.test/category/content-gastro-living/"' ) !== false );

check( '3d  the current page is marked as such for a screen reader',
	strpos( $h, 'aria-current="page"' ) !== false );

check( '3e  it inherits the PARENT\'s card — the child has no registry entry',
	strpos( $h, 'Written for the day you are having' ) !== false );

check( '3f  no lede is invented for a child with no description of its own',
	strpos( $h, '__intro' ) === false );

check( '3g  ...but its own description is used when it has one',
	strpos( render( 87, array(), array( 87 => 'Eating well around a gut condition.' ) ),
		'__intro">Eating well around a gut condition.<' ) !== false );

check( '3h  the band reports the CHILD\'s figures, not the parent\'s',
	strpos( $h, '__line-v">16<' ) !== false
	&& strpos( $h, '__line-v">89<' ) === false );


/* ===================================================================== */
section( '4. Photograph resolution' );
/* ===================================================================== */

/*
 * The registry names a file per top-level section. None is on disk yet — the
 * OpenRouter account that generates them was out of credit on 2026-08-31 — so
 * the live path today is the motif, and that is what these assert. When a
 * photograph lands, 4a flips to the __media branch and 4b starts asserting the
 * <img>; both are written so the file's presence decides, not a hard-coded
 * expectation, and 4h holds the shape contract either way.
 */
$photo_on_disk = file_exists( $THEME . '/assets/img/heroes/categories/clinical-reviews.jpg' );

$h = render( 17 );
if ( $photo_on_disk ) {
	check( '4a  a section with its photograph on disk renders the <img> branch',
		strpos( $h, '__media' ) !== false && strpos( $h, '__motif' ) === false );
	check( '4b  ...with the intrinsic box the file is cut to, so it cannot shift the headline',
		strpos( $h, 'width="1400" height="876"' ) !== false );
	check( '4c  ...at the registry focal point',
		strpos( $h, 'object-position: 52% 20%' ) !== false );
} else {
	check( '4a  a section with no photograph on disk falls back to the motif, not a broken <img>',
		strpos( $h, '__motif' ) !== false && strpos( $h, '<img' ) === false );
	check( '4b  the motif is hidden from assistive technology',
		strpos( $h, '__motif" aria-hidden="true"' ) !== false );
	check( '4c  and the section is flagged so the CSS can give it its top padding back',
		strpos( $h, ' has-motif"' ) !== false );
}

// An admin upload takes over from both.
$h = render( 17, array( 'vance_cat_photo_17' => 'https://cdn.test/mine.jpg' ) );
check( '4d  the per-category Photograph control wins over everything',
	strpos( $h, 'src="https://cdn.test/mine.jpg"' ) !== false );

check( '4e  ...and carries empty alt, because the registry description is about a different picture',
	strpos( $h, 'src="https://cdn.test/mine.jpg" alt=""' ) !== false
	|| preg_match( '/mine\.jpg"\s+alt=""/', $h ) === 1 );

// The parent's upload re-skins every child. This is the rule the brief asked
// for: "sub-category pages can use the same image as the parent page".
$h = render( 87, array( 'vance_cat_photo_75' => 'https://cdn.test/living.jpg' ) );
check( '4f  a sub-category inherits the parent\'s photograph',
	strpos( $h, 'https://cdn.test/living.jpg' ) !== false );

check( '4g  ...unless it has one of its own',
	strpos( render( 87, array(
		'vance_cat_photo_75' => 'https://cdn.test/living.jpg',
		'vance_cat_photo_87' => 'https://cdn.test/food.jpg',
	) ), 'https://cdn.test/food.jpg' ) !== false );

// The legacy dark-band image must NOT leak onto the pale band.
$h = render( 17, array( 'vance_cat_hero_17' => 'https://cdn.test/dark-navy-veiled.png' ) );
check( '4h  the legacy dark-band Hero Image is never used by this hero',
	strpos( $h, 'dark-navy-veiled' ) === false );


/* ===================================================================== */
section( '5. Customizer overrides that already existed' );
/* ===================================================================== */

check( '5a  the Tagline control still overrides the eyebrow',
	strpos( render( 17, array( 'vance_cat_tagline_17' => 'Evidence-Based Excellence' ) ),
		'__eyebrow">Evidence-Based Excellence<' ) !== false );

check( '5b  a sub-category\'s own Tagline beats the parent name',
	strpos( render( 87, array( 'vance_cat_tagline_87' => 'On the plate' ) ),
		'__eyebrow">On the plate<' ) !== false );

/*
 * Two title keys, and both are read on purpose: the three dark heroes have
 * always read vance_cat_hero_title_override_<id>, while the Customizer control
 * in functions.php registers vance_cat_title_<id>. They have never matched,
 * which is why that field has never done anything. Reading both is what makes
 * it work without discarding a value saved under either.
 */
check( '5c  the key the Customizer actually registers is honoured',
	strpos( render( 17, array( 'vance_cat_title_17' => 'The Evidence Desk' ) ),
		'>The Evidence Desk</h1>' ) !== false );

check( '5d  the key the old templates read is honoured',
	strpos( render( 17, array( 'vance_cat_hero_title_override_17' => 'Reviews' ) ),
		'>Reviews</h1>' ) !== false );

check( '5e  when both are set, the templates\' long-standing key wins',
	strpos( render( 17, array(
		'vance_cat_hero_title_override_17' => 'Old Key',
		'vance_cat_title_17'               => 'New Key',
	) ), '>Old Key</h1>' ) !== false );

check( '5f  a term description beats the registry lede',
	strpos( render( 17, array(), array( 17 => 'Edited in Posts &rarr; Categories.' ) ),
		'Edited in Posts' ) !== false );

check( '5g  ...and HTML in that description is stripped, not printed',
	strpos( render( 17, array(), array( 17 => '<p>Plain <b>text</b> only.</p>' ) ),
		'__intro">Plain text only.<' ) !== false );


/* ===================================================================== */
section( '6. Sections the registry has never heard of' );
/* ===================================================================== */

$h = render( 66 );   // Brand New Section — no registry entry, no parent

check( '6a  still renders rather than falling through to nothing',
	strpos( $h, '>Brand New Section</h1>' ) !== false );

check( '6b  gets the generic card, so the two-column grid has no hole in it',
	strpos( $h, 'Checked before it is published' ) !== false );

check( '6c  no eyebrow is invented for it',
	strpos( $h, '__eyebrow' ) === false );

check( '6d  and the motif stands in for the photograph it does not have',
	strpos( $h, '__motif' ) !== false );


/* ===================================================================== */
section( '7. What the renderer refuses' );
/* ===================================================================== */

$GLOBALS['TERMS'][ 500 ] = new WP_Term( 500, 'Low FODMAP', 'low-fodmap', 0, 'post_tag' );

ob_start();
$r = vance_render_category_hero( $GLOBALS['TERMS'][500] );
$h = ob_get_clean();
check( '7a  a tag term renders nothing and reports false, so archive.php can fall through',
	$r === false && trim( $h ) === '' );

$GLOBALS['QUERIED'] = null;
ob_start();
$r = vance_render_category_hero();
$h = ob_get_clean();
check( '7b  no queried object is not a fatal — it is a false',
	$r === false && trim( $h ) === '' );


/* ===================================================================== */
section( '8. The registry and the icon set' );
/* ===================================================================== */

$meta = vance_category_hero_meta();

check( '8a  every live top-level category with content has an entry',
	isset( $meta['content-clinical-reviews'], $meta['content-gastro-living'], $meta['content-healthcare-news'] ) );

$bad = array();
foreach ( $meta as $slug => $m ) {
	foreach ( array( 'eyebrow', 'intro', 'image', 'focal', 'card' ) as $k ) {
		if ( empty( $m[ $k ] ) ) { $bad[] = "$slug.$k"; }
	}
	foreach ( array( 'icon', 'title', 'text' ) as $k ) {
		if ( empty( $m['card'][ $k ] ) ) { $bad[] = "$slug.card.$k"; }
	}
}
check( '8b  no entry is missing a field: ' . implode( ', ', $bad ), $bad === array() );

$missing = array();
foreach ( $meta as $slug => $m ) {
	if ( vance_category_hero_icon( $m['card']['icon'] ) === '' ) { $missing[] = $slug; }
}
check( '8c  every card icon exists in the icon set: ' . implode( ', ', $missing ), $missing === array() );

foreach ( array( 'stack', 'tiles', 'clock', 'folder' ) as $needed ) {
	check( "8d  the band/fallback icon '$needed' is drawn",
		vance_category_hero_icon( $needed ) !== '' );
}

check( '8e  an unknown icon key returns nothing rather than a broken <svg>',
	vance_category_hero_icon( 'no-such-icon' ) === '' );

$images = array();
foreach ( $meta as $m ) { $images[] = $m['image']; }
check( '8f  no two sections declare the same photograph',
	count( $images ) === count( array_unique( $images ) ) );

$bad_focal = array();
foreach ( $meta as $slug => $m ) {
	if ( ! preg_match( '/^\d{1,3}% \d{1,3}%$/', $m['focal'] ) ) { $bad_focal[] = $slug; }
}
check( '8g  every focal point is a valid object-position pair: ' . implode( ', ', $bad_focal ),
	$bad_focal === array() );

/*
 * Three copies of the same motif now exist — here, inc/page-hero-spotlight.php
 * and inc/legal-hero.php. Two identical gradient ids on one document repaint
 * whichever one lost, and nothing about that failure looks like a bug in this
 * file, so it is asserted rather than trusted to a comment.
 */
$motif = vance_category_hero_motif();
$page  = file_get_contents( $THEME . '/inc/page-hero-spotlight.php' );
$legal = file_get_contents( $THEME . '/inc/legal-hero.php' );
preg_match_all( '/id="([A-Za-z0-9]+)"/', $motif, $ids );
$clash = array();
foreach ( $ids[1] as $id ) {
	if ( strpos( $page, '"' . $id . '"' ) !== false || strpos( $legal, '"' . $id . '"' ) !== false ) {
		$clash[] = $id;
	}
}
check( '8h  its gradient ids collide with neither other motif: ' . implode( ', ', $clash ),
	$ids[1] !== array() && $clash === array() );


/* ===================================================================== */
section( '9. The three templates (SOURCE assertions — see the file header)' );
/* ===================================================================== */

$templates = array(
	'archive.php',
	'template-parts/subcategory-grouped-archive.php',
	'category-content-healthcare-news.php',
);

foreach ( $templates as $t ) {
	$src = file_get_contents( $THEME . '/' . $t );

	// Strip comments before looking for the call, so commenting it out fails.
	$live = preg_replace( array( '#/\*.*?\*/#s', '#^\s*//.*$#m' ), '', $src );

	check( "9a  $t calls the renderer, and not from inside a comment",
		strpos( $live, 'vance_render_category_hero(' ) !== false );

	check( "9b  $t no longer builds the 350px dark band for a category",
		strpos( $src, 'height: 350px' ) === false
			|| $t === 'archive.php' );  // archive.php keeps it for tag/date/post-type
}

$arch = file_get_contents( $THEME . '/archive.php' );
check( '9c  archive.php gates the old band behind the renderer\'s return value',
	strpos( $arch, 'if ( ! $vance_arch_hero_done ) :' ) !== false
	&& strpos( $arch, 'endif; // ! $vance_arch_hero_done' ) !== false );

/*
 * $vance_cat arrived with the hero block in the grouped template and is read
 * 80 lines further down to find the child terms every post buckets into.
 * Removing the hero without re-declaring it left the page syntactically valid
 * and semantically empty — every group collapsed into "ungrouped" — which is
 * exactly the kind of break `php -l` cannot see.
 */
$grp = file_get_contents( $THEME . '/template-parts/subcategory-grouped-archive.php' );
check( '9d  the grouped template still declares $vance_cat before it groups on it',
	preg_match( '/\$vance_cat\s*=\s*get_queried_object\(\)/', $grp ) === 1
	&& strpos( $grp, '$vance_cat' ) < strpos( $grp, "'parent'     => \$vance_cat->term_id" ) );

$fn = file_get_contents( $THEME . '/functions.php' );
// Comments stripped for the same reason as the templates above: a commented-out
// require still matches a plain strpos, and the theme then has no renderer at
// all while this check stays green.
$fn_live = preg_replace( array( '#/\*.*?\*/#s', '#^\s*//.*$#m' ), '', $fn );

check( '9e  functions.php loads the renderer, and not from inside a comment',
	strpos( $fn_live, "require_once get_template_directory() . '/inc/category-hero.php';" ) !== false );

check( '9f  and registers the Photograph SETTING the renderer reads',
	strpos( $fn_live, "add_setting( \"vance_cat_photo_{\$cat->term_id}\"" ) !== false );

check( '9g  ...with a control attached to it, or the field never appears',
	strpos( $fn_live, "WP_Customize_Image_Control( \$wp_customize, \"vance_cat_photo_{\$cat->term_id}\"" ) !== false );


/* ===================================================================== */
section( '10. The inline stylesheet' );
/* ===================================================================== */

$GLOBALS['QUERIED'] = $GLOBALS['TERMS'][17];
ob_start(); vance_category_hero_styles(); $again = ob_get_clean();
check( '10a  printed once per request — a template calling the hero twice cannot emit it twice',
	trim( $again ) === '' );

// Re-read it from a fresh process boundary: the static above has fired, so
// take the block from the source instead.
$src = file_get_contents( $THEME . '/inc/category-hero.php' );
preg_match( '/<style id="vhh-category-hero-css">(.*?)<\/style>/s', $src, $m );
$css = isset( $m[1] ) ? $m[1] : '';

check( '10b  the block exists and is not empty', strlen( trim( $css ) ) > 200 );

check( '10c  every radius is a token with a literal fallback (CLAUDE.md §5)',
	preg_match( '/border-radius:\s*\d/', $css ) === 0 );

$main = file_get_contents( $THEME . '/assets/css/main.css' );
/*
 * Every class the renderer emits has to be styled SOMEWHERE — either by the
 * committed .vhh-hero-spotlight block or by the inline one above. A class
 * emitted and styled by neither renders as an unstyled div, which is invisible
 * to every other check in this file and instantly obvious to an eye.
 */
$h = render( 87 );   // the sub-category: the only render that emits every class
preg_match_all( '/class="([^"]+)"/', $h, $cm );
$emitted = array();
foreach ( $cm[1] as $attr ) {
	foreach ( explode( ' ', $attr ) as $c ) {
		if ( strpos( $c, 'vhh-hero-spotlight' ) === 0 ) { $emitted[ $c ] = true; }
	}
}
/*
 * Five classes are structural wrappers or markers that carry no rules of their
 * own, and inc/page-hero-spotlight.php and inc/gi-hero.php emit every one of
 * them unstyled too. They are listed rather than pattern-matched so that a
 * SIXTH unstyled class — the actual failure mode, a component that renders as
 * a bare div — still turns this check red.
 */
$structural = array(
	'vhh-hero-spotlight--page',        // set marker, no rules anywhere
	'vhh-hero-spotlight__copy',        // grid child; the grid is on __inner
	'vhh-hero-spotlight__slot-wrap',   // label + band grouping
	'vhh-hero-spotlight__slot--lines', // markup-shape marker; rules are on __line
	'vhh-hero-spotlight__slot--facts', // scoping modifier; every rule is `--facts .__line-v`
	'vhh-hero-spotlight__card-body',   // flex child of __card
);
$unstyled = array();
foreach ( array_keys( $emitted ) as $c ) {
	// Per-section classes are hooks for future overrides, not styles.
	if ( strpos( $c, 'vhh-hero-spotlight--cat-' ) === 0 ) { continue; }
	if ( in_array( $c, $structural, true ) ) { continue; }
	/*
	 * Block form, not mere presence. `.vhh-hero-spotlight__crumb a { }` proves
	 * nothing about `.vhh-hero-spotlight__crumb { }` — deleting the latter
	 * leaves the breadcrumb as unstyled inline text while the former still
	 * satisfies a presence check. mutate-category.py caught exactly that.
	 */
	if ( ! css_has( $main, $c, true ) && ! css_has( $css, $c, true ) ) {
		$unstyled[] = $c;
	}
}

/*
 * The allowlist must stay honest: if one of those five ever GAINS rules, the
 * exemption is stale and should go. Cheap to assert, and it cannot pass by
 * accident.
 */
$now_styled = array();
foreach ( $structural as $c ) {
	if ( css_has( $main, $c, true ) || css_has( $css, $c, true ) ) {
		$now_styled[] = $c;
	}
}
check( '10d2 no allowlisted structural class has quietly gained rules: ' . implode( ', ', $now_styled ),
	$now_styled === array() );
check( '10d  every emitted class is styled by main.css or the inline block: ' . implode( ', ', $unstyled ),
	$unstyled === array() );

check( '10e  the shared band rules it relies on really are in main.css',
	css_has( $main, 'vhh-hero-spotlight__line-ico', true )
	&& css_has( $main, 'vhh-hero-spotlight__slot', true )
	&& css_has( $main, 'vhh-hero-spotlight__eyebrow', true ) );

/*
 * The band's cells are <div>s and must stay inert. That holds only while every
 * link affordance in main.css is scoped to `a.__line` — an UNSCOPED
 * `.vhh-hero-spotlight__line:hover` would give a non-link cell a hover fill and
 * a pointer, which is a lie about what clicking does.
 *
 * Checked as "the scoped rule exists AND no unscoped one does", because the
 * scoped selector alone is a substring of nothing useful: main.css also carries
 * `a.__line:hover .__line-v`, so merely finding the text proves little.
 */
check( '10f  main.css declares the hover fill on the anchor, not the cell',
	preg_match( '/a\.vhh-hero-spotlight__line:hover\s*\{/', $main ) === 1 );

check( '10g  ...and nowhere gives a bare .__line a hover state',
	preg_match( '/(^|[^a-z.])\.vhh-hero-spotlight__line:hover/m', $main ) === 0 );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
