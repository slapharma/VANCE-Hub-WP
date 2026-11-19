<?php
/**
 * VanceHubClub result page for the Gastro Health Survey.
 *
 * Off by default. With the Customizer checkbox "Health Survey: VanceHubClub
 * result page" (VanceHubClub section) unticked, nothing in this file changes
 * the survey: no markup, no stylesheet, no script. An administrator can preview
 * it without saving: add ?vhh_layout=hub to the survey address
 * (?vhh_layout=classic forces it off). The preview goes through
 * vance_hub_resolve_layout() in inc/hub-layout.php, so a visitor's query value
 * is never read.
 *
 * HOW THE SURVEY WORKS (page-healthcare-quiz.php, template "Healthcare Quiz")
 *
 *   - The questions are hard-coded in the template and must match
 *     vance_quiz_steps() in functions.php. They are answered on the same page.
 *   - At the last step the template's script hides the form and shows
 *     #results-screen. For a member it first posts the answers to the
 *     wp_ajax_vance_save_quiz_results action, which merges them into the user
 *     meta key _sla_healthcare_quiz_results. For a guest nothing is stored,
 *     and this file does not change that: no browser storage, no replay.
 *     A guest's summary is on screen only; the guest card says so.
 *   - There is no scoring and no "questions for your clinician" logic. The
 *     survey only collects answers.
 *
 * WHAT THIS FILE ADDS. A hidden block, printed inside the survey wrapper, that
 * assets/js/hub-tool-result.js reveals when #results-screen shows. The script
 * reads the form, builds the "What you told us" tiles from the answers, and
 * hides the old hero and form. It does not change any ID, field name, AJAX
 * action or nonce of the survey.
 *
 * THE DATA RULE. Every tile comes from an answer the visitor gave. There is no
 * invented question, count or member figure. A block with nothing real behind
 * it does not render: no answers means no summary card, a missing page means
 * no next-step card, and the "Saved to My Hub" card appears only after the
 * save request really succeeded.
 *
 * To back the feature out, delete the require line in functions.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Pure helpers (no WordPress calls apart from escaping and translation)
 * ====================================================================== */

/**
 * Decide whether the result page is on.
 *
 * Reuses the home layout's preview resolution: the saved checkbox stands in
 * for the saved layout, and an administrator's ?vhh_layout=hub|classic wins
 * over it. Any other query value, or a viewer who may not preview, leaves the
 * saved checkbox in charge.
 *
 * @param mixed $mod_value   Saved vance_hub_tool_result mod.
 * @param mixed $query_value Value of ?vhh_layout= ('' when absent or unread).
 * @param bool  $can_preview True when the viewer may preview layouts.
 * @return bool
 */
function vance_hub_tool_result_resolve( $mod_value, $query_value, $can_preview ) {
	$saved = vance_hub_sanitize_checkbox( $mod_value ) ? 'hub' : 'classic';
	return 'hub' === vance_hub_resolve_layout( $saved, $query_value, $can_preview );
}

/**
 * The survey questions as a small spec for the script: which field to read,
 * what to call it, and how to print an answer.
 *
 * Only steps that have a field and a label are listed, so a step the
 * dashboard cannot name never becomes a tile. Optional keys appear only when
 * they hold something:
 *   map   value => display text, where the option shows other text than it stores
 *   text  option value => name of the free-text field it reveals
 *   dep   option value => { field, text } for a nested multi-select it reveals
 *
 * @param array $steps vance_quiz_steps() shape.
 * @return array[] Rows of field, label and optional map, text, dep.
 */
