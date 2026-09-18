<?php
/**
 * inc/customizer-structure.php: the map (three pure functions returning the
 * intended panel/section/control layout) and vance_customizer_structure_apply(),
 * which is hooked at customize_register priority 999 and pushes that map onto
 * a real WP_Customize_Manager.
 *
 * Section 1 is static analysis of the map alone: no WordPress needed.
 * Sections 2+ run vance_customizer_structure_apply() against a small fake
 * WP_Customize_Manager — plain objects with the id/title/priority/panel/section
 * properties the real Customizer classes expose, and the handful of methods
 * apply() actually calls (get_panel/add_panel/get_section/get_control/
 * controls/sections/panels/remove_section/remove_panel). This is the same
 * "recording stub, not a re-implementation" shape as hero-customizer.test.php.
 *
 * Every check here must be able to go red — confirmed by hand-sabotaging
 * inc/customizer-structure.php (flipping the cleanup's strpos() check, the
 * priority increment, and the null-title guard in turn) and watching this
 * suite fail, then restoring the exact original bytes.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- WordPress stub: apply() only ever calls add_action() at file scope. */
$GLOBALS['REGISTERED_ACTIONS'] = array();
function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
	$GLOBALS['REGISTERED_ACTIONS'][] = array( $hook, $cb, $priority );
}

require_once $THEME . '/inc/customizer-structure.php';

/* ---- the runner --------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }

/* ---- a minimal fake of the three WP_Customize_Manager collections ------- */

class FakePanel {
	public $id, $title, $priority;
	public function __construct( $id, $title, $priority ) {
		$this->id = $id; $this->title = $title; $this->priority = $priority;
	}
}
class FakeSection {
	public $id, $title, $priority, $panel;
	public function __construct( $id, $panel, $title, $priority = 10 ) {
		$this->id = $id; $this->panel = $panel; $this->title = $title; $this->priority = $priority;
	}
}
class FakeControl {
	public $id, $section;
	public function __construct( $id, $section ) {
		$this->id = $id; $this->section = $section;
	}
}

/**
 * Stands in for WP_Customize_Manager. Seeded directly via the public arrays
 * (add_panel_raw/add_section_raw/add_control_raw) so a test can register only
 * the fixture it needs, rather than replaying the whole real registration.
 */
class FakeCustomize {
	public $panels   = array();
	public $sections = array();
	public $controls = array();

	public function seed_panel( $id, $title, $priority ) {
		$this->panels[ $id ] = new FakePanel( $id, $title, $priority );
	}
	public function seed_section( $id, $panel, $title, $priority = 10 ) {
		$this->sections[ $id ] = new FakeSection( $id, $panel, $title, $priority );
	}
	public function seed_control( $id, $section ) {
		$this->controls[ $id ] = new FakeControl( $id, $section );
	}

	public function get_panel( $id )   { return isset( $this->panels[ $id ] )   ? $this->panels[ $id ]   : null; }
	public function get_section( $id ) { return isset( $this->sections[ $id ] ) ? $this->sections[ $id ] : null; }
	public function get_control( $id ) { return isset( $this->controls[ $id ] ) ? $this->controls[ $id ] : null; }

	public function add_panel( $id, $args = array() ) {
		$this->panels[ $id ] = new FakePanel( $id, $args['title'], $args['priority'] );
		return $this->panels[ $id ];
	}

	public function controls() { return array_values( $this->controls ); }
	public function sections() { return array_values( $this->sections ); }
	public function panels()   { return array_values( $this->panels ); }

	public function remove_section( $id ) { unset( $this->sections[ $id ] ); }
	public function remove_panel( $id )   { unset( $this->panels[ $id ] ); }
}


/* ========================================================================= *
 * 1. Map integrity — pure arrays, no Customizer involved.
 * ========================================================================= */
section( '1. Map integrity' );

$panels_map   = vance_customizer_structure_panels();
$sections_map = vance_customizer_structure_sections();
$controls_map = vance_customizer_structure_controls();

check( '1a  every panel id used as a sections() key exists in panels()',
	( function () use ( $panels_map, $sections_map ) {
		foreach ( array_keys( $sections_map ) as $panel_id ) {
			if ( ! array_key_exists( $panel_id, $panels_map ) ) { return false; }
		}
		return true;
	} )() );

