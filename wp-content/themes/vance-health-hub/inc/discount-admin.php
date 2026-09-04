<?php
/**
 * Discount CPT admin editor — meta boxes and save handler for `vance_discount`.
 *
 * Plain HTML fields, no JS repeater, no ACF — same reasoning as
 * inc/recipe-admin.php: a WP-CLI import (inc/discount-cpt.php) writes most of
 * this data, so the editor UI exists for the ~26 seed schemes' occasional
 * hand correction and for whatever the ongoing research pass adds later, not
 * as the primary authoring path.
 *
 * Field shape mirrors docs/DISCOUNTS_TOOL_PLAN.md §4 exactly. Checkbox-set
 * fields (eligibility signals) store a plain array in one meta row, same as
 * `_vance_recipe_ingredients` stores a plain array — no serialization helper
 * needed, update_post_meta() handles it.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every checkbox key from docs/DISCOUNTS_TOOL_PLAN.md §6, in the order they
 * should render. Shared with the (not-yet-built) matcher in
 * inc/discount-data.php, so this is the one place the vocabulary is spelled
 * out — everything else should read it from here rather than repeat it.
 *
 * @return array<string,string> key => label
 */
function vance_discount_signal_labels() {
	return array(
		'pip'              => __( 'PIP', 'vance-health-hub' ),
		'dla'              => __( 'DLA', 'vance-health-hub' ),
		'adp'              => __( 'ADP (Scotland)', 'vance-health-hub' ),
		'aa'               => __( 'Attendance Allowance', 'vance-health-hub' ),
		'carers_allowance' => __( "Carer's Allowance", 'vance-health-hub' ),
		'blue_badge'       => __( 'Blue Badge', 'vance-health-hub' ),
		'bus_pass'         => __( 'Disabled bus pass', 'vance-health-hub' ),
		'ccuk_member'      => __( "Crohn's & Colitis UK member", 'vance-health-hub' ),
		'access_card'      => __( 'Access Card', 'vance-health-hub' ),
		'stoma'            => __( 'Permanent stoma', 'vance-health-hub' ),
		'uc'               => __( 'Universal Credit', 'vance-health-hub' ),
		'pension_credit'   => __( 'Pension Credit', 'vance-health-hub' ),
		'housing_benefit'  => __( 'Housing Benefit', 'vance-health-hub' ),
		'low_income'       => __( 'Low income (NHS LIS)', 'vance-health-hub' ),
		'water_meter'      => __( 'On a water meter', 'vance-health-hub' ),
		'state_pension_age' => __( 'State Pension age', 'vance-health-hub' ),
		'employed'         => __( 'Employed', 'vance-health-hub' ),
		'needs_companion'  => __( 'Needs a companion', 'vance-health-hub' ),
		'child_under_16'   => __( 'Child under 16', 'vance-health-hub' ),
		'ibd_diagnosis'    => __( 'IBD diagnosis alone', 'vance-health-hub' ),
	);
}

function vance_discount_add_meta_boxes() {
	add_meta_box( 'vance_discount_summary', __( 'Scheme Summary', 'vance-health-hub' ), 'vance_discount_render_summary_box', 'vance_discount', 'side', 'default' );
	add_meta_box( 'vance_discount_apply', __( 'Apply', 'vance-health-hub' ), 'vance_discount_render_apply_box', 'vance_discount', 'side', 'default' );
	add_meta_box( 'vance_discount_flags', __( 'Status', 'vance-health-hub' ), 'vance_discount_render_flags_box', 'vance_discount', 'side', 'low' );
	add_meta_box( 'vance_discount_details', __( 'Who Qualifies & What You Get', 'vance-health-hub' ), 'vance_discount_render_details_box', 'vance_discount', 'normal', 'high' );
	add_meta_box( 'vance_discount_evidence', __( 'Evidence Accepted', 'vance-health-hub' ), 'vance_discount_render_evidence_box', 'vance_discount', 'normal', 'default' );
	add_meta_box( 'vance_discount_signals', __( 'Eligibility Signals', 'vance-health-hub' ), 'vance_discount_render_signals_box', 'vance_discount', 'normal', 'default' );
	add_meta_box( 'vance_discount_related', __( 'Related Articles', 'vance-health-hub' ), 'vance_discount_render_related_box', 'vance_discount', 'side', 'low' );
}
add_action( 'add_meta_boxes_vance_discount', 'vance_discount_add_meta_boxes' );

/**
 * Field renderer. `text` covers text/url/number/date via $type; `textarea`
 * and `select` are separate branches. Matches inc/recipe-admin.php's
 * vance_recipe_meta_field() signature and inline-style convention.
 */
