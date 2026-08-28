<?php
/**
 * Build the Vance Hub mega menu. Run with:  wp eval-file build-mega-menu.php
 *
 * Builds into a NEW menu ("Primary - mega") and does NOT assign it to a
 * location. The existing "Primary - main" menu is never touched, so the site is
 * unchanged until the location is switched deliberately, and rollback is one
 * command.
 *
 * Idempotent: re-running wipes and rebuilds the menu and its widgets.
 */

if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$MENU_NAME   = 'Primary - mega';
$WIDGET_BASES = array( 'vhh_nav_tiles', 'vhh_nav_cta', 'vhh_nav_featured' );

function say( $m ) { echo $m . "\n"; }

/** Monotonic menu-item position counter. A static beats a global here because
 *  wp eval-file's variable scope is not guaranteed to be the global scope. */
function vh_pos() { static $n = 0; return ++$n; }

/** Panel width, in Max Mega Menu's 12-column grid. */
if ( ! defined( 'VH_PANEL_COLS' ) ) { define( 'VH_PANEL_COLS', 12 ); }

/* ---------------------------------------------------------------- helpers */

/** Resolve a site path to a page ID, or 0. */
function vh_page( $path ) {
	$p = get_page_by_path( trim( $path, '/' ) );
	return $p ? (int) $p->ID : 0;
}

/** Resolve a category slug to a term ID, or 0. */
function vh_cat( $slug ) {
	$t = get_category_by_slug( $slug );
	return $t ? (int) $t->term_id : 0;
}

/**
 * Add one menu item. $spec keys: title, path (page), cat (category slug),
 * url (raw), parent, position.
 */
function vh_add( $menu_id, $spec ) {
	$args = array(
		'menu-item-title'     => $spec['title'],
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => isset( $spec['parent'] ) ? (int) $spec['parent'] : 0,
		'menu-item-position'  => isset( $spec['position'] ) ? (int) $spec['position'] : 0,
	);

	if ( ! empty( $spec['path'] ) ) {
		$pid = vh_page( $spec['path'] );
		if ( ! $pid ) { say( "  !! MISSING PAGE: {$spec['path']} ({$spec['title']})" ); return 0; }
		$args['menu-item-type']      = 'post_type';
		$args['menu-item-object']    = 'page';
		$args['menu-item-object-id'] = $pid;
	} elseif ( ! empty( $spec['cat'] ) ) {
		$tid = vh_cat( $spec['cat'] );
		if ( ! $tid ) { say( "  !! MISSING CATEGORY: {$spec['cat']} ({$spec['title']})" ); return 0; }
		$args['menu-item-type']      = 'taxonomy';
		$args['menu-item-object']    = 'category';
		$args['menu-item-object-id'] = $tid;
	} else {
		$args['menu-item-type'] = 'custom';
		$args['menu-item-url']  = isset( $spec['url'] ) ? $spec['url'] : '#';
	}

	$id = wp_update_nav_menu_item( $menu_id, 0, $args );
	if ( is_wp_error( $id ) ) { say( '  !! ' . $id->get_error_message() ); return 0; }
	return (int) $id;
}

/** Write Max Mega Menu per-item settings, merged over the plugin defaults. */
function vh_mm( $item_id, $settings ) {
	$defaults = Mega_Menu_Nav_Menus::get_menu_item_defaults();
	update_post_meta( $item_id, '_megamenu', array_merge( $defaults, $settings ) );
}

/* ------------------------------------------------- 1. reset menu + widgets */

$menu = wp_get_nav_menu_object( $MENU_NAME );
if ( $menu ) {
	foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}
	say( "Reused existing menu '{$MENU_NAME}' (#{$menu->term_id}), cleared its items." );
} else {
	$new = wp_create_nav_menu( $MENU_NAME );
	if ( is_wp_error( $new ) ) { say( 'FATAL: ' . $new->get_error_message() ); return; }
	$menu = wp_get_nav_menu_object( $new );
	say( "Created menu '{$MENU_NAME}' (#{$menu->term_id})." );
}
$MENU_ID = (int) $menu->term_id;

// Drop every previously-created panel widget so re-runs do not accumulate.
$sidebars = wp_get_sidebars_widgets();
$mega_sb  = isset( $sidebars['mega-menu'] ) ? (array) $sidebars['mega-menu'] : array();
foreach ( $WIDGET_BASES as $base ) {
	$opt = get_option( 'widget_' . $base );
	if ( is_array( $opt ) ) {
		foreach ( array_keys( $opt ) as $k ) { if ( $k !== '_multiwidget' ) { unset( $opt[ $k ] ); } }
		update_option( 'widget_' . $base, $opt );
	}
	$mega_sb = array_values( array_filter( $mega_sb, function ( $wid ) use ( $base ) {
		return strpos( $wid, $base . '-' ) !== 0;
	} ) );
}
$sidebars['mega-menu'] = $mega_sb;
wp_set_sidebars_widgets( $sidebars );
say( 'Cleared previous panel widgets.' );

/* ---------------------------------------------------------- 2. the tree */

$created = array();