check( '1b  no section id appears under two different panels',
	( function () use ( $sections_map ) {
		$owner = array();
		foreach ( $sections_map as $panel_id => $sections ) {
			foreach ( array_keys( $sections ) as $section_id ) {
				if ( isset( $owner[ $section_id ] ) && $owner[ $section_id ] !== $panel_id ) {
					return false;
				}
				$owner[ $section_id ] = $panel_id;
			}
		}
		return true;
	} )() );

check( '1c  within one panel, no two sections resolve to the same non-null title',
	( function () use ( $sections_map ) {
		foreach ( $sections_map as $panel_id => $sections ) {
			$titles = array_filter( array_values( $sections ), function ( $t ) { return null !== $t; } );
			if ( count( $titles ) !== count( array_unique( $titles ) ) ) { return false; }
		}
		return true;
	} )() );

check( '1d  panel priorities are unique',
	( function () use ( $panels_map ) {
		$priorities = array_map( function ( $spec ) { return $spec[1]; }, $panels_map );
		return count( $priorities ) === count( array_unique( $priorities ) );
	} )() );

check( '1e  every control-move target section is placed somewhere in the sections map',
	( function () use ( $sections_map, $controls_map ) {
		$all_sections = array();
		foreach ( $sections_map as $sections ) {
			foreach ( array_keys( $sections ) as $section_id ) { $all_sections[ $section_id ] = true; }
		}
		foreach ( $controls_map as $control_id => $target_section ) {
			if ( empty( $all_sections[ $target_section ] ) ) { return false; }
		}
		return true;
	} )() );


/* ========================================================================= *
 * 2. apply(): panels — retitle existing, create missing.
 * ========================================================================= */
section( '2. apply(): panel retitle vs creation' );

$c = new FakeCustomize();
// Existing panel, wrong title/priority — must be corrected in place.
$c->seed_panel( 'vance_brand_panel', 'Old Brand Title', 999 );
$c->seed_section( 'vance_header_nav', 'some_old_panel', 'Header navigation' );
$c->seed_control( 'x_header_nav_control', 'vance_header_nav' ); // keeps the section (and panel) "used"

// vance_footer_panel is NOT seeded at all — apply() must create it.
$c->seed_section( 'vance_newsletter', 'some_old_panel', 'Old newsletter title' );
$c->seed_control( 'x_newsletter_control', 'vance_newsletter' );

vance_customizer_structure_apply( $c );

check( '2a  an existing panel is retitled',
	$c->panels['vance_brand_panel']->title === 'Site · Brand & Header' );
check( '2b  ...and re-prioritised',
	$c->panels['vance_brand_panel']->priority === 10 );

check( '2c  a missing panel is created',
	isset( $c->panels['vance_footer_panel'] ) );
check( '2d  ...with the mapped title',
	isset( $c->panels['vance_footer_panel'] ) && $c->panels['vance_footer_panel']->title === 'Site · Footer' );
check( '2e  ...and the mapped priority',
	isset( $c->panels['vance_footer_panel'] ) && $c->panels['vance_footer_panel']->priority === 11 );


/* ========================================================================= *
 * 3. apply(): sections — moved, re-prioritised 10/20/30 in list order, and
 *    the null-title guard.
 * ========================================================================= */
section( '3. apply(): section move, priority sequence, null title' );

$c = new FakeCustomize();
// Three sections that belong (per the map) under vance_mobile_panel, in this
// order: vance_mobile_experience, vance_menu_promo_banners,
// vance_legal_heroes_mobile. Seed them out of order and under a foreign panel.
$c->seed_section( 'vance_legal_heroes_mobile', 'foreign_panel', 'Old title A' );
$c->seed_section( 'vance_mobile_experience',   'foreign_panel', 'Old title B' );
$c->seed_section( 'vance_menu_promo_banners',  'foreign_panel', 'Old title C' );
foreach ( array( 'vance_legal_heroes_mobile', 'vance_mobile_experience', 'vance_menu_promo_banners' ) as $sid ) {
	$c->seed_control( "x_{$sid}_control", $sid );
}
// custom_css: mapped title is null, so its registered title must survive.
$c->seed_panel( 'vance_advanced_panel', 'Old Advanced', 5 );
$c->seed_section( 'custom_css', 'foreign_panel', 'Additional CSS' );
$c->seed_control( 'x_custom_css_control', 'custom_css' );
// vhh_annotations is named in the map but never registered — must be a no-op.

