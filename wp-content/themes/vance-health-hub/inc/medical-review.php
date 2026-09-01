<?php
/**
 * Medical review: a named clinician against an article, in the markup and on
 * the page.
 *
 * An audit on 2026-09-01 found no `reviewedBy` on any of the 156 articles and
 * the phrase "reviewed by" present on two pages of the whole site, while the
 * About page carries a "Clinician Approved" badge. Google's guidance for
 * your-money-or-your-life health content, and every health publisher that
 * ranks, treats a named reviewer with a real qualification as the baseline.
 *
 * This ships the mechanism deliberately empty. No post has a reviewer set, so
 * nothing renders and nothing is added to the schema — the site makes no claim
 * it cannot support. That is the point: a reviewer line is worth having only
 * when a real clinician has actually read the article, and inventing one on
 * medical content would be worse than the gap it fills.
 *
 * To switch it on for an article: Posts → edit → the "Medical review" box in
 * the sidebar. Fill in a name and the line appears above the article and the
 * reviewedBy node joins the schema. Clear the name and both disappear.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VANCE_REVIEW_NAME  = '_vance_reviewer_name';
const VANCE_REVIEW_CREDS = '_vance_reviewer_credentials';
const VANCE_REVIEW_URL   = '_vance_reviewer_url';
const VANCE_REVIEW_DATE  = '_vance_reviewed_date';

/**
 * The reviewer recorded against a post, or null when there isn't one.
 *
 * The name is the switch. Credentials, URL and date are optional decoration on
 * top of it — a review by somebody unnamed is not a review.
 *
 * @param int $post_id Post ID.
 * @return array{name:string,credentials:string,url:string,date:string}|null
 */
function vance_article_reviewer( $post_id ) {
	$name = trim( (string) get_post_meta( $post_id, VANCE_REVIEW_NAME, true ) );

	if ( '' === $name ) {
		return null;
	}

	return array(
		'name'        => $name,
		'credentials' => trim( (string) get_post_meta( $post_id, VANCE_REVIEW_CREDS, true ) ),
		'url'         => trim( (string) get_post_meta( $post_id, VANCE_REVIEW_URL, true ) ),
		'date'        => trim( (string) get_post_meta( $post_id, VANCE_REVIEW_DATE, true ) ),
	);
}

/**
 * Print the "Medically reviewed by" line for the current post.
 *
 * Prints nothing when no reviewer is set, which is every post today.
 *
 * @return void
 */
