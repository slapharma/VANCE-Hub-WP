<?php
/**
 * VanceHubClub "Reset this section" buttons.
 *
 * Every section of the VanceHubClub panel of the Customizer, except three, gets
 * a "Reset this section" button as its last control. The button sets every
 * setting of that section back to its default. It changes the Customizer state
 * only: nothing is saved and nothing changes on the site until the
 * administrator selects Publish. Select Close instead of Publish to drop it.
 *
 * Which sections get a button. vance_hub_reset_sections() takes the sections of
 * vance_hub_customizer_sections() (inc/hub-layout.php) and removes the ones in
 * vance_hub_reset_excluded(). A new section therefore gets a button by default.
 * The three sections that never get one:
 *
 *   - vance_home_layout ("On or off", section 1). A reset would set the site
 *     layout to classic and remove the go-live time. That turns VanceHubClub
 *     off for every visitor and member, and it is not a reset of text.
 *   - vance_hub_sec_checkin ("Daily check-in"). Its settings hold the consent
 *     text and the three questions. A reset would change the agreement text,
 *     so each member would have to agree again. The answers are health data.
 *     A project rule keeps this section out of any bulk change.
 *   - vance_hub_sec_flare ("Flare plan"). Its settings hold the consent text
 *     and the urgent help text. A reset would change the agreement text, and
 *     it would clear the urgent help text, so the flare card then does not
 *     show. A clinician must write that text. The plan is health data, and a
 *     project rule keeps this section out of any bulk change.
 *
 * How the data reaches the script. At customize_controls_enqueue_scripts the
 * code reads the live Customizer manager: for each reset section it takes every
 * control of that section that has a setting, and maps setting id to the default
 * of that setting. No list of setting ids is written by hand, so a new setting
 * is reset without a change here. The map is printed as JSON, so a boolean, a
 * number and a string keep their type in the script. assets/js/hub-customizer-reset.js
 * then calls setting.set( default ) for each entry, and shows a notice.
 *
 * A setting without a control (the three retired switches of the home page) is
 * not in the map: the administrator cannot see it, so the button leaves it alone.
 *
 * The button is a control without a setting ('settings' => array()), so it
 * saves nothing and sits in no Customizer change set.
 *
 * The functions in the first block read no WordPress state, so tests can call
 * them with stubs.
 *
 * Needs inc/hub-layout.php (vance_hub_customizer_sections). Loaded from
 * functions.php directly after inc/hub-community-settings.php.
 *
 * To back the feature out: delete the require line of this file in functions.php,
 * and delete assets/js/hub-customizer-reset.js. The buttons then no longer
 * exist. Nothing is saved by this feature, so nothing else needs a clean up. The
 * small listener that customizer-sortable.js got (the setting to list direction)
 * can stay: with no reset button, nothing calls it and it does no harm.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Pure helpers (no WordPress calls)
 * ====================================================================== */

/**
 * The sections that never get a reset button. See the file docblock for why.
 *
 * @return string[] Section ids.
 */
function vance_hub_reset_excluded() {
	return array( 'vance_home_layout', 'vance_hub_sec_checkin', 'vance_hub_sec_flare' );
}

/**
 * The sections that get a reset button, in panel order.
 *
 * @return string[] Section ids.
 */
function vance_hub_reset_sections() {
	return array_values( array_diff( array_keys( vance_hub_customizer_sections() ), vance_hub_reset_excluded() ) );
}

/**
 * Id of the reset control of a section.
 *
 * @param string $section Section id.
 * @return string
 */
function vance_hub_reset_control_id( $section ) {
	return 'vance_hub_reset_' . $section;
}

/**
 * The words the button and the script use.
 *
 * 'confirm' holds %s, which the script replaces with the section title.
 *
 * @return string[] Key => text: button, description, confirm, done.
 */