vance_customizer_structure_apply( $c );

check( '3a  each of the three sections is re-parented to vance_mobile_panel',
	$c->sections['vance_mobile_experience']->panel === 'vance_mobile_panel'
	&& $c->sections['vance_menu_promo_banners']->panel === 'vance_mobile_panel'
	&& $c->sections['vance_legal_heroes_mobile']->panel === 'vance_mobile_panel' );

check( '3b  priorities follow the map\'s list order, not registration order: 10, 20, 30',
	$c->sections['vance_mobile_experience']->priority === 10
	&& $c->sections['vance_menu_promo_banners']->priority === 20
	&& $c->sections['vance_legal_heroes_mobile']->priority === 30 );

check( '3c  titles are rewritten to the map\'s wording',
	$c->sections['vance_mobile_experience']->title === 'Bottom navigation & phone components'
	&& $c->sections['vance_menu_promo_banners']->title === 'Menu drawer banners'
	&& $c->sections['vance_legal_heroes_mobile']->title === 'Policy page heroes on phones' );

check( '3d  a null-title entry (custom_css) keeps its originally registered title',
	$c->sections['custom_css']->title === 'Additional CSS' );

check( '3e  a mapped section that was never registered (vhh_annotations) is silently absent, not fatal',
	! isset( $c->sections['vhh_annotations'] ) );


/* ========================================================================= *
 * 4. apply(): control moves only when both control and target section exist.
 * ========================================================================= */
section( '4. apply(): control moves' );

$c = new FakeCustomize();
$c->seed_section( 'vance_hero_settings', 'vance_homepage_panel', 'Hero (classic) + design switch' );
$c->seed_control( 'vance_home_hero_overlay', 'vance_hero_overlays_old' );
$c->seed_control( 'x_hero_settings_control', 'vance_hero_settings' ); // keeps the section used

// vance_evidence_hero_overlay targets vance_evidence_hero, which is deliberately
// NOT registered here — the control must stay put.
$c->seed_control( 'vance_evidence_hero_overlay', 'vance_evidence_hero_overlay_old' );

// vance_askai_hero_overlay is not registered as a control at all.

vance_customizer_structure_apply( $c );

check( '4a  a control whose target section exists is moved to it',
	$c->controls['vance_home_hero_overlay']->section === 'vance_hero_settings' );

check( '4b  a control whose target section does NOT exist stays where it was',
	$c->controls['vance_evidence_hero_overlay']->section === 'vance_evidence_hero_overlay_old' );

check( '4c  a control that was never registered causes no error (reaching here is the assertion)',
	true );


/* ========================================================================= *
 * 5. Cleanup: vance_ sections/panels with zero controls are removed;
 *    non-vance_ ones, and panels whose only section is non-vance_, are kept.
 * ========================================================================= */
section( '5. Cleanup after the moves' );

$c = new FakeCustomize();

// A vance_ section with zero controls pointing at it -- must be removed --
// and it is the only section in its panel, so the panel must go too.
$c->seed_panel( 'vance_content_widgets_panel', 'Content Widgets', 33 );
$c->seed_section( 'vance_cw_1', 'vance_content_widgets_panel', 'Widget 1' );
// (deliberately: no control anywhere has section === 'vance_cw_1')

// A non-vance_ section with zero controls -- core-style id -- must survive,
// and it must keep its (non-vance_) panel alive too.
$c->seed_panel( 'nav_menus', 'Menus', 100 );
$c->seed_section( 'add_menu', 'nav_menus', 'Add a Menu' );
// (also zero controls -- the point is that the vance_ prefix, not the empty
// control count, is what triggers removal)

// A panel whose only section is a non-vance_, core-registered section
// (custom_css) must be kept even though every control that touches it is,
// itself, zero -- because non-vance_ sections are never removed regardless.
$c->seed_panel( 'vance_advanced_panel', 'Website Functions', 70 );
$c->seed_section( 'custom_css', 'vance_advanced_panel', 'Additional CSS' );

vance_customizer_structure_apply( $c );

check( '5a  a vance_ section left with zero controls is removed',
	! isset( $c->sections['vance_cw_1'] ) );

check( '5b  a vance_ panel left with zero surviving sections is removed',
	! isset( $c->panels['vance_content_widgets_panel'] ) );