function vance_discount_meta_field( $post, $key, $label, $type = 'text', $extra = '', $options = array() ) {
	$value = get_post_meta( $post->ID, $key, true );

	if ( 'select' === $type ) {
		printf( '<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:2px;">%2$s</label><select id="%1$s" name="%1$s" style="width:100%%;">', esc_attr( $key ), esc_html( $label ) );
		printf( '<option value="">%s</option>', esc_html__( '&mdash; choose &mdash;', 'vance-health-hub' ) );
		foreach ( $options as $opt_value => $opt_label ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $opt_value ), selected( $value, $opt_value, false ), esc_html( $opt_label ) );
		}
		echo '</select></p>';
		return;
	}

	if ( 'textarea' === $type ) {
		printf(
			'<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:2px;">%2$s</label><textarea id="%1$s" name="%1$s" rows="4" style="width:100%%;">%3$s</textarea></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_textarea( $value )
		);
		return;
	}

	if ( 'checkbox' === $type ) {
		printf(
			'<p><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s> %3$s</label></p>',
			esc_attr( $key ),
			checked( $value, 1, false ),
			esc_html( $label )
		);
		return;
	}

	printf(
		'<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:2px;">%2$s</label>' .
		'<input type="%3$s" id="%1$s" name="%1$s" value="%4$s" style="width:100%%;" %5$s></p>',
		esc_attr( $key ),
		esc_html( $label ),
		esc_attr( $type ),
		esc_attr( $value ),
		$extra
	);
}

