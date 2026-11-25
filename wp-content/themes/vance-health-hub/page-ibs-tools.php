<?php
/**
 * Free IBS tools (/ibs-tools/): the one page for the social bio link.
 *
 * Each tool link keeps the visitor's utm_* tags and uses the exact slug with the
 * final slash, so no redirect drops the tags (vance_marketing_tool_url()).
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
		<h1><?php esc_html_e( 'Free tools for life with IBS', 'vance-health-hub' ); ?></h1>
		<p class="vmp__lead"><?php esc_html_e( 'Pick one. Each tool is free to use.', 'vance-health-hub' ); ?></p>

		<div class="vmp__tools">
			<?php foreach ( vance_marketing_tools() as $vmp_tool ) : ?>
				<section class="vmp__card">
					<h2><?php echo esc_html( $vmp_tool['name'] ); ?></h2>
					<p><?php echo esc_html( $vmp_tool['text'] ); ?></p>
					<a class="vmp__btn" href="<?php echo esc_url( vance_marketing_tool_url( $vmp_tool['path'] ) ); ?>"><?php echo esc_html( $vmp_tool['cta'] ); ?></a>
				</section>
			<?php endforeach; ?>
		</div>

		<p class="vmp__urgent"><?php esc_html_e( 'These tools give general information. They do not diagnose or treat anything. For urgent medical advice, call NHS 111. In an emergency, call 999.', 'vance-health-hub' ); ?></p>
	</div>
</main>
<?php
get_footer();