/** Add a top-level panel item. */
function vh_top( $menu_id, $title, $spec, $disable_link = false ) {
	$spec['title'] = $title;
	$spec['position'] = vh_pos();
	$id = vh_add( $menu_id, $spec );
	if ( ! $id ) { return 0; }
	vh_mm( $id, array(
		'type'          => 'megamenu',
		'panel_columns' => VH_PANEL_COLS,
		'align'         => 'bottom-left',
		'disable_link'  => $disable_link ? 'true' : 'false',
	) );
	return $id;
}

/** Add a column heading (2nd level) under a panel, with its span and order. */
function vh_col( $menu_id, $parent, $title, $spec, $span, $order, $disable_link = false ) {
	$spec['title']    = $title;
	$spec['parent']   = $parent;
	$spec['position'] = vh_pos();
	$id = vh_add( $menu_id, $spec );
	if ( ! $id ) { return 0; }
	vh_mm( $id, array(
		'mega_menu_columns' => $span,
		'mega_menu_order'   => array( $parent => $order ),
		'disable_link'      => $disable_link ? 'true' : 'false',
	) );
	return $id;
}

/** Add a plain link (3rd level) under a column heading. */
function vh_link( $menu_id, $parent, $title, $spec ) {
	$spec['title']    = $title;
	$spec['parent']   = $parent;
	$spec['position'] = vh_pos();
	return vh_add( $menu_id, $spec );
}

// ---------- THE HUB ----------
say( '' );
say( 'THE HUB' );
$hub = vh_top( $MENU_ID, 'THE HUB', array( 'url' => '#' ), true );
$created['hub'] = $hub;

$c = vh_col( $MENU_ID, $hub, 'Start here', array( 'url' => '#' ), 3, 0, true );
vh_link( $MENU_ID, $c, 'Get Started',        array( 'path' => 'get-started-today' ) );
vh_link( $MENU_ID, $c, 'How to Use the Hub', array( 'path' => 'how-to-use-the-hub' ) );
vh_link( $MENU_ID, $c, 'User Guide',         array( 'path' => 'user-guide' ) );
vh_link( $MENU_ID, $c, 'Create an Account',  array( 'path' => 'register' ) );

$c = vh_col( $MENU_ID, $hub, 'Free Health Tools', array( 'path' => 'free-health-tools' ), 3, 1 );
vh_link( $MENU_ID, $c, 'Malnutrition Calculator', array( 'path' => 'malnutrition-calculator' ) );
vh_link( $MENU_ID, $c, 'Gastro Health Survey',    array( 'path' => 'gastro-health-survey' ) );
vh_link( $MENU_ID, $c, 'Recipes & Meal Planner',  array( 'path' => 'gastro-recipies' ) );

$c = vh_col( $MENU_ID, $hub, 'Your Account', array( 'path' => 'dashboard' ), 3, 2 );
vh_link( $MENU_ID, $c, 'My Dashboard', array( 'path' => 'dashboard' ) );
vh_link( $MENU_ID, $c, 'My Notes',     array( 'path' => 'my-notes' ) );

$c = vh_col( $MENU_ID, $hub, 'For Professionals', array( 'path' => 'healthcare-professionals' ), 3, 4 );
vh_link( $MENU_ID, $c, 'HCP Hub',              array( 'path' => 'healthcare-professionals' ) );
vh_link( $MENU_ID, $c, 'Clinical Data Reviews', array( 'cat'  => 'content-clinical-reviews' ) );
vh_link( $MENU_ID, $c, 'Webinars & Courses',   array( 'path' => 'webinars-and-courses' ) );

$c = vh_col( $MENU_ID, $hub, 'Vance Medical', array( 'path' => 'about-us' ), 3, 5 );
vh_link( $MENU_ID, $c, 'Who We Are',   array( 'path' => 'about-us' ) );
vh_link( $MENU_ID, $c, 'Our Heritage', array( 'path' => 'our-heritage' ) );
vh_link( $MENU_ID, $c, 'Contact Us',   array( 'path' => 'contact-us' ) );

// ---------- KNOWLEDGEBASE ----------
say( '' );
say( 'KNOWLEDGEBASE' );
$kb = vh_top( $MENU_ID, 'KNOWLEDGEBASE', array( 'path' => 'knowledgebase' ) );
$created['kb'] = $kb;

$c = vh_col( $MENU_ID, $kb, 'Browse the library', array( 'path' => 'knowledgebase' ), 3, 0 );
vh_link( $MENU_ID, $c, 'All Articles',            array( 'path' => 'knowledgebase' ) );
vh_link( $MENU_ID, $c, 'Gastro Health Explained', array( 'path' => 'gastro-health-explained' ) );
vh_link( $MENU_ID, $c, 'Webinars & Courses',      array( 'path' => 'webinars-and-courses' ) );
vh_link( $MENU_ID, $c, 'Recipes & Meal Planner',  array( 'path' => 'gastro-recipies' ) );