function vance_discount_render_summary_box( $post ) {
	wp_nonce_field( 'vance_discount_save_meta', 'vance_discount_meta_nonce' );
	vance_discount_meta_field( $post, 'vance_discount_provider', __( 'Provider', 'vance-health-hub' ), 'text' );
	vance_discount_meta_field( $post, 'vance_discount_value', __( 'Value summary (one line, shown on the card)', 'vance-health-hub' ), 'text' );
	vance_discount_meta_field( $post, 'vance_discount_cost', __( 'Cost', 'vance-health-hub' ), 'text' );
	vance_discount_meta_field( $post, 'vance_discount_verified_on', __( 'Verified on', 'vance-health-hub' ), 'date' );
	vance_discount_meta_field(
		$post,
		'vance_discount_confidence',
		__( 'Confidence', 'vance-health-hub' ),
		'select',
		'',
		array( 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low' )
	);
}

function vance_discount_render_apply_box( $post ) {
	vance_discount_meta_field( $post, 'vance_discount_official_url', __( 'Official info URL', 'vance-health-hub' ), 'url' );
	vance_discount_meta_field( $post, 'vance_discount_apply_url', __( 'Apply URL', 'vance-health-hub' ), 'url' );
	vance_discount_meta_field(
		$post,
		'vance_discount_apply_type',
		__( 'Apply type', 'vance-health-hub' ),
		'select',
		'',
		array(
			'online'       => 'Online',
			'phone'        => 'Phone',
			'post'         => 'Post',
			'pdf'          => 'PDF',
			'at-venue'     => 'At the venue',
			'at-booking'   => 'At booking',
			'via-supplier' => 'Via supplier',
			'via-council'  => 'Via council',
			'via-gp'       => 'Via GP',
			'in-account'   => 'In-account',
		)
	);
	vance_discount_meta_field( $post, 'vance_discount_apply_contact', __( 'Apply contact (phone/email/postal)', 'vance-health-hub' ), 'text' );
	vance_discount_meta_field(
		$post,
		'vance_discount_tier',
		__( 'Integration tier', 'vance-health-hub' ),
		'select',
		'',
		array( '1' => 'Tier 1 — on-hub', '2' => 'Tier 2 — popup hand-off', '3' => 'Tier 3 — prepare only' )
	);
	vance_discount_meta_field( $post, 'vance_discount_frameable', __( 'Frameable (probe result)', 'vance-health-hub' ), 'checkbox' );
}

function vance_discount_render_flags_box( $post ) {
	vance_discount_meta_field( $post, 'vance_discount_featured', __( 'Featured (eligible for the featured pool)', 'vance-health-hub' ), 'checkbox' );
}

function vance_discount_render_details_box( $post ) {
	vance_discount_meta_field( $post, 'vance_discount_what', __( 'What you get', 'vance-health-hub' ), 'textarea' );
	vance_discount_meta_field( $post, 'vance_discount_who', __( 'Who qualifies', 'vance-health-hub' ), 'textarea' );
	vance_discount_meta_field( $post, 'vance_discount_ibd_note', __( 'IBD note', 'vance-health-hub' ), 'textarea' );
	vance_discount_meta_field( $post, 'vance_discount_upcoming', __( 'Upcoming change (dated — rendered as a banner)', 'vance-health-hub' ), 'textarea' );
}

function vance_discount_render_evidence_box( $post ) {
	$evidence = get_post_meta( $post->ID, '_vance_discount_evidence', true );
	?>
	<p style="color:#646970;"><?php esc_html_e( 'One piece of accepted evidence per line.', 'vance-health-hub' ); ?></p>
	<textarea name="vance_discount_evidence_text" rows="6" style="width:100%;"><?php echo esc_textarea( $evidence ); ?></textarea>
	<?php
}

function vance_discount_render_signals_box( $post ) {
	$checked = get_post_meta( $post->ID, '_vance_discount_signals', true );
	$checked = is_array( $checked ) ? $checked : array();
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;">';
	foreach ( vance_discount_signal_labels() as $key => $label ) {
		printf(
			'<label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="vance_discount_signals[]" value="%1$s"%2$s> %3$s</label>',
			esc_attr( $key ),
			checked( in_array( $key, $checked, true ), true, false ),
			esc_html( $label )
		);
	}
	echo '</div>';
}

function vance_discount_render_related_box( $post ) {
	$related = get_post_meta( $post->ID, '_vance_discount_related_posts', true );
	$related = is_array( $related ) ? implode( ',', $related ) : '';
	?>
	<p style="color:#646970;"><?php esc_html_e( 'Comma-separated post IDs.', 'vance-health-hub' ); ?></p>
	<input type="text" name="vance_discount_related_posts_text" value="<?php echo esc_attr( $related ); ?>" style="width:100%;">
	<?php
}

/**
 * Save handler. Form fields submit as `vance_discount_*`; this writes them to
 * `_vance_discount_*` post meta — same translation convention as every other
 * CPT in this theme (CLAUDE.md constraint 2, though that constraint is about
 * the legacy `_sla_*` keys specifically; the pattern is reused here for the
 * same reason: form field names never carry a leading underscore).
 */
function vance_discount_save_meta( $post_id, $post ) {
	if ( 'vance_discount' !== $post->post_type ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['vance_discount_meta_nonce'] ) || ! wp_verify_nonce( $_POST['vance_discount_meta_nonce'], 'vance_discount_save_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = array(
		'vance_discount_provider'      => '_vance_discount_provider',
		'vance_discount_value'         => '_vance_discount_value',
		'vance_discount_cost'          => '_vance_discount_cost',
		'vance_discount_verified_on'   => '_vance_discount_verified_on',
		'vance_discount_confidence'    => '_vance_discount_confidence',
		'vance_discount_apply_type'    => '_vance_discount_apply_type',
		'vance_discount_apply_contact' => '_vance_discount_apply_contact',
		'vance_discount_tier'          => '_vance_discount_tier',
		'vance_discount_what'          => '_vance_discount_what',
		'vance_discount_who'           => '_vance_discount_who',
		'vance_discount_ibd_note'      => '_vance_discount_ibd_note',
		'vance_discount_upcoming'      => '_vance_discount_upcoming',
	);
	foreach ( $text_fields as $field => $meta_key ) {
		$raw = isset( $_POST[ $field ] ) ? trim( (string) wp_unslash( $_POST[ $field ] ) ) : '';
		if ( '' === $raw ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $raw ) );
		}
	}

	$url_fields = array(
		'vance_discount_official_url' => '_vance_discount_official_url',
		'vance_discount_apply_url'    => '_vance_discount_apply_url',
	);
	foreach ( $url_fields as $field => $meta_key ) {
		$raw = isset( $_POST[ $field ] ) ? trim( (string) wp_unslash( $_POST[ $field ] ) ) : '';
		if ( '' === $raw ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, esc_url_raw( $raw ) );
		}
	}

	update_post_meta( $post_id, '_vance_discount_frameable', isset( $_POST['vance_discount_frameable'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_vance_discount_featured', isset( $_POST['vance_discount_featured'] ) ? 1 : 0 );

	if ( isset( $_POST['vance_discount_evidence_text'] ) ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['vance_discount_evidence_text'] ) );
		$lines = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $lines ) ) );
		if ( $lines ) {
			update_post_meta( $post_id, '_vance_discount_evidence', implode( "\n", $lines ) );
		} else {
			delete_post_meta( $post_id, '_vance_discount_evidence' );
		}
	}

	if ( isset( $_POST['vance_discount_signals'] ) && is_array( $_POST['vance_discount_signals'] ) ) {
		$signals = array_map( 'sanitize_key', wp_unslash( $_POST['vance_discount_signals'] ) );
		update_post_meta( $post_id, '_vance_discount_signals', $signals );
	} else {
		delete_post_meta( $post_id, '_vance_discount_signals' );
	}

	if ( isset( $_POST['vance_discount_related_posts_text'] ) ) {
		$ids = array_filter( array_map( 'intval', explode( ',', (string) wp_unslash( $_POST['vance_discount_related_posts_text'] ) ) ) );
		if ( $ids ) {
			update_post_meta( $post_id, '_vance_discount_related_posts', array_values( $ids ) );
		} else {
			delete_post_meta( $post_id, '_vance_discount_related_posts' );
		}
	}
}
add_action( 'save_post', 'vance_discount_save_meta', 10, 2 );