function vance_hub_tool_result_fields( $steps ) {
	$out = array();
	foreach ( (array) $steps as $step ) {
		if ( ! is_array( $step ) || empty( $step['field'] ) || empty( $step['label'] ) || ! is_string( $step['field'] ) || ! is_string( $step['label'] ) ) {
			continue;
		}
		$map  = array();
		$text = array();
		$dep  = array();
		$opts = isset( $step['opts'] ) && is_array( $step['opts'] ) ? $step['opts'] : array();
		foreach ( $opts as $opt ) {
			if ( ! is_array( $opt ) || ! isset( $opt['v'] ) || ! is_string( $opt['v'] ) ) {
				continue;
			}
			$value = $opt['v'];
			if ( isset( $opt['t'] ) && is_string( $opt['t'] ) && '' !== $opt['t'] && $opt['t'] !== $value ) {
				$map[ $value ] = $opt['t'];
			}
			if ( ! empty( $opt['textInput'] ) && ! empty( $opt['txtField'] ) && is_string( $opt['txtField'] ) ) {
				$text[ $value ] = $opt['txtField'];
			}
			if ( ! empty( $opt['depField'] ) && is_string( $opt['depField'] ) ) {
				$inner = array();
				$boxes = isset( $opt['depCheckboxes'] ) && is_array( $opt['depCheckboxes'] ) ? $opt['depCheckboxes'] : array();
				foreach ( $boxes as $box ) {
					if ( is_array( $box ) && isset( $box['v'] ) && is_string( $box['v'] ) && ! empty( $box['textInput'] ) && ! empty( $box['txtField'] ) && is_string( $box['txtField'] ) ) {
						$inner[ $box['v'] ] = $box['txtField'];
					}
				}
				$dep[ $value ] = array( 'field' => $opt['depField'], 'text' => $inner );
			}
		}
		$row = array( 'field' => $step['field'], 'label' => $step['label'] );
		if ( $map ) {
			$row['map'] = $map;
		}
		if ( $text ) {
			$row['text'] = $text;
		}
		if ( $dep ) {
			$row['dep'] = $dep;
		}
		$out[] = $row;
	}
	return $out;
}

/**
 * The sign-up address, with a return path to the survey.
 *
 * @param string $login_url  The login page address.
 * @param string $survey_url The survey address to come back to.
 * @return string
 */
function vance_hub_tool_result_signup_url( $login_url, $survey_url ) {
	$login_url  = (string) $login_url;
	$survey_url = (string) $survey_url;
	if ( '' === $login_url ) {
		return '';
	}
	$url = $login_url . ( false === strpos( $login_url, '?' ) ? '?' : '&' ) . 'tab=signup';
	if ( '' !== $survey_url ) {
		$url .= '&redirect_to=' . rawurlencode( $survey_url );
	}
	return $url;
}

/**
 * The "What to do next" cards, at most three. A card whose page address is
 * empty is left out, so a page that does not exist is never linked.
 *
 * @param array $urls Keys planner, guides, ask. Each a URL or ''.
 * @return array[] Rows of key, tone, eyebrow, title, text, cta, url, ask.
 */
function vance_hub_tool_result_next_cards( $urls ) {
	$urls  = is_array( $urls ) ? $urls : array();
	$cards = array();

	if ( ! empty( $urls['planner'] ) && is_string( $urls['planner'] ) ) {
		$cards[] = array(
			'key'     => 'planner',
			'tone'    => 'purple',
			'eyebrow' => __( 'NEXT TOOL', 'vance-health-hub' ),
			'title'   => __( 'Plan a week of gut-friendly meals', 'vance-health-hub' ),
			'text'    => __( 'Build a weekly meal plan from the Hub recipes.', 'vance-health-hub' ),
			'cta'     => __( 'Open the Meal Planner', 'vance-health-hub' ),
			'url'     => $urls['planner'],
			'ask'     => false,
		);
	}
	if ( ! empty( $urls['guides'] ) && is_string( $urls['guides'] ) ) {
		$cards[] = array(
			'key'     => 'guides',
			'tone'    => 'navy',
			'eyebrow' => __( 'READ', 'vance-health-hub' ),
			'title'   => __( 'Read the gut health guides', 'vance-health-hub' ),
			'text'    => __( 'Plain guides to the gut conditions the Hub covers.', 'vance-health-hub' ),
			'cta'     => __( 'See the guides', 'vance-health-hub' ),
			'url'     => $urls['guides'],
			'ask'     => false,
		);
	}
	if ( ! empty( $urls['ask'] ) && is_string( $urls['ask'] ) ) {
		$cards[] = array(
			'key'     => 'ask',
			'tone'    => 'teal',
			'eyebrow' => __( 'ASK', 'vance-health-hub' ),
			'title'   => __( 'Ask VANCE-Ai a question', 'vance-health-hub' ),
			'text'    => __( 'Ask about gut health and get a plain answer.', 'vance-health-hub' ),
			'cta'     => __( 'Ask VANCE-Ai', 'vance-health-hub' ),
			'url'     => $urls['ask'],
			'ask'     => true,
		);
	}
	return array_slice( $cards, 0, 3 );
}