function vance_render_medical_review_line() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$reviewer = vance_article_reviewer( get_the_ID() );
	if ( null === $reviewer ) {
		return;
	}

	$who = $reviewer['name'];
	if ( '' !== $reviewer['credentials'] ) {
		$who .= ', ' . $reviewer['credentials'];
	}

	if ( '' !== $reviewer['url'] ) {
		$who = sprintf(
			'<a href="%s" rel="noopener">%s</a>',
			esc_url( $reviewer['url'] ),
			esc_html( $who )
		);
	} else {
		$who = esc_html( $who );
	}

	$when = '';
	$time = $reviewer['date'] ? strtotime( $reviewer['date'] ) : false;
	if ( $time ) {
		$when = sprintf(
			' <span class="va-review-date">on <time datetime="%s">%s</time></span>',
			esc_attr( gmdate( 'Y-m-d', $time ) ),
			esc_html( date_i18n( get_option( 'date_format' ), $time ) )
		);
	}

	printf(
		'<p class="va-medical-review"><span class="va-review-label">Medically reviewed by</span> %s%s</p>',
		$who, // Escaped above, and may legitimately carry an anchor.
		$when // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Add reviewedBy to the article node in AIOSEO's schema graph.
 *
 * Mirrors inc/medical-schema.php: edit AIOSEO's own graph rather than printing
 * a second JSON-LD block, so there is one description of the page and the two
 * cannot drift apart.
 *
 * aioseo_schema_output passes the whole @graph — a list of nodes — not one node
 * at a time. The first version of this function took the argument for a single
 * node, which made it a silent no-op on the live site while a unit test that
 * handed it one node passed happily. Iterate the list.
 *
 * @param array $graph The assembled @graph array.
 * @return array
 */
function vance_medical_review_schema( $graph ) {
	if ( ! is_array( $graph ) || ! is_singular( 'post' ) ) {
		return $graph;
	}

	$reviewer = vance_article_reviewer( get_the_ID() );

	if ( null === $reviewer ) {
		return $graph;
	}

	$person = array(
		'@type' => 'Person',
		'name'  => $reviewer['name'],
	);

	if ( '' !== $reviewer['credentials'] ) {
		$person['honorificSuffix'] = $reviewer['credentials'];
	}
	if ( '' !== $reviewer['url'] ) {
		$person['url'] = $reviewer['url'];
	}

	$time      = $reviewer['date'] ? strtotime( $reviewer['date'] ) : false;
	$reviewed  = $time ? gmdate( 'Y-m-d', $time ) : '';

	foreach ( $graph as $i => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
			continue;
		}

		if ( ! array_intersect( (array) $node['@type'], array( 'Article', 'NewsArticle', 'BlogPosting', 'MedicalWebPage' ) ) ) {
			continue;
		}

		$graph[ $i ]['reviewedBy'] = $person;

		if ( $reviewed ) {
			$graph[ $i ]['lastReviewed'] = $reviewed;
		}
	}

	return $graph;
}
add_filter( 'aioseo_schema_output', 'vance_medical_review_schema' );

/**
 * Register the editor box.
 *
 * @return void
 */
function vance_medical_review_meta_box() {
	add_meta_box(
		'vance-medical-review',
		__( 'Medical review', 'vance-health-hub' ),
		'vance_medical_review_meta_box_html',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'vance_medical_review_meta_box' );

/**
 * The editor box.
 *
 * @param WP_Post $post Post being edited.
 * @return void
 */
function vance_medical_review_meta_box_html( $post ) {
	wp_nonce_field( 'vance_medical_review_save', 'vance_medical_review_nonce' );

	$fields = array(
		VANCE_REVIEW_NAME  => array( 'Reviewer name', 'text', 'e.g. Dr Jane Okonkwo' ),
		VANCE_REVIEW_CREDS => array( 'Credentials', 'text', 'e.g. MBBS, MRCP, Consultant Gastroenterologist' ),
		VANCE_REVIEW_URL   => array( 'Profile URL (optional)', 'url', 'https://' ),
		VANCE_REVIEW_DATE  => array( 'Date reviewed', 'date', '' ),
	);

	echo '<p style="margin-top:0;color:#555;">Leave the name blank for no review. Nothing is shown or claimed in the markup unless a name is filled in.</p>';

	foreach ( $fields as $key => $spec ) {
		list( $label, $type, $placeholder ) = $spec;
		printf(
			'<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:2px;">%2$s</label>
			 <input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s" style="width:100%%;" /></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( (string) get_post_meta( $post->ID, $key, true ) ),
			esc_attr( $placeholder )
		);
	}
}

/**
 * Save the box.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function vance_medical_review_save( $post_id ) {
	if ( ! isset( $_POST['vance_medical_review_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vance_medical_review_nonce'] ) ), 'vance_medical_review_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$map = array(
		VANCE_REVIEW_NAME  => 'sanitize_text_field',
		VANCE_REVIEW_CREDS => 'sanitize_text_field',
		VANCE_REVIEW_URL   => 'esc_url_raw',
		VANCE_REVIEW_DATE  => 'sanitize_text_field',
	);

	foreach ( $map as $key => $sanitiser ) {
		$value = isset( $_POST[ $key ] ) ? call_user_func( $sanitiser, wp_unslash( $_POST[ $key ] ) ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			continue;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_post', 'vance_medical_review_save' );
