<?php
/**
 * VanceHubClub home page settings: block order, counts and texts.
 *
 * front-page-hub.php prints the home page. This file holds what an administrator
 * can change on it from the Customizer:
 *
 *   - which blocks show, and in which order (the sortable "Home page blocks"
 *     control, setting vance_hub_block_order);
 *   - how many articles, recipes and quotes each block shows (five numbers);
 *   - the wording of the hero, the Self-Assessment card, the three facts and the
 *     Join block (22 texts). An empty text always means the default wording;
 *   - the four tool cards: which tool each slot shows (or none), and an
 *     optional title, text, button, link and image for each slot
 *     (vance_hub_home_tools()).
 *
 * The registries and readers are pure where they can be (they call only
 * vance_get_theme_mod and the sanitiser helpers), so tests can call them.
 * Defaults equal the wording and numbers that front-page-hub.php had before this
 * file existed: with nothing saved, the page is byte for byte the same.
 *
 * Needs inc/hub-layout.php (vance_hub_sanitize_checkbox, the section ids).
 * Loaded from functions.php directly after hub-layout.php. To back the feature
 * out, delete that require line and restore the literals in front-page-hub.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Blocks: which show, and in which order
 * ====================================================================== */

/**
 * The nine blocks of the home page that can move or hide, in default order.
 *
 * The hero and the three facts are not in this list. They always show, first.
 * The member bar always shows above them.
 *
 * @return string[] Block id => label.
 */
function vance_hub_home_blocks() {
	return array(
		'tools'      => __( 'Tools', 'vance-health-hub' ),
		'inside'     => __( 'Inside the Hub', 'vance-health-hub' ),
		'conditions' => __( 'Conditions', 'vance-health-hub' ),
		'community'  => __( 'Community and recipes', 'vance-health-hub' ),
		'promo'      => __( 'Promo block', 'vance-health-hub' ),
		'library'    => __( 'From the library', 'vance-health-hub' ),
		'sections'   => __( 'Latest by section', 'vance-health-hub' ),
		'more'       => __( 'More to read', 'vance-health-hub' ),
		'join'       => __( 'Join block', 'vance-health-hub' ),
	);
}

/**
 * The three retired on/off switches and the block each one used to control.
 *
 * Their controls are gone from the Customizer. Their settings are still
 * registered, and a saved "off" still hides its block, until the administrator
 * saves the block order for the first time.
 *
 * @return string[] Block id => theme mod name.
 */
function vance_hub_home_legacy_switches() {
	return array(
		'promo'    => 'vance_hub_show_promo',
		'sections' => 'vance_hub_show_sections',
		'more'     => 'vance_hub_show_more',
	);
}

/**
 * Sanitise the saved block order: a comma-separated list of block ids.
 *
 * Keeps known ids only, in the order given, each once. The empty string is a
 * valid value: it means every block is hidden.
 *
 * @param mixed $value Raw value.
 * @return string Comma-separated block ids.
 */
function vance_hub_sanitize_block_order( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$known = array_keys( vance_hub_home_blocks() );
	$out   = array();
	foreach ( explode( ',', $value ) as $id ) {
		$id = trim( $id );
		if ( in_array( $id, $known, true ) && ! in_array( $id, $out, true ) ) {
			$out[] = $id;
		}
	}
	return implode( ',', $out );
}

/**
 * The block order for a site where the order was never saved: the default
 * order without each block whose retired switch is saved as off.
 *
 * One source of truth: vance_hub_block_order() returns this when nothing is
 * saved, and the Customizer registers it as the default of the setting, so the
 * control, the preview and the live page agree.
 *
 * @return string[] Block ids in print order.
 */
function vance_hub_block_order_default() {
	$order = array_keys( vance_hub_home_blocks() );
	foreach ( vance_hub_home_legacy_switches() as $block => $mod ) {
		if ( ! vance_hub_sanitize_checkbox( vance_get_theme_mod( $mod, true ) ) ) {
			$order = array_values( array_diff( $order, array( $block ) ) );
		}
	}
	return $order;
}