/**
 * The block's markup.
 *
 * $d keys:
 *   member        bool    viewer is logged in
 *   fields        array   vance_hub_tool_result_fields() rows
 *   signup_url    string  sign-up address with the return path ('' hides the button)
 *   google_html   string  trusted markup of the Google sign-in button, or ''
 *   dashboard_url string  My Hub address, or '' when there is no such page
 *   next          array   vance_hub_tool_result_next_cards() rows
 *   home_url      string  address for the breadcrumb root
 *   image         string  hero image address, or ''
 *   image_alt     string  hero image text
 *   ajax_url      string  admin-ajax address (members only)
 *   nonce         string  save nonce (members only)
 *
 * The block is printed hidden. The script reveals it at the result state.
 *
 * @param array $d Data.
 * @return string
 */
function vance_hub_tool_result_html( $d ) {
	$d      = is_array( $d ) ? $d : array();
	$member = ! empty( $d['member'] );
	$arrow  = ' <span aria-hidden="true">&rarr;</span>';

	$config = array(
		'fields' => isset( $d['fields'] ) && is_array( $d['fields'] ) ? array_values( $d['fields'] ) : array(),
		'member' => $member,
	);
	if ( $member ) {
		$config['ajaxUrl'] = isset( $d['ajax_url'] ) ? (string) $d['ajax_url'] : '';
		$config['nonce']   = isset( $d['nonce'] ) ? (string) $d['nonce'] : '';
	}

	$html  = '<div class="vhub-tr" id="vhub-tool-result" hidden data-config="' . esc_attr( wp_json_encode( $config ) ) . '">';

	// -- Hero ---------------------------------------------------------------
	$html .= '<section class="vhub-tr__hero" aria-labelledby="vhub-tr-title"><div class="vhub-tr__hero-in"><div class="vhub-tr__hero-copy">';
	$html .= '<p class="vhub-tr__crumb">';
	if ( ! empty( $d['home_url'] ) ) {
		$html .= '<a href="' . esc_url( $d['home_url'] ) . '">' . esc_html__( 'Tools', 'vance-health-hub' ) . '</a>';
	} else {
		$html .= esc_html__( 'Tools', 'vance-health-hub' );
	}
	$html .= ' / ' . esc_html__( 'Gastro Health Survey', 'vance-health-hub' ) . ' / ' . esc_html__( 'Your summary', 'vance-health-hub' ) . '</p>';
	$html .= '<p class="vhub-tr__pill">' . esc_html__( 'GASTRO HEALTH SURVEY', 'vance-health-hub' ) . ' &middot; ' . esc_html__( 'COMPLETE', 'vance-health-hub' ) . '</p>';
	$html .= '<h1 id="vhub-tr-title" class="vhub-tr__title">' . esc_html__( 'Your summary is', 'vance-health-hub' ) . ' <span>' . esc_html__( 'ready', 'vance-health-hub' ) . '</span></h1>';
	$html .= '<p class="vhub-tr__lead">' . esc_html__( 'You can read it now. You do not need an account. This summary is not a diagnosis.', 'vance-health-hub' ) . '</p>';
	$html .= '</div>';
	if ( ! empty( $d['image'] ) ) {
		$html .= '<img class="vhub-tr__hero-img" src="' . esc_url( $d['image'] ) . '" alt="' . esc_attr( isset( $d['image_alt'] ) ? $d['image_alt'] : '' ) . '" loading="lazy" decoding="async">';
	}
	$html .= '</div></section>';

	// -- Summary card and save card ------------------------------------------
	$html .= '<div class="vhub-tr__main">';

	// The summary is filled by the script from the answers. It stays hidden
	// when there are none, and nothing is printed for it without a field list.
	if ( ! empty( $config['fields'] ) ) {
		$html .= '<section class="vhub-tr__summary" id="vhub-tr-summary" hidden aria-labelledby="vhub-tr-summary-title">';
		$html .= '<h2 id="vhub-tr-summary-title" class="vhub-tr__h2">' . esc_html__( 'What you told us', 'vance-health-hub' ) . '</h2>';
		$html .= '<p class="vhub-tr__print-note">' . esc_html__( 'Gastro Health Survey summary. This summary is not a diagnosis.', 'vance-health-hub' ) . '</p>';
		$html .= '<div class="vhub-tr__tiles" id="vhub-tr-tiles"></div>';
		$html .= '<div class="vhub-tr__actions"><button type="button" class="vhub-tr__btn vhub-tr__btn--teal" id="vhub-tr-print">' . esc_html__( 'Print the summary', 'vance-health-hub' ) . '</button></div>';
		$html .= '</section>';
	}

	if ( $member ) {
		// Hidden until the save request has really succeeded.
		if ( ! empty( $d['dashboard_url'] ) ) {
			$html .= '<aside class="vhub-tr__save vhub-tr__save--done" id="vhub-tr-saved" hidden aria-labelledby="vhub-tr-saved-title">';
			$html .= '<p class="vhub-tr__pill vhub-tr__pill--light">' . esc_html__( 'MY HUB', 'vance-health-hub' ) . '</p>';
			$html .= '<h2 id="vhub-tr-saved-title" class="vhub-tr__h2 vhub-tr__h2--light">' . esc_html__( 'Saved to My Hub', 'vance-health-hub' ) . '</h2>';
			$html .= '<p class="vhub-tr__save-text">' . esc_html__( 'Your answers are saved to your health profile.', 'vance-health-hub' ) . '</p>';
			$html .= '<a class="vhub-tr__btn vhub-tr__btn--purple" href="' . esc_url( $d['dashboard_url'] ) . '">' . esc_html__( 'Open My Hub', 'vance-health-hub' ) . $arrow . '</a>';
			$html .= '</aside>';
		}
	} elseif ( ! empty( $d['signup_url'] ) ) {
		$html .= '<aside class="vhub-tr__save" aria-labelledby="vhub-tr-save-title">';
		// Honest wording: a guest's answers are not stored anywhere, so this
		// summary cannot be saved after the fact. Print it, or join first.
		$html .= '<p class="vhub-tr__pill vhub-tr__pill--light">' . esc_html__( 'SEE HOW YOU CHANGE', 'vance-health-hub' ) . '</p>';
		$html .= '<h2 id="vhub-tr-save-title" class="vhub-tr__h2 vhub-tr__h2--light">' . esc_html__( 'Join free and save your next survey', 'vance-health-hub' ) . '</h2>';
		$html .= '<p class="vhub-tr__save-text">' . esc_html__( 'We do not keep this summary. Print it if you want a copy. With a free account, your next survey is saved to My Hub, and you can compare your answers over time.', 'vance-health-hub' ) . '</p>';
		$html .= '<a class="vhub-tr__btn vhub-tr__btn--purple" href="' . esc_url( $d['signup_url'] ) . '">' . esc_html__( 'Join free', 'vance-health-hub' ) . $arrow . '</a>';
		if ( ! empty( $d['google_html'] ) ) {
			// Markup from the theme's own [google_login] shortcode, built by
			// vance_hub_tool_result_google_html(); it escapes its own values.
			$html .= '<div class="vhub-tr__google">' . $d['google_html'] . '</div>';
		}
		$html .= '</aside>';
	}
	$html .= '</div>';

	// -- What to do next -----------------------------------------------------
	$cards = isset( $d['next'] ) && is_array( $d['next'] ) ? $d['next'] : array();
	if ( $cards ) {
		$html .= '<section class="vhub-tr__next" aria-labelledby="vhub-tr-next-title">';
		$html .= '<h2 id="vhub-tr-next-title" class="vhub-tr__h2 vhub-tr__h2--big">' . esc_html__( 'What to do next', 'vance-health-hub' ) . '</h2>';
		$html .= '<div class="vhub-tr__cards">';
		foreach ( $cards as $card ) {
			$tone  = isset( $card['tone'] ) && in_array( $card['tone'], array( 'purple', 'navy', 'teal' ), true ) ? $card['tone'] : 'navy';
			$html .= '<a class="vhub-tr__card vhub-tr__card--' . esc_attr( $tone ) . '" href="' . esc_url( $card['url'] ) . '"' . ( ! empty( $card['ask'] ) ? ' data-vance-askai-open="new"' : '' ) . '>';
			$html .= '<span class="vhub-tr__card-eyebrow">' . esc_html( $card['eyebrow'] ) . '</span>';
			$html .= '<span class="vhub-tr__card-title">' . esc_html( $card['title'] ) . '</span>';
			$html .= '<span class="vhub-tr__card-text">' . esc_html( $card['text'] ) . '</span>';
			$html .= '<span class="vhub-tr__card-cta">' . esc_html( $card['cta'] ) . $arrow . '</span>';
			$html .= '</a>';
		}
		$html .= '</div></section>';
	}

	$html .= '</div>';
	return $html;
}

