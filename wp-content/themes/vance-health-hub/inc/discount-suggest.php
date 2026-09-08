<?php
/**
 * IBD Discounts & Freebies — "Suggest a Discount".
 *
 * A promo card that sits in the directory grid (pinned to the top of the
 * right-hand column) and at the top of every single scheme page's sidebar,
 * plus the modal form it opens and the AJAX handler that emails the
 * suggestion in.
 *
 * Nothing is stored: a suggestion is an email to the site's editors, not a
 * post. That is deliberate — a draft `vance_discount` created from public
 * input would land in the same list as the checked, tier-rated schemes the
 * whole section's credibility rests on, and inc/discount-check.php would
 * start reporting on rows nobody vetted.
 *
 * Anti-spam is three cheap layers rather than a captcha, because the form
 * carries no account and no money:
 *   1. a nonce (see the cache note on vance_discount_suggest_modal_markup()),
 *   2. a honeypot field a human never sees and a bot fills in,
 *   3. a 60-second per-IP throttle held in a transient.
 * A captcha would mean loading Google's script on the directory page for a
 * form most visitors never open.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where suggestions are emailed.
 *
 * Customizer → Page - IBD Discounts → Suggest a Discount, falling back to the
 * site admin email so the feature works before anyone sets it.
 *
 * @return string
 */
function vance_discount_suggest_recipient() {
	$to = trim( (string) vance_get_theme_mod( 'vance_discount_suggest_email', '' ) );

	return is_email( $to ) ? $to : get_option( 'admin_email' );
}

/**
 * The promo card.
 *
 * Same white-card-on-grey family as .vance-discount-card (4px accent top
 * edge, surface radius) but on the palette's teal tint, so it reads as an
 * invitation rather than as a scheme someone could apply for — several of
 * these sit in a grid of cards that all look applyable, and a white one here
 * was mistaken for a scheme in the first pass.
 *
 * @param string $context 'grid' (directory) or 'sidebar' (single scheme page).
 * @return string Escaped HTML.
 */