$c = vh_col( $MENU_ID, $kb, 'By content type', array( 'url' => '#' ), 3, 1, true );
vh_link( $MENU_ID, $c, 'Gastro Living Insights', array( 'cat' => 'content-gastro-living' ) );
vh_link( $MENU_ID, $c, 'Gastro Health News',     array( 'cat' => 'content-healthcare-news' ) );
vh_link( $MENU_ID, $c, 'Clinical Data Reviews',  array( 'cat' => 'content-clinical-reviews' ) );

// ---------- CONDITIONS ----------
say( '' );
say( 'CONDITIONS' );
$cond = vh_top( $MENU_ID, 'CONDITIONS', array( 'path' => 'gastro-health-explained' ) );
$created['cond'] = $cond;

/* --------------------------------------------------------- 3. the widgets */

/**
 * Create one widget instance bound to a panel.
 *
 * mega_menu_columns is a scalar; mega_menu_order is keyed by the parent menu
 * item id — see megamenu.php:1061 and widget-manager.class.php:1213.
 */
function vh_widget( $base, $parent_item, $span, $order, $settings ) {
	$opt = get_option( 'widget_' . $base );
	if ( ! is_array( $opt ) ) { $opt = array( '_multiwidget' => 1 ); }

	$n = 1;
	while ( isset( $opt[ $n ] ) ) { $n++; }

	$opt[ $n ] = array_merge( $settings, array(
		'mega_menu_columns'        => $span,
		'mega_menu_parent_menu_id' => $parent_item,
		'mega_menu_order'          => array( $parent_item => $order ),
	) );
	$opt['_multiwidget'] = 1;
	update_option( 'widget_' . $base, $opt );

	$sb = wp_get_sidebars_widgets();
	if ( ! isset( $sb['mega-menu'] ) ) { $sb['mega-menu'] = array(); }
	$sb['mega-menu'][] = $base . '-' . $n;
	wp_set_sidebars_widgets( $sb );

	return $base . '-' . $n;
}

say( '' );
say( 'Widgets' );

$w = vh_widget( 'vhh_nav_cta', $created['hub'], 3, 3, array(
	'eyebrow'  => 'Always on',
	'heading'  => 'Ask VANCE-Ai anything about gut health',
	'text'     => "Evidence-based answers drawn from the Hub's own clinical library, any time of day.",
	'btn_text' => 'Start a chat',
	'btn_url'  => home_url( '/ask-ai/' ),
	'icon'     => 'sparkles',
) );
say( "  $w  -> THE HUB row 1" );

$w = vh_widget( 'vhh_nav_cta', $created['hub'], 6, 6, array(
	'eyebrow'  => 'For clinicians',
	'heading'  => 'Give your patients somewhere reliable to go',
	'text'     => 'Create a practitioner account to share tools and access the clinical library in full.',
	'btn_text' => 'Create a practitioner account',
	'btn_url'  => home_url( '/register/' ),
	'icon'     => 'shield',
) );
say( "  $w  -> THE HUB row 2" );

$w = vh_widget( 'vhh_nav_featured', $created['kb'], 6, 2, array(
	'title'     => 'Latest from the Hub',
	'cat'       => 0,
	'count'     => 2,
	'more_text' => 'See everything in the Knowledgebase',
	'more_url'  => home_url( '/knowledgebase/' ),
) );
say( "  $w  -> KNOWLEDGEBASE" );

$tiles = implode( "\n", array(
	'organ | Inflammatory Bowel Disease | The umbrella term - start here | ' . home_url( '/inflammatory-bowel-disease/' ),
	'pulse | Ulcerative Colitis | Symptoms, flares and treatment | ' . home_url( '/ulcerative-colitis/' ),
	'drop | Crohn\'s Disease | Diagnosis through to daily life | ' . home_url( '/crohns-disease/' ),
	'flask | Microscopic Colitis | Often missed, very treatable | ' . home_url( '/microscopic-colitis/' ),
	'clipboard | Irritable Bowel Syndrome | Triggers, testing and diet | ' . home_url( '/irritable-bowel-syndrome/' ),
	'ribbon | Colorectal Cancer | Screening and early signs | ' . home_url( '/colorectal-cancer/' ),
	'book | Diverticular Disease | And diverticulitis | ' . home_url( '/diverticular-disease/' ),
	'* quiz | Not sure where to start? | Take the short self-assessment and get a summary you can share with your clinician. | ' . home_url( '/gastro-health-survey/' ),
) );

$w = vh_widget( 'vhh_nav_tiles', $created['cond'], 12, 0, array(
	'title' => 'Understanding digestive conditions',
	'cols'  => 3,
	'tiles' => $tiles,
) );
say( "  $w  -> CONDITIONS" );

/* ------------------------------------------------------------- 4. summary */

say( '' );
say( '--- RESULT ---' );
$items = wp_get_nav_menu_items( $MENU_ID );
say( 'menu id: ' . $MENU_ID . '   items: ' . count( $items ) );
$loc = get_nav_menu_locations();
say( 'primary-menu location currently points at menu: ' . ( isset( $loc['primary-menu'] ) ? $loc['primary-menu'] : 'none' ) );
say( 'NOT assigned — site is unchanged. Assign deliberately when verified.' );