/* =========================================================================
 * WordPress glue
 * ====================================================================== */

/**
 * True when the result page is on for this request.
 *
 * Only users who may preview have their query value read at all, and the value
 * must be a string: ?vhh_layout[]= arrives as an array.
 *
 * @return bool
 */
function vance_hub_tool_result_active() {
	$query    = '';
	$can_read = current_user_can( 'edit_theme_options' );
	if ( $can_read && isset( $_GET['vhh_layout'] ) && is_string( $_GET['vhh_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview switch, capability-gated.
		$query = sanitize_key( wp_unslash( $_GET['vhh_layout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return vance_hub_tool_result_resolve( vance_get_theme_mod( 'vance_hub_tool_result', false ), $query, '' !== $query && $can_read );
}

/**
 * True on the survey page with the result page on.
 *
 * @return bool
 */
function vance_hub_tool_result_on_survey() {
	return is_page_template( 'page-healthcare-quiz.php' ) && vance_hub_tool_result_active();
}

/**
 * Address of a page by slug, or '' when no such page is published.
 *
 * @param string $slug Page slug.
 * @return string
 */
function vance_hub_tool_result_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		return '';
	}
	$url = get_permalink( $page );
	return is_string( $url ) ? $url : '';
}

/**
 * The Google sign-in button, only when the theme's Google sign-in is set up
 * (GOOGLE_CLIENT_ID in wp-config.php and the [google_login] shortcode).
 *
 * The shortcode reads redirect_to from the query string to decide where a
 * successful sign-in goes. It is set to the survey for this one call, so the
 * visitor comes back to the survey, then put back as it was.
 *
 * @param string $survey_url Address to return to.
 * @return string Trusted markup, or ''.
 */
function vance_hub_tool_result_google_html( $survey_url ) {
	if ( ! defined( 'GOOGLE_CLIENT_ID' ) || '' === (string) GOOGLE_CLIENT_ID || ! shortcode_exists( 'google_login' ) ) {
		return '';
	}
	$had  = array_key_exists( 'redirect_to', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$prev = $had ? $_GET['redirect_to'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$_GET['redirect_to'] = $survey_url; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$out = do_shortcode( '[google_login]' );
	if ( $had ) {
		$_GET['redirect_to'] = $prev; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		unset( $_GET['redirect_to'] );
	}
	return is_string( $out ) ? $out : '';
}

/**
 * Gather the real data for the block.
 *
 * @return array vance_hub_tool_result_html() input.
 */
function vance_hub_tool_result_data() {
	$member     = is_user_logged_in();
	$survey_url = vance_hub_tool_result_page_url( 'gastro-health-survey' );
	if ( '' === $survey_url ) {
		$survey_url = home_url( '/gastro-health-survey/' );
	}

	$guides = vance_hub_tool_result_page_url( 'gastro-health-explained' );
	if ( '' === $guides ) {
		$guides = vance_hub_tool_result_page_url( 'gi-health' );
	}

	$data = array(
		'member'        => $member,
		'fields'        => function_exists( 'vance_quiz_steps' ) ? vance_hub_tool_result_fields( vance_quiz_steps() ) : array(),
		'dashboard_url' => vance_hub_tool_result_page_url( 'dashboard' ),
		'next'          => vance_hub_tool_result_next_cards( array(
			'planner' => vance_hub_tool_result_page_url( 'gastro-meal-planner' ),
			'guides'  => $guides,
			'ask'     => vance_hub_tool_result_page_url( 'ask-ai' ),
		) ),
		'home_url'      => home_url( '/' ),
		'image'         => vance_hub_img( 'gi-health/ulcerative-colitis.webp' ),
		'image_alt'     => __( 'Two women walking and talking together on a sunlit city street', 'vance-health-hub' ),
	);

	if ( $member ) {
		$data['ajax_url'] = admin_url( 'admin-ajax.php' );
		$data['nonce']    = wp_create_nonce( 'vance_quiz_nonce' );
	} else {
		$data['signup_url']  = vance_hub_tool_result_signup_url( home_url( '/login/' ), $survey_url );
		$data['google_html'] = vance_hub_tool_result_google_html( $survey_url );
	}
	return $data;
}

/**
 * Print the block (called from page-healthcare-quiz.php). Returns '' when the
 * result page is off.
 *
 * @return string
 */
function vance_hub_tool_result_render() {
	if ( ! vance_hub_tool_result_active() ) {
		return '';
	}
	return vance_hub_tool_result_html( vance_hub_tool_result_data() );
}

/**
 * Stylesheet and script, only on the survey page with the result page on.
 */
function vance_hub_tool_result_enqueue() {
	if ( ! vance_hub_tool_result_on_survey() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style(
		'vance-hub-tool-result',
		$uri . '/assets/css/hub-tool-result.css',
		array( 'vance-main-style' ),
		@filemtime( $dir . '/assets/css/hub-tool-result.css' ) ?: '1.0.0'
	);
	wp_enqueue_script(
		'vance-hub-tool-result',
		$uri . '/assets/js/hub-tool-result.js',
		array( 'jquery' ),
		@filemtime( $dir . '/assets/js/hub-tool-result.js' ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_tool_result_enqueue', 20 );

/**
 * The Customizer switch, in the VanceHubClub section (registered by
 * inc/hub-layout.php, which this file loads after).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_tool_result_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'vance_hub_tool_result', array(
		'default'           => false,
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_tool_result', array(
		'label'       => __( 'Health Survey: VanceHubClub result page', 'vance-health-hub' ),
		'description' => __( 'Off: the survey ends as it does now. On: the end of the Gastro Health Survey shows a summary of the answers, a print button and next steps. Administrators can preview it without saving: add ?vhh_layout=hub to the survey address.', 'vance-health-hub' ),
		'section'     => 'vance_home_layout',
		'type'        => 'checkbox',
		'priority'    => 9,
	) );
}
add_action( 'customize_register', 'vance_hub_tool_result_customize_register', 11 );