function vance_discount_suggest_card( $context = 'grid' ) {
	$context = ( 'sidebar' === $context ) ? 'sidebar' : 'grid';

	ob_start();
	?>
	<div class="vance-discount-suggest vance-discount-suggest--<?php echo esc_attr( $context ); ?>">
		<span class="vance-discount-suggest__eyebrow"><?php esc_html_e( 'Know one we\'ve missed?', 'vance-health-hub' ); ?></span>
		<h3 class="vance-discount-suggest__title"><?php esc_html_e( 'Suggest a discount', 'vance-health-hub' ); ?></h3>
		<p class="vance-discount-suggest__body"><?php esc_html_e( 'Tell us about a scheme, discount or freebie that helps with life with IBD. We check every suggestion against the provider\'s own page before it goes up.', 'vance-health-hub' ); ?></p>
		<button type="button" class="vance-discount-apply-btn vance-discount-suggest__btn" data-vance-discount-suggest>
			<?php esc_html_e( 'Suggest a discount', 'vance-health-hub' ); ?>
		</button>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Modal markup, printed once in the footer on every page that enqueues the
 * discounts bundle (functions.php) — the same gate the VAT modal uses.
 *
 * The nonce is rendered into the markup rather than localized, but both
 * ride the same page cache: on a LiteSpeed-cached page served more than a
 * nonce lifetime after it was built, verification fails. The handler answers
 * that specific case with "please refresh the page and try again" instead of
 * a generic failure, which is the only recovery a visitor can act on.
 *
 * @return void
 */
function vance_discount_suggest_modal_markup() {
	?>
	<div id="vance-suggest-modal" class="vance-vat-modal vance-suggest-modal" hidden>
		<div class="vance-vat-modal__panel" role="dialog" aria-modal="true" aria-labelledby="vance-suggest-modal-title">
			<button type="button" class="vance-vat-modal__close" id="vance-suggest-modal-close" aria-label="<?php esc_attr_e( 'Close', 'vance-health-hub' ); ?>">&times;</button>
			<h2 id="vance-suggest-modal-title"><?php esc_html_e( 'Suggest a discount', 'vance-health-hub' ); ?></h2>
			<p class="vance-vat-modal__intro"><?php esc_html_e( 'Only the name is required. A link helps us check it faster, and anything you can tell us about who qualifies is genuinely useful.', 'vance-health-hub' ); ?></p>

			<form id="vance-suggest-form" novalidate>
				<label class="vance-vat-modal__field" for="vance-suggest-name">
					<?php esc_html_e( 'Name of the discount or scheme', 'vance-health-hub' ); ?> <span aria-hidden="true">*</span>
					<input type="text" id="vance-suggest-name" name="discount_name" maxlength="200" required autocomplete="off">
				</label>
				<label class="vance-vat-modal__field" for="vance-suggest-url">
					<?php esc_html_e( 'Link to it (optional)', 'vance-health-hub' ); ?>
					<input type="url" id="vance-suggest-url" name="discount_url" maxlength="500" placeholder="https://" autocomplete="off" inputmode="url">
				</label>
				<label class="vance-vat-modal__field" for="vance-suggest-info">
					<?php esc_html_e( 'Anything else we should know (optional)', 'vance-health-hub' ); ?>
					<textarea id="vance-suggest-info" name="discount_info" rows="4" maxlength="2000" placeholder="<?php esc_attr_e( 'Who qualifies, what you get, which part of the UK it covers…', 'vance-health-hub' ); ?>"></textarea>
				</label>

				<?php
				// Honeypot. Off-screen rather than display:none — some bots skip
				// hidden fields but fill positioned ones. aria-hidden + tabindex
				// keep it away from screen readers and the tab order.
				?>
				<div class="vance-suggest-hp" aria-hidden="true">
					<label for="vance-suggest-website"><?php esc_html_e( 'Leave this field empty', 'vance-health-hub' ); ?></label>
					<input type="text" id="vance-suggest-website" name="website" tabindex="-1" autocomplete="off">
				</div>

				<p class="vance-suggest-status" id="vance-suggest-status" role="status" aria-live="polite"></p>

				<div class="vance-vat-modal__actions">
					<button type="submit" class="vance-discount-apply-btn" id="vance-suggest-send"><?php esc_html_e( 'Send suggestion', 'vance-health-hub' ); ?></button>
					<a href="#" id="vance-suggest-cancel"><?php esc_html_e( 'Cancel', 'vance-health-hub' ); ?></a>
				</div>

				<input type="hidden" id="vance-suggest-nonce" value="<?php echo esc_attr( wp_create_nonce( 'vance_suggest_discount' ) ); ?>">
			</form>
		</div>
	</div>
	<?php
}

/**
 * AJAX: email one suggestion.
 *
 * Registered for logged-out visitors too — most people who spot a missing
 * scheme are not members.
 *
 * @return void
 */
function vance_ajax_suggest_discount() {
	if ( ! check_ajax_referer( 'vance_suggest_discount', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'This form has expired. Please refresh the page and try again.', 'vance-health-hub' ) ) );
	}

	// Honeypot: a filled field means a bot. Answer with success so it has
	// nothing to tune against; nothing is sent.
	//
	// $_POST, NOT filter_input( INPUT_POST, ... ). filter_input reads PHP's
	// own copy of the original request variables, which on this host comes
	// back null for every key — so the guard was a condition that could never
	// be true, and a submission with the honeypot filled sailed through and
	// sent mail. Caught live on 2026-09-08 by posting one with the field
	// filled and finding the throttle transient set behind it, which only a
	// successful send writes.
	$vance_dsug_hp = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
	if ( '' !== $vance_dsug_hp ) {
		wp_send_json_success( array( 'message' => __( 'Thanks — that\'s with our editors.', 'vance-health-hub' ) ) );
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'vance_dsug_' . md5( $ip );
	if ( get_transient( $key ) ) {
		wp_send_json_error( array( 'message' => __( 'You\'ve just sent one — give it a minute before sending another.', 'vance-health-hub' ) ) );
	}

	$name = isset( $_POST['discount_name'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_name'] ) ) : '';
	$url  = isset( $_POST['discount_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['discount_url'] ) ) ) : '';
	$info = isset( $_POST['discount_info'] ) ? sanitize_textarea_field( wp_unslash( $_POST['discount_info'] ) ) : '';
	$from_page = isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : '';

	// Match the maxlength attributes on the inputs — those are a nicety in the
	// browser, not a constraint on what reaches this handler.
	$name = mb_substr( $name, 0, 200 );
	$url  = mb_substr( $url, 0, 500 );
	$info = mb_substr( $info, 0, 2000 );

	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'Please give the discount a name.', 'vance-health-hub' ) ) );
	}

	$site_name = get_bloginfo( 'name' );
	// Verified Resend sending domain, the same one the contact form uses
	// (functions.php, vance_contact_process_submission) — a From on any other
	// domain fails SPF/DKIM and lands in spam.
	$from_addr = 'team@vancemedicalfoods.co.uk';

	$user       = wp_get_current_user();
	$submitter  = ( $user && $user->exists() ) ? sprintf( '%s <%s>', $user->display_name, $user->user_email ) : __( 'Not signed in', 'vance-health-hub' );

	$lines = array(
		__( 'A visitor suggested a discount for the IBD Discounts & Freebies directory.', 'vance-health-hub' ),
		'',
		sprintf( '%s %s', __( 'Name:', 'vance-health-hub' ), $name ),
		sprintf( '%s %s', __( 'Link:', 'vance-health-hub' ), $url ? $url : __( '(none given)', 'vance-health-hub' ) ),
		'',
		__( 'Additional information:', 'vance-health-hub' ),
		$info ? $info : __( '(none given)', 'vance-health-hub' ),
		'',
		'---',
		sprintf( '%s %s', __( 'Suggested by:', 'vance-health-hub' ), $submitter ),
		sprintf( '%s %s', __( 'Sent from:', 'vance-health-hub' ), $from_page ? $from_page : home_url( '/ibd-discounts/' ) ),
	);

	$headers = array( "From: {$site_name} <{$from_addr}>" );
	if ( $user && $user->exists() && is_email( $user->user_email ) ) {
		// Only when we actually know who sent it — the form asks for no email
		// address, so there is nothing to reply to for a logged-out visitor.
		$headers[] = sprintf( 'Reply-To: %s <%s>', $user->display_name, $user->user_email );
	}

	$sent = wp_mail(
		vance_discount_suggest_recipient(),
		sprintf( /* translators: %s: name of the suggested discount. */ __( 'Discount suggestion: %s', 'vance-health-hub' ), $name ),
		implode( "\n", $lines ),
		$headers
	);

	if ( ! $sent ) {
		wp_send_json_error( array( 'message' => __( 'We couldn\'t send that just now. Please try again in a moment.', 'vance-health-hub' ) ) );
	}

	// Set only on a send that worked, so a visitor whose mail failed can
	// retry immediately rather than being throttled out of their own retry.
	set_transient( $key, 1, MINUTE_IN_SECONDS );

	wp_send_json_success( array( 'message' => __( 'Thanks — that\'s with our editors. We check every suggestion before it goes up.', 'vance-health-hub' ) ) );
}
add_action( 'wp_ajax_vance_suggest_discount', 'vance_ajax_suggest_discount' );
add_action( 'wp_ajax_nopriv_vance_suggest_discount', 'vance_ajax_suggest_discount' );
