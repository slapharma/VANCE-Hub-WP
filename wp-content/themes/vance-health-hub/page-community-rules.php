<?php
/**
 * Community rules (/community-rules/).
 *
 * WordPress picks this file by the page slug. While the page has no content of
 * its own, the built-in DRAFT below shows, with a notice. When a reviewer
 * writes the approved rules into the page in wp-admin, those replace the draft
 * and the notice goes. The Community page links here.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$vmp_own = '';
while ( have_posts() ) {
	the_post();
	$vmp_own = trim( (string) get_the_content() );
}
?>
<main id="main-content" class="vmp">
	<div class="vmp__wrap">
		<h1><?php esc_html_e( 'Community rules', 'vance-health-hub' ); ?></h1>

		<?php if ( '' !== $vmp_own ) : ?>
			<div class="vmp__card"><?php echo apply_filters( 'the_content', $vmp_own ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core content filter, editor-written page. ?></div>
		<?php else : ?>
			<p class="vmp__draft"><?php esc_html_e( 'DRAFT for review. These rules are not final. Parts in square brackets still need a decision from Vance.', 'vance-health-hub' ); ?></p>

			<div class="vmp__urgent">
				<p><strong><?php esc_html_e( 'If you need help now', 'vance-health-hub' ); ?></strong></p>
				<p><?php esc_html_e( 'The Community is not an emergency service. For urgent medical advice, call NHS 111. In an emergency, call 999.', 'vance-health-hub' ); ?></p>
			</div>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Privacy', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'Only signed-in members can read discussions and see member profiles. Anything you write is shared with other members and with our moderators. Share only what you are happy for them to read.', 'vance-health-hub' ); ?></p>
				<p><?php esc_html_e( 'Do not post other people\'s details, such as names, phone numbers, addresses or photos. Do not post your own contact details.', 'vance-health-hub' ); ?></p>
				<p><?php esc_html_e( 'Never copy what a member wrote to another place. We do not use a member\'s post in our marketing without that member\'s separate written permission.', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Names and nicknames', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'You can post with your first name or a nickname. A nickname is not always anonymity. People who know you may still recognise you from what you write. Choose a nickname that does not include your full name, your email address or your workplace.', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Be kind', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'Treat every member with respect. No abuse, no harassment, no hate, and no selling. Members share experience. They do not give medical advice.', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Medical information and misinformation', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'Talk about what happened to you. Do not tell another member what to take, stop or change. Do not post claims that a food, supplement or product treats, prevents or cures a condition. Do not post advice that tells someone to stop a treatment their clinician gave them.', 'vance-health-hub' ); ?></p>
				<p><?php esc_html_e( 'A moderator may remove a post that gives medical advice or makes a health claim. Please speak to your own doctor, nurse, pharmacist or dietitian about your care.', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Moderation', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'A moderator reads every post before it shows. A moderator may edit, hold or remove a post that breaks these rules, and may remove a member who breaks them again. [Decide: do we tell the member why? How long do we keep removed posts?]', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Reporting a post', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'Use the Report link on any post or reply. Say what is wrong. A moderator checks the report. [Decide: how quickly do we check a report?]', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'When a post shows a member may be at risk', 'vance-health-hub' ); ?></h2>
				<p><?php esc_html_e( 'If a post says that someone may be in danger or very unwell, a moderator does not give medical advice. The moderator replies with the NHS 111 and 999 information on this page. [Decide: who may contact a member directly, and in what cases? Needs clinical sign-off.]', 'vance-health-hub' ); ?></p>
			</section>

			<section class="vmp__card">
				<h2><?php esc_html_e( 'Complaints', 'vance-health-hub' ); ?></h2>
				<p>
					<?php esc_html_e( 'If you disagree with a moderator\'s decision, or you have another complaint, contact us.', 'vance-health-hub' ); ?>
					<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact us', 'vance-health-hub' ); ?></a>.
					<?php esc_html_e( '[Decide: who answers a complaint, and how quickly?]', 'vance-health-hub' ); ?>
				</p>
			</section>
		<?php endif; ?>

		<p><a class="vmp__btn" href="<?php echo esc_url( function_exists( 'vance_community_page_url' ) ? vance_community_page_url() : home_url( '/community/' ) ); ?>"><?php esc_html_e( 'Back to the Community', 'vance-health-hub' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