/**
 * The block ids to print, in order.
 *
 * A saved block order wins, and the three retired switches are then ignored.
 * With no saved order (the mod was never saved, which is not the same as saved
 * empty), the result is vance_hub_block_order_default().
 *
 * @return string[] Block ids in print order.
 */
function vance_hub_block_order() {
	// null is the "never saved" sentinel: an empty string is a saved value.
	$saved = vance_get_theme_mod( 'vance_hub_block_order', null );
	if ( is_string( $saved ) ) {
		$clean = vance_hub_sanitize_block_order( $saved );
		return '' === $clean ? array() : explode( ',', $clean );
	}

	return vance_hub_block_order_default();
}

/**
 * True when a block prints.
 *
 * @param string $id Block id.
 * @return bool
 */
function vance_hub_block_on( $id ) {
	return in_array( $id, vance_hub_block_order(), true );
}

/**
 * Where "See what members get" and the other join links point.
 *
 * The Join block when it prints, else the sign-up page. A link to an anchor
 * that is not on the page would go nowhere.
 *
 * @return string
 */
function vance_hub_join_href() {
	return vance_hub_block_on( 'join' ) ? '#vhub-join' : home_url( '/login/?tab=signup' );
}

/* =========================================================================
 * Counts
 * ====================================================================== */

/**
 * The five counts: default, smallest, largest and label.
 *
 * Each row is array( default, min, max, label ), in that order.
 *
 * @return array[] Theme mod name => row.
 */
function vance_hub_home_counts() {
	return array(
		'vance_hub_count_sections' => array( 3, 1, 6, __( 'Articles in each "Latest by section" column', 'vance-health-hub' ) ),
		'vance_hub_count_more'     => array( 6, 3, 12, __( '"More to read" articles', 'vance-health-hub' ) ),
		'vance_hub_count_library'  => array( 4, 2, 7, __( 'Library articles (one large and a list)', 'vance-health-hub' ) ),
		'vance_hub_count_recipes'  => array( 2, 1, 4, __( 'Recipes in each recipe list', 'vance-health-hub' ) ),
		'vance_hub_count_quotes'   => array( 4, 1, 4, __( 'Community quotes', 'vance-health-hub' ) ),
	);
}

/**
 * Bring a raw value into the range of one count.
 *
 * @param mixed $value Raw value.
 * @param array $row   Registry row: default, min, max.
 * @return int The value as a whole number inside the range, or the default
 *             when the value is not a number.
 */
function vance_hub_clamp_count( $value, $row ) {
	if ( ! is_numeric( $value ) ) {
		return (int) $row[0];
	}
	return max( (int) $row[1], min( (int) $row[2], (int) $value ) );
}

/**
 * One count, as the template reads it.
 *
 * @param string $mod Theme mod name (a key of vance_hub_home_counts()).
 * @return int The saved value inside its range, or the default when the value
 *             is missing or is not a number. 0 for an unknown name.
 */
function vance_hub_count( $mod ) {
	$counts = vance_hub_home_counts();
	if ( ! isset( $counts[ $mod ] ) ) {
		return 0;
	}
	return vance_hub_clamp_count( vance_get_theme_mod( $mod, $counts[ $mod ][0] ), $counts[ $mod ] );
}

/**
 * Sanitise one count setting.
 *
 * WordPress passes the setting object as the second argument of a sanitise
 * callback, so one function serves all five settings: it reads the range from
 * the registry by the setting id. A plain id string works too.
 *
 * @param mixed                      $value   Raw value.
 * @param WP_Customize_Setting|string $setting The setting, or its id.
 * @return int
 */
function vance_hub_sanitize_count( $value, $setting = '' ) {
	$id     = is_object( $setting ) && isset( $setting->id ) ? (string) $setting->id : (string) $setting;
	$counts = vance_hub_home_counts();
	if ( ! isset( $counts[ $id ] ) ) {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}
	return vance_hub_clamp_count( $value, $counts[ $id ] );
}

/* =========================================================================
 * Texts
 * ====================================================================== */