check( '5c  a non-vance_ section with zero controls is NOT removed',
	isset( $c->sections['add_menu'] ) );

check( '5d  a non-vance_ panel is NOT removed',
	isset( $c->panels['nav_menus'] ) );

check( '5e  a panel whose only section is a non-vance_ core section (custom_css) is kept',
	isset( $c->panels['vance_advanced_panel'] ) );

check( '5f  ...and that section is still there too',
	isset( $c->sections['custom_css'] ) );


/* ========================================================================= *
 * 6. Source-level guard against a typo'd section id in the map.
 *
 * Every vance_-prefixed section id in vance_customizer_structure_sections()
 * must either appear as a string literal in one of the theme's registration
 * files, or belong to one of the three families confirmed (by reading the
 * generating loops) to be built at runtime:
 *   - vance_cw_1..5           functions.php:  'vance_cw_' . $cwn            (for $cwn = 1..VANCE_CONTENT_WIDGET_INSTANCES)
 *   - vance_gi_cond_{key}     inc/customizer-gi-health.php: "vance_gi_cond_{$key}" over the 7-entry $conditions map
 *   - vance_hero_slideN_settings  functions.php: 'vance_hero_slide' . $hs . '_settings' (for $hs = 2..VANCE_HERO_SLIDE_INSTANCES, = 5)
 * A section id that is none of these and not found anywhere is exactly the
 * failure mode apply() hides: get_section() returns null and the entry is
 * skipped without a word.
 * ========================================================================= */
section( '6. Every vance_ section id is real, not a typo' );

$registration_files = array(
	$THEME . '/functions.php',
	$THEME . '/customizer-pages.php',
	$THEME . '/inc/customizer-gi-health.php',
	$THEME . '/inc/page-hero-spotlight.php',
	$THEME . '/inc/hero-mobile.php',
	$THEME . '/inc/dashboard-features.php',
);
$haystack = '';
foreach ( $registration_files as $f ) {
	check( "registration file exists: $f", file_exists( $f ) );
	$haystack .= (string) file_get_contents( $f ) . "\n";
}

// Generated families confirmed by reading the loops themselves (see comment
// above) -- verified statically, not guessed.
$generated_cw        = array( 'vance_cw_1', 'vance_cw_2', 'vance_cw_3', 'vance_cw_4', 'vance_cw_5' );
$generated_gi_cond    = array(
	'vance_gi_cond_ibd', 'vance_gi_cond_uc', 'vance_gi_cond_crohns', 'vance_gi_cond_mc',
	'vance_gi_cond_ibs', 'vance_gi_cond_crc', 'vance_gi_cond_div',
);
$generated_hero_slide = array(
	'vance_hero_slide2_settings', 'vance_hero_slide3_settings',
	'vance_hero_slide4_settings', 'vance_hero_slide5_settings',
);
$known_generated = array_merge( $generated_cw, $generated_gi_cond, $generated_hero_slide );

$unverified = array();
foreach ( $sections_map as $sections ) {
	foreach ( array_keys( $sections ) as $section_id ) {
		if ( 0 !== strpos( $section_id, 'vance_' ) ) { continue; } // e.g. custom_css
		if ( in_array( $section_id, $known_generated, true ) ) { continue; }
		if ( false === strpos( $haystack, $section_id ) ) {
			$unverified[] = $section_id;
		}
	}
}
check( 'every vance_ section id is either a literal in the registration files or a confirmed generated family',
	array() === $unverified );
if ( $unverified ) {
	echo "     unverified ids: " . implode( ', ', $unverified ) . "\n";
}

// And the reverse sanity check for the three generated families themselves:
// the loop that builds them really does iterate the range the map assumes.
check( '6a  the vance_cw_ loop pattern is present in functions.php',
	false !== strpos( file_get_contents( $THEME . '/functions.php' ), "'vance_cw_' . \$cwn" ) );
check( '6b  the vance_gi_cond_ pattern is present in inc/customizer-gi-health.php',
	false !== strpos( file_get_contents( $THEME . '/inc/customizer-gi-health.php' ), '"vance_gi_cond_{$key}"' ) );
check( '6c  the vance_hero_slideN_settings pattern is present in functions.php',
	false !== strpos( file_get_contents( $THEME . '/functions.php' ), "'vance_hero_slide' . \$hs . '_settings'" ) );


/* ========================================================================= */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