function vance_hub_reset_texts() {
	return array(
		'button'      => __( 'Reset this section', 'vance-health-hub' ),
		'description' => __( 'This sets every setting of this section back to its default. Nothing changes on the site until you select Publish.', 'vance-health-hub' ),
		/* translators: %s: title of the section. */
		'confirm'     => __( 'Reset every setting in "%s" to its default? Nothing changes on the site until you select Publish.', 'vance-health-hub' ),
		'done'        => __( 'This section is reset. Select Publish to make the change live.', 'vance-health-hub' ),
		'unavailable' => __( 'This section cannot be reset here. Reload the page and try again.', 'vance-health-hub' ),
	);
}

/**
 * The inline script that gives the reset data to the page.
 *
 * wp_json_encode() returns false when a value is not valid UTF-8, and a script
 * "window.vanceHubReset = ;" is a syntax error that stops every button without a
 * message. So when the encoding fails, this prints valid data with no sections
 * (the script then tells the administrator that the section cannot be reset). The
 * second encoding holds the fixed texts only. If that fails too, a literal empty
 * object is used.
 *
 * @param array $data From vance_hub_reset_script_data().
 * @return string One JavaScript assignment that always ends with a semicolon.
 */
function vance_hub_reset_inline_script( array $data ) {
	$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	$json  = wp_json_encode( $data, $flags );
	if ( ! is_string( $json ) || '' === $json ) {
		$texts = vance_hub_reset_texts();
		$json  = wp_json_encode(
			array(
				'sections'    => (object) array(),
				'confirm'     => $texts['confirm'],
				'done'        => $texts['done'],
				'unavailable' => $texts['unavailable'],
			),
			$flags
		);
		if ( ! is_string( $json ) || '' === $json ) {
			$json = '{"sections":{}}';
		}
	}
	return 'window.vanceHubReset = ' . $json . ';';
}

/**
 * From the controls of the manager: section id => ( setting id => default ).
 *
 * A control without a setting (the reset buttons) adds nothing. A control with
 * several settings adds each of them.
 *
 * @param iterable $controls Control objects with a section and a settings array
 *                           (each setting has an id and a default).
 * @return array[]
 */
function vance_hub_reset_collect( $controls ) {
	$found = array();
	foreach ( $controls as $control ) {
		if ( ! is_object( $control ) || empty( $control->section ) || empty( $control->settings ) || ! is_array( $control->settings ) ) {
			continue;
		}
		foreach ( $control->settings as $setting ) {
			if ( ! is_object( $setting ) || ! isset( $setting->id ) || '' === (string) $setting->id ) {
				continue;
			}
			$found[ (string) $control->section ][ (string) $setting->id ] = isset( $setting->default ) ? $setting->default : '';
		}
	}
	return $found;
}

/**
 * The reset map: for each reset section, the default of every setting.
 *
 * A section with no setting is left out, so the script never offers a reset that
 * has nothing to reset.
 *
 * @param string[] $sections Section ids that get a button (vance_hub_reset_sections()).
 * @param array[]  $found    Section id => ( setting id => default ), from vance_hub_reset_collect().
 * @return array[] Section id => ( setting id => default ), in the order of $sections.
 */
function vance_hub_reset_map( array $sections, array $found ) {
	$map = array();
	foreach ( $sections as $section ) {
		if ( isset( $found[ $section ] ) && is_array( $found[ $section ] ) && ! empty( $found[ $section ] ) ) {
			$map[ $section ] = $found[ $section ];
		}
	}
	return $map;
}

/**
 * What the script receives.
 *
 * Each section has a title and a defaults object. The defaults keep their PHP
 * type when printed as JSON: true and false stay booleans, whole numbers stay
 * numbers, text stays text.
 *
 * @param array[]  $map    From vance_hub_reset_map().
 * @param string[] $titles Section id => title.
 * @return array
 */