/**
 * The 22 editable texts of the home page.
 *
 * 'default' is the wording front-page-hub.php had before the texts became
 * editable. 'group' is where the text shows. 'type' is the Customizer control
 * type, and picks the sanitiser.
 *
 * The note under the Self-Assessment button ("This is not a diagnosis") is a
 * safety statement. It stays in the template and is not in this list.
 *
 * @return array[] Theme mod name => array( default, label, group, type ).
 */
function vance_hub_home_text_defaults() {
	return array(
		// Hero.
		'vance_hub_text_hero_pill'       => array(
			'default' => __( 'Your Gastro Health Community', 'vance-health-hub' ),
			'label'   => __( 'Hero: small label above the title', 'vance-health-hub' ),
			'group'   => 'hero',
			'type'    => 'text',
		),
		'vance_hub_text_hero_title'      => array(
			'default' => __( 'What do you need help with', 'vance-health-hub' ),
			'label'   => __( 'Hero: title', 'vance-health-hub' ),
			'group'   => 'hero',
			'type'    => 'text',
		),
		'vance_hub_text_hero_accent'     => array(
			'default' => __( 'today?', 'vance-health-hub' ),
			'label'   => __( 'Hero: highlighted end of the title', 'vance-health-hub' ),
			'group'   => 'hero',
			'type'    => 'text',
		),
		'vance_hub_text_hero_sub'        => array(
			'default' => __( 'Take the Gastro Self-Assessment, plan meals, read the science and get plain answers from our VANCE-Ai.', 'vance-health-hub' ),
			'label'   => __( 'Hero: text under the title', 'vance-health-hub' ),
			'group'   => 'hero',
			'type'    => 'textarea',
		),
		// Self-Assessment card.
		'vance_hub_text_survey_label'    => array(
			'default' => __( 'Gastro Self-Assessment', 'vance-health-hub' ),
			'label'   => __( 'Self-Assessment card: small label', 'vance-health-hub' ),
			'group'   => 'survey',
			'type'    => 'text',
		),
		'vance_hub_text_survey_title'    => array(
			'default' => __( 'Not sure where to start?', 'vance-health-hub' ),
			'label'   => __( 'Self-Assessment card: title', 'vance-health-hub' ),
			'group'   => 'survey',
			'type'    => 'text',
		),
		'vance_hub_text_survey_text'     => array(
			'default' => __( 'Answer a few short questions about you and your gut. The Hub then points you to the guides, tools and recipes that fit.', 'vance-health-hub' ),
			'label'   => __( 'Self-Assessment card: text', 'vance-health-hub' ),
			'group'   => 'survey',
			'type'    => 'textarea',
		),
		'vance_hub_text_survey_button'   => array(
			'default' => __( 'Start the survey', 'vance-health-hub' ),
			'label'   => __( 'Self-Assessment card: button', 'vance-health-hub' ),
			'group'   => 'survey',
			'type'    => 'text',
		),
		// Fact band.
		'vance_hub_text_fact_1_title'    => array(
			'default' => __( 'Free Forever', 'vance-health-hub' ),
			'label'   => __( 'Fact 1: title', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		'vance_hub_text_fact_1_text'     => array(
			'default' => __( 'Every tool available 24/7', 'vance-health-hub' ),
			'label'   => __( 'Fact 1: text', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		'vance_hub_text_fact_2_title'    => array(
			'default' => __( 'Activity driven', 'vance-health-hub' ),
			'label'   => __( 'Fact 2: title', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		'vance_hub_text_fact_2_text'     => array(
			'default' => __( 'Save searches, answers and results.', 'vance-health-hub' ),
			'label'   => __( 'Fact 2: text', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		'vance_hub_text_fact_3_title'    => array(
			'default' => __( 'Verified Science', 'vance-health-hub' ),
			'label'   => __( 'Fact 3: title', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		'vance_hub_text_fact_3_text'     => array(
			'default' => __( 'Every clinical source referenced.', 'vance-health-hub' ),
			'label'   => __( 'Fact 3: text', 'vance-health-hub' ),
			'group'   => 'facts',
			'type'    => 'text',
		),
		// Join block, visitor variant.
		'vance_hub_text_join_pill'       => array(
			'default' => __( 'Membership is free', 'vance-health-hub' ),
			'label'   => __( 'Join block, visitors: small label', 'vance-health-hub' ),
			'group'   => 'join_visitor',
			'type'    => 'text',
		),
		'vance_hub_text_join_title'      => array(
			'default' => __( 'Your place is ready.', 'vance-health-hub' ),
			'label'   => __( 'Join block, visitors: title', 'vance-health-hub' ),
			'group'   => 'join_visitor',
			'type'    => 'text',
		),
		'vance_hub_text_join_text'       => array(
			'default' => __( 'Members keep their survey answers, tool results and meal plans. They can save articles and notes, and their VANCE-Ai chats stay in their dashboard.', 'vance-health-hub' ),
			'label'   => __( 'Join block, visitors: text', 'vance-health-hub' ),
			'group'   => 'join_visitor',
			'type'    => 'textarea',
		),
		'vance_hub_text_join_card'       => array(
			'default' => __( 'Take your place', 'vance-health-hub' ),
			'label'   => __( 'Join block, visitors: title of the sign-up card', 'vance-health-hub' ),
			'group'   => 'join_visitor',
			'type'    => 'text',
		),
		'vance_hub_text_join_button'     => array(
			'default' => __( 'Create my free account', 'vance-health-hub' ),
			'label'   => __( 'Join block, visitors: button', 'vance-health-hub' ),
			'group'   => 'join_visitor',
			'type'    => 'text',
		),
		// Join block, member variant.
		'vance_hub_text_member_title'    => array(
			'default' => __( 'Pick up where you left off.', 'vance-health-hub' ),
			'label'   => __( 'Join block, members: title', 'vance-health-hub' ),
			'group'   => 'join_member',
			'type'    => 'text',
		),
		'vance_hub_text_member_text'     => array(
			'default' => __( 'Your saved articles, notes, results and chats are in your dashboard.', 'vance-health-hub' ),
			'label'   => __( 'Join block, members: text', 'vance-health-hub' ),
			'group'   => 'join_member',
			'type'    => 'textarea',
		),
		'vance_hub_text_member_button'   => array(
			'default' => __( 'Go to My Hub', 'vance-health-hub' ),
			'label'   => __( 'Join block, members: button', 'vance-health-hub' ),
			'group'   => 'join_member',
			'type'    => 'text',
		),
	);
}

/**
 * One home page text: the saved wording, or the default when the saved value is
 * empty. An empty text always means the default.
 *
 * @param string $mod Theme mod name (a key of vance_hub_home_text_defaults()).
 * @return string Plain text, not yet escaped. '' for an unknown name.
 */
function vance_hub_home_text( $mod ) {
	$defaults = vance_hub_home_text_defaults();
	if ( ! isset( $defaults[ $mod ] ) ) {
		return '';
	}
	$default = $defaults[ $mod ]['default'];
	$saved   = vance_get_theme_mod( $mod, $default );
	$saved   = is_string( $saved ) ? trim( $saved ) : '';
	return '' !== $saved ? $saved : $default;
}

/* =========================================================================
 * Tool cards
 * ====================================================================== */

/**
 * The tools a card slot can show, and the "hide" choice.
 *
 * The keys of the first four are the 'mod' values of vance_hub_tools().
 *
 * @return string[] Pick => label.
 */
function vance_hub_tool_picks() {
	return array(
		'survey'    => __( 'Self-Assessment', 'vance-health-hub' ),
		'nutrition' => __( 'Nutrition check', 'vance-health-hub' ),
		'recipes'   => __( 'Meal planner', 'vance-health-hub' ),
		'discounts' => __( 'Discounts and benefits', 'vance-health-hub' ),
		'none'      => __( 'Hide this card', 'vance-health-hub' ),
	);
}

/**
 * The tool that a slot shows when nothing is saved: today's order.
 *
 * @param int $slot Slot number, 1 to 4.
 * @return string A pick. 'none' for a slot that does not exist.
 */
function vance_hub_tool_pick_default( $slot ) {
	$defaults = array(
		1 => 'survey',
		2 => 'nutrition',
		3 => 'recipes',
		4 => 'discounts',
	);
	return isset( $defaults[ (int) $slot ] ) ? $defaults[ (int) $slot ] : 'none';
}

/**
 * Sanitise the pick of one card slot.
 *
 * WordPress passes the setting object as the second argument, so one function
 * serves all four settings: it reads the slot from the setting id
 * (vance_hub_tool_{slot}_pick). A plain id string works too.
 *
 * @param mixed                       $value   Raw value.
 * @param WP_Customize_Setting|string $setting The setting, or its id.
 * @return string A known pick. An unknown value gives the default of the slot,
 *                or 'none' when the slot cannot be read from the id.
 */
function vance_hub_sanitize_tool_pick( $value, $setting = '' ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( array_key_exists( $value, vance_hub_tool_picks() ) ) {
		return $value;
	}
	$id = is_object( $setting ) && isset( $setting->id ) ? (string) $setting->id : (string) $setting;
	if ( preg_match( '/^vance_hub_tool_([1-4])_pick$/', $id, $m ) ) {
		return vance_hub_tool_pick_default( (int) $m[1] );
	}
	return 'none';
}

/**
 * Put the slot settings on the standard cards. Pure: no theme mod is read.
 *
 * Each slot gives at most one card, in slot order. A slot whose pick is 'none'
 * (or is not a standard tool) gives no card. Otherwise the slot starts from the
 * standard card of its tool and then applies its own values:
 *
 *   - a non-empty title, text or button replaces title, desc or cta;
 *   - a non-empty link replaces href;
 *   - an image id whose attachment has a URL replaces img, and alt becomes the
 *     alt text of that attachment. The standard alt describes another picture,
 *     so it is never kept with a custom image. An empty alt is correct when the
 *     media library has none. When the attachment has no URL, the standard
 *     image and alt stay.
 *
 * The same tool may sit in two slots. Nothing is removed as a duplicate.
 *
 * @param array[] $standard Cards in the shape of vance_hub_tools().
 * @param array[] $slots    One array per slot, in slot order, with the keys
 *                          pick, title, text, button, link and image.
 * @return array[] Cards to print, in the shape of vance_hub_tools().
 */
function vance_hub_home_tools_apply( $standard, $slots ) {
	$by_mod = array();
	foreach ( $standard as $card ) {
		if ( isset( $card['mod'] ) ) {
			$by_mod[ $card['mod'] ] = $card;
		}
	}

	$cards = array();
	foreach ( $slots as $slot ) {
		$pick = isset( $slot['pick'] ) ? $slot['pick'] : 'none';
		if ( ! is_string( $pick ) || ! isset( $by_mod[ $pick ] ) ) {
			continue;
		}
		$card = $by_mod[ $pick ];

		foreach ( array( 'title' => 'title', 'text' => 'desc', 'button' => 'cta', 'link' => 'href' ) as $key => $field ) {
			$value = isset( $slot[ $key ] ) && is_string( $slot[ $key ] ) ? trim( $slot[ $key ] ) : '';
			if ( '' !== $value ) {
				$card[ $field ] = $value;
			}
		}

		$image = isset( $slot['image'] ) ? max( 0, (int) $slot['image'] ) : 0;
		if ( $image > 0 ) {
			$url = wp_get_attachment_image_url( $image, 'large' );
			if ( is_string( $url ) && '' !== $url ) {
				$card['img'] = $url;
				$card['alt'] = trim( (string) get_post_meta( $image, '_wp_attachment_image_alt', true ) );
			}
		}

		$cards[] = $card;
	}
	return $cards;
}

/**
 * The tool cards the home page prints, in slot order.
 *
 * With nothing saved this equals vance_hub_tools(). Only the home page calls
 * this: the article sidebar and the dashboard keep reading vance_hub_tools().
 *
 * @return array[] Cards in the shape of vance_hub_tools(). Empty when every
 *                 slot is hidden.
 */
function vance_hub_home_tools() {
	$slots = array();
	for ( $i = 1; $i <= 4; $i++ ) {
		$pick_id = "vance_hub_tool_{$i}_pick";
		$slot    = array(
			'pick' => vance_hub_sanitize_tool_pick( vance_get_theme_mod( $pick_id, vance_hub_tool_pick_default( $i ) ), $pick_id ),
		);
		foreach ( array( 'title', 'text', 'button', 'link' ) as $key ) {
			$saved        = vance_get_theme_mod( "vance_hub_tool_{$i}_{$key}", '' );
			$slot[ $key ] = is_string( $saved ) ? trim( $saved ) : '';
		}
		$slot['image'] = max( 0, (int) vance_get_theme_mod( "vance_hub_tool_{$i}_image", 0 ) );
		$slots[]       = $slot;
	}
	return vance_hub_home_tools_apply( vance_hub_tools(), $slots );
}

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * Register the home page controls: the block order and the five counts in
 * "Home page: blocks and counts", and the 22 texts in "Home page: text".
 *
 * This is a function of its own, hooked on customize_register at the same
 * priority as vance_hub_customize_register() and added after it. So the panel
 * and its sections exist when this runs, and the new code stays in the new
 * file. The tile controls and the three retired switches stay in
 * inc/hub-layout.php.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_home_customize_register( $wp_customize ) {
	$section = 'vance_hub_sec_home';

	// The block order. The default is vance_hub_block_order_default(): the full
	// order minus each block whose retired switch is saved off. The control and
	// the Customizer preview read this default while nothing is saved, so they
	// show what the live page prints. The reader reads the raw mod, so "never
	// saved" stays different from "saved empty".
	$wp_customize->add_setting( 'vance_hub_block_order', array(
		'default'           => implode( ',', vance_hub_block_order_default() ),
		'sanitize_callback' => 'vance_hub_sanitize_block_order',
	) );
	$order_args = array(
		'label'       => __( 'Home page blocks', 'vance-health-hub' ),
		'description' => __( 'Drag to set the order. Remove the tick to hide a block. The hero and the three facts always show first.', 'vance-health-hub' ),
		'section'     => $section,
		'priority'    => 10,
	);
	if ( class_exists( 'Vance_Customize_Sortable_Sections_Control' ) ) {
		$available = array();
		foreach ( vance_hub_home_blocks() as $id => $label ) {
			$available[ $id ] = array( 'label' => $label, 'group' => __( 'Home page', 'vance-health-hub' ) );
		}
		$order_args['available'] = $available;
		$wp_customize->add_control( new Vance_Customize_Sortable_Sections_Control( $wp_customize, 'vance_hub_block_order', $order_args ) );
	} else {
		// No sortable control class (a test, or the class file is missing): a
		// plain text control, so the setting still has a control.
		$order_args['type'] = 'text';
		$wp_customize->add_control( 'vance_hub_block_order', $order_args );
	}

	// The five counts, priorities 20 to 60.
	$priority = 10;
	foreach ( vance_hub_home_counts() as $mod => $row ) {
		$priority += 10;
		$wp_customize->add_setting( $mod, array(
			'default'           => $row[0],
			'sanitize_callback' => 'vance_hub_sanitize_count',
		) );
		$wp_customize->add_control( $mod, array(
			'label'       => $row[3],
			/* translators: 1: smallest number, 2: largest number, 3: default number. */
			'description' => sprintf( __( 'Use a number from %1$d to %2$d. The default is %3$d.', 'vance-health-hub' ), $row[1], $row[2], $row[0] ),
			'section'     => $section,
			'type'        => 'number',
			'priority'    => $priority,
			'input_attrs' => array( 'min' => $row[1], 'max' => $row[2], 'step' => 1 ),
		) );
	}

	// The 22 texts, in the order of the registry: hero, survey, facts, visitor
	// join, member join. One priority each.
	$where = array(
		'hero'         => __( 'This text shows in the hero at the top of the home page.', 'vance-health-hub' ),
		'survey'       => __( 'This text shows in the Self-Assessment card in the hero. The note under the button is fixed.', 'vance-health-hub' ),
		'facts'        => __( 'This text shows in the three facts under the hero.', 'vance-health-hub' ),
		'join_visitor' => __( 'This text shows in the Join block for visitors who are not signed in.', 'vance-health-hub' ),
		'join_member'  => __( 'This text shows in the Join block for signed-in members.', 'vance-health-hub' ),
	);
	$seen     = array();
	$priority = 0;
	foreach ( vance_hub_home_text_defaults() as $mod => $row ) {
		$priority += 10;
		$description = __( 'Leave empty to use the default.', 'vance-health-hub' );
		if ( ! isset( $seen[ $row['group'] ] ) ) {
			$seen[ $row['group'] ] = true;
			$description          .= ' ' . $where[ $row['group'] ];
		}
		$wp_customize->add_setting( $mod, array(
			'default'           => $row['default'],
			'sanitize_callback' => 'textarea' === $row['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $mod, array(
			'label'       => $row['label'],
			'description' => $description,
			'section'     => 'vance_hub_sec_home_text',
			'type'        => $row['type'],
			'priority'    => $priority,
		) );
	}

	// The four tool cards. Six controls each: priorities 10 to 15 for card 1,
	// 20 to 25 for card 2, and so on.
	$tools_section = 'vance_hub_sec_home_tools';
	for ( $i = 1; $i <= 4; $i++ ) {
		$base = $i * 10;

		$wp_customize->add_setting( "vance_hub_tool_{$i}_pick", array(
			'default'           => vance_hub_tool_pick_default( $i ),
			'sanitize_callback' => 'vance_hub_sanitize_tool_pick',
		) );
		$wp_customize->add_control( "vance_hub_tool_{$i}_pick", array(
			/* translators: %d: card number, 1 to 4. */
			'label'       => sprintf( __( 'Card %d: tool', 'vance-health-hub' ), $i ),
			'description' => __( 'An empty field below uses the standard wording of the selected tool. The standard text of the Self-Assessment and of the meal planner shows live numbers.', 'vance-health-hub' ),
			'section'     => $tools_section,
			'type'        => 'select',
			'priority'    => $base,
			'choices'     => vance_hub_tool_picks(),
		) );

		/* translators: %d: card number, 1 to 4. Each label below. */
		$fields = array(
			'title'  => array( __( 'Card %d: title', 'vance-health-hub' ), 'text', 'sanitize_text_field' ),
			'text'   => array( __( 'Card %d: text', 'vance-health-hub' ), 'textarea', 'sanitize_textarea_field' ),
			'button' => array( __( 'Card %d: button', 'vance-health-hub' ), 'text', 'sanitize_text_field' ),
			'link'   => array( __( 'Card %d: link', 'vance-health-hub' ), 'url', 'esc_url_raw' ),
		);
		$offset = 0;
		foreach ( $fields as $key => $field ) {
			$offset++;
			$wp_customize->add_setting( "vance_hub_tool_{$i}_{$key}", array(
				'default'           => '',
				'sanitize_callback' => $field[2],
			) );
			$wp_customize->add_control( "vance_hub_tool_{$i}_{$key}", array(
				'label'    => sprintf( $field[0], $i ),
				'section'  => $tools_section,
				'type'     => $field[1],
				'priority' => $base + $offset,
			) );
		}

		$wp_customize->add_setting( "vance_hub_tool_{$i}_image", array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		) );
		$image_args = array(
			/* translators: %d: card number, 1 to 4. */
			'label'    => sprintf( __( 'Card %d: image', 'vance-health-hub' ), $i ),
			'section'  => $tools_section,
			'priority' => $base + 5,
		);
		if ( class_exists( 'WP_Customize_Media_Control' ) ) {
			$image_args['mime_type'] = 'image';
			$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "vance_hub_tool_{$i}_image", $image_args ) );
		} else {
			// No core media control (a test): a plain number control, so the
			// setting still has a control.
			$image_args['type'] = 'number';
			$wp_customize->add_control( "vance_hub_tool_{$i}_image", $image_args );
		}
	}
}
add_action( 'customize_register', 'vance_hub_home_customize_register' );
