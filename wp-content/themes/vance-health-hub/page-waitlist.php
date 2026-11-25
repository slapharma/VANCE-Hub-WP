<?php
/**
 * B-Vance waitlist (/waitlist/).
 *
 * Separate from the tools and the condition articles, and not linked from them.
 * No health question. Product emails only, with their own consent box.
 * Handler: inc/waitlist.php. Script: assets/js/waitlist.js.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main-content" class="vmp">
	<div class="vmp__wrap">
		<h1><?php esc_html_e( 'Hear about the B-Vance launch', 'vance-health-hub' ); ?></h1>
		<p class="vmp__lead"><?php esc_html_e( 'B-Vance is not available yet. Join the list and we will email you when we have news about the launch.', 'vance-health-hub' ); ?></p>
		<?php if ( current_user_can( 'edit_pages' ) ) : ?>
			<p class="vmp__draft"><?php esc_html_e( 'Editors only: DRAFT wording for regulatory and legal review. Do not publish this page before it is cleared.', 'vance-health-hub' ); ?></p>
		<?php endif; ?>

		<section class="vmp__card">
			<form class="vmp__form" id="vance-waitlist-form" method="post" novalidate>
				<p><?php esc_html_e( 'Joining is not an order and not a commitment. We will not ask about your health.', 'vance-health-hub' ); ?></p>

				<label for="vwl-email"><?php esc_html_e( 'Email address', 'vance-health-hub' ); ?></label>
				<input id="vwl-email" name="email" type="email" required autocomplete="email" inputmode="email">

				<label for="vwl-first"><?php esc_html_e( 'First name (optional)', 'vance-health-hub' ); ?></label>
				<input id="vwl-first" name="first_name" type="text" maxlength="60" autocomplete="given-name">

				<div class="vmp__hp" aria-hidden="true">
					<label for="vwl-website"><?php esc_html_e( 'Leave this empty', 'vance-health-hub' ); ?></label>
					<input id="vwl-website" name="website" type="text" tabindex="-1" autocomplete="off">
				</div>

				<label class="vmp__check" for="vwl-consent">
					<input id="vwl-consent" name="consent" type="checkbox" value="1" required>
					<span><?php echo esc_html( vance_waitlist_consent_text() ); ?></span>
				</label>

				<p><button type="submit" class="vmp__btn"><?php esc_html_e( 'Join the list', 'vance-health-hub' ); ?></button></p>
				<p class="vmp__msg" id="vance-waitlist-msg" role="status" aria-live="polite"></p>
			</form>
			<p class="vmp__fine"><?php esc_html_e( 'We use your email only for this. Read our privacy policy to see how we look after it.', 'vance-health-hub' ); ?>
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'vance-health-hub' ); ?></a></p>
		</section>
	</div>
</main>
<?php
get_footer();