function vance_hub_reset_script_data( array $map, array $titles ) {
	$texts    = vance_hub_reset_texts();
	$sections = array();
	foreach ( $map as $section => $defaults ) {
		$sections[ $section ] = array(
			'title'    => isset( $titles[ $section ] ) ? (string) $titles[ $section ] : (string) $section,
			// An object, also when the PHP array would print as a list.
			'defaults' => (object) $defaults,
		);
	}
	return array(
		'sections' => (object) $sections,
		'confirm'  => $texts['confirm'],
		'done'     => $texts['done'],
		'unavailable' => $texts['unavailable'],
	);
}

/* =========================================================================
 * The control
 * ====================================================================== */

/**
 * The button control. Defined only when the Customizer classes exist, as for the
 * sortable control (inc/customizer-sortable-control.php).
 */
if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'Vance_Customize_Hub_Reset_Control' ) ) {

	class Vance_Customize_Hub_Reset_Control extends WP_Customize_Control {

		public $type = 'vance_hub_reset';

		public function render_content() {
			$texts = vance_hub_reset_texts();
			?>
			<span class="description customize-control-description"><?php echo esc_html( $texts['description'] ); ?></span>
			<button type="button" class="button vance-hub-reset" data-section="<?php echo esc_attr( $this->section ); ?>"><?php echo esc_html( $texts['button'] ); ?></button>
			<?php
		}
	}
}

/**
 * Add the reset button to each reset section, as its last control.
 *
 * Priority 50 on customize_register: after every VanceHubClub registration
 * (they run at 10, 11 and 30), and before inc/customizer-structure.php (999).
 *
 * The control has no setting ('settings' => array()). Without the Customizer
 * classes (a test) a plain control is added instead, with the type 'hidden', so
 * a suite can count it.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_reset_customize_register( $wp_customize ) {
	foreach ( vance_hub_reset_sections() as $section ) {
		$id   = vance_hub_reset_control_id( $section );
		$args = array(
			'section'  => $section,
			'priority' => 9999,
			'settings' => array(),
		);
		if ( class_exists( 'Vance_Customize_Hub_Reset_Control' ) ) {
			$wp_customize->add_control( new Vance_Customize_Hub_Reset_Control( $wp_customize, $id, $args ) );
		} else {
			$args['type'] = 'hidden';
			$wp_customize->add_control( $id, $args );
		}
	}
}
add_action( 'customize_register', 'vance_hub_reset_customize_register', 50 );

/* =========================================================================
 * The script
 * ====================================================================== */

/**
 * Print the reset map and enqueue the script, in the Customizer only.
 *
 * Reads the live manager, so the map follows every control that is registered
 * at this time.
 */
function vance_hub_reset_enqueue() {
	global $wp_customize;
	if ( ! is_object( $wp_customize ) || ! method_exists( $wp_customize, 'controls' ) ) {
		return;
	}

	$map    = vance_hub_reset_map( vance_hub_reset_sections(), vance_hub_reset_collect( $wp_customize->controls() ) );
	$titles = array();
	foreach ( vance_hub_customizer_sections() as $id => $section ) {
		$titles[ $id ] = $section['title'];
	}
	foreach ( array_keys( $map ) as $id ) {
		$live = $wp_customize->get_section( $id );
		if ( $live && isset( $live->title ) && '' !== (string) $live->title ) {
			$titles[ $id ] = (string) $live->title;
		}
	}

	$dir = get_template_directory();
	wp_enqueue_script(
		'vance-hub-reset',
		get_template_directory_uri() . '/assets/js/hub-customizer-reset.js',
		array( 'jquery', 'customize-controls' ),
		@filemtime( $dir . '/assets/js/hub-customizer-reset.js' ) ?: '1.0.0',
		true
	);
	// JSON, not wp_localize_script(): this keeps a boolean and a number as they are.
	wp_add_inline_script(
		'vance-hub-reset',
		vance_hub_reset_inline_script( vance_hub_reset_script_data( $map, $titles ) ),
		'before'
	);
}
add_action( 'customize_controls_enqueue_scripts', 'vance_hub_reset_enqueue' );
