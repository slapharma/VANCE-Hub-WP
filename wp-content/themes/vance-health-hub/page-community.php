<?php
/**
 * Template Name: VanceHubClub Community
 *
 * The /community/ page: questions and replies from members, the weekly poll,
 * and the house rules. Logic: inc/hub-community.php. Markup:
 * inc/hub-community-view.php. With the Community hub switch off, the page
 * answers 404 before this template is reached (vance_community_template_redirect).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main-content" class="site-main vcom-site-main">
	<?php echo vance_community_render_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped in inc/hub-community-view.php. ?>
</main>
<?php
get_footer();
