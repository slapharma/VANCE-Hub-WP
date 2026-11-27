<?php
/**
 * The VanceHubClub site header and the purple member bar under it.
 *
 * Included by header.php in place of the classic <header class="site-header">
 * when vance_hub_header_active() is true. Logic: inc/hub-header.php. Styles:
 * assets/css/hub-header.css. Behaviour of the phone menu: assets/js/hub-header.js.
 *
 * The VANCE-Ai button keeps data-vance-askai-open, so the site-wide chat modal
 * (assets/js/vance-askai.js) opens from it. The search block keeps the classes
 * and the data-vance-search hook that assets/js/vance-header-search.js and
 * main.css already use.
 *
 * On a phone the bar shows the logo, one primary button (Join free, or My Hub
 * for a member), the search toggle and the menu button. The menu button opens
 * the navigation as a panel; VANCE-Ai and Sign in sit in that panel.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vhh_logged_in = is_user_logged_in();
$vhh_nav       = vance_hub_header_nav_html();
$vhh_mega      = vance_hub_header_nav_is_mega( $vhh_nav );
$vhh_search    = is_search();
// Button texts, links and the search hint come from inc/hub-look-settings.php.
// With that file missing, each one keeps today's literal.
$vhh_look       = function_exists( 'vance_hub_look_text' ) && function_exists( 'vance_hub_look_link' );
$vhh_t_signin   = $vhh_look ? vance_hub_look_text( 'vance_hub_text_signin' ) : __( 'Sign in', 'vance-health-hub' );
$vhh_t_join     = $vhh_look ? vance_hub_look_text( 'vance_hub_text_join' ) : __( 'Join free', 'vance-health-hub' );
$vhh_t_myhub    = $vhh_look ? vance_hub_look_text( 'vance_hub_text_myhub' ) : __( 'My Hub', 'vance-health-hub' );
$vhh_t_search   = $vhh_look ? vance_hub_look_text( 'vance_hub_text_search' ) : __( 'Search articles, tools, recipes...', 'vance-health-hub' );
$vhh_l_signin   = $vhh_look ? vance_hub_look_link( 'signin' ) : home_url( '/login/' );
$vhh_l_join     = $vhh_look ? vance_hub_look_link( 'join' ) : home_url( '/login/?tab=signup' );
$vhh_l_myhub    = $vhh_look ? vance_hub_look_link( 'myhub' ) : home_url( '/dashboard/' );
$vhh_spark     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/></svg>';
?>
<?php /* Marks the page as scripted before the header paints, so the phone panel is
         closed from the first frame and the menu button shows only when it can
         work. Without JavaScript the nav stays visible and the button is hidden. */ ?>
<script>document.documentElement.classList.add('vhh-js');</script>
<header class="vhh-header<?php echo $vhh_mega ? ' vhh-header--mega' : ''; ?>" id="vhh-header" data-vhh-header>
	<div class="vhh-header__inner">

		<a class="vhh-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-header.png' ); ?>" width="440" height="248" alt="<?php esc_attr_e( 'Vance Medical', 'vance-health-hub' ); ?>">
		</a>

		<nav class="vhh-nav" id="vhh-nav" aria-label="<?php esc_attr_e( 'Main menu', 'vance-health-hub' ); ?>">
			<?php echo $vhh_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in vance_hub_header_nav_html() or by wp_nav_menu(). ?>
			<div class="vhh-nav__extra">
				<button type="button" class="vhh-btn vhh-btn--teal" data-vance-askai-open aria-haspopup="dialog"><?php echo $vhh_spark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG above. ?><span><?php esc_html_e( 'VANCE-Ai', 'vance-health-hub' ); ?></span></button>
				<?php if ( ! $vhh_logged_in ) : ?>
					<a class="vhh-btn vhh-btn--outline" href="<?php echo esc_url( $vhh_l_signin ); ?>"><?php echo esc_html( $vhh_t_signin ); ?></a>
				<?php endif; ?>
			</div>
		</nav>

		<div class="vhh-actions">
			<button type="button" class="vhh-btn vhh-btn--teal vhh-actions__ask" data-vance-askai-open aria-haspopup="dialog" aria-label="<?php esc_attr_e( 'VANCE-Ai', 'vance-health-hub' ); ?>"><?php echo $vhh_spark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG above. ?><span><?php esc_html_e( 'VANCE-Ai', 'vance-health-hub' ); ?></span></button>
			<?php if ( $vhh_logged_in ) : ?>
				<a class="vhh-btn vhh-btn--purple" href="<?php echo esc_url( $vhh_l_myhub ); ?>"><?php echo esc_html( $vhh_t_myhub ); ?></a>
			<?php else : ?>
				<a class="vhh-btn vhh-btn--outline vhh-actions__signin" href="<?php echo esc_url( $vhh_l_signin ); ?>"><?php echo esc_html( $vhh_t_signin ); ?></a>
				<a class="vhh-btn vhh-btn--purple" href="<?php echo esc_url( $vhh_l_join ); ?>"><?php echo esc_html( $vhh_t_join ); ?></a>
			<?php endif; ?>

			<?php /* Site search: same block and hooks as the classic header
			         (main.css .vance-header-search, assets/js/vance-header-search.js). */ ?>
			<div class="vance-header-search vhh-search<?php echo $vhh_search ? ' is-open' : ''; ?>" data-vance-search>
				<form role="search" method="get" class="vance-header-search__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input
						type="search"
						id="vance-header-search-field"
						class="vance-header-search__field"
						name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php echo esc_attr( $vhh_t_search ); ?>"
						aria-label="<?php esc_attr_e( 'Search the Hub', 'vance-health-hub' ); ?>"
						autocomplete="off">
				</form>
				<button type="button" class="vance-header-search__toggle" aria-expanded="<?php echo $vhh_search ? 'true' : 'false'; ?>" aria-controls="vance-header-search-field" aria-label="<?php esc_attr_e( 'Open search', 'vance-health-hub' ); ?>">
					<svg class="vance-header-search__icon vance-header-search__icon--open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
					<svg class="vance-header-search__icon vance-header-search__icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
				</button>
			</div>
		</div>

		<?php /* With the original menu (Max Mega Menu) the plugin's own hamburger and
		         drawer are the phone menu, so this button is not printed. */ ?>
		<?php if ( ! $vhh_mega ) : ?>
		<button type="button" class="vhh-menu-btn" aria-expanded="false" aria-controls="vhh-nav" aria-label="<?php esc_attr_e( 'Menu', 'vance-health-hub' ); ?>">
			<span class="vhh-menu-btn__bars" aria-hidden="true"><span></span><span></span><span></span></span>
		</button>
		<?php endif; ?>

	</div>
</header>
<?php
// On the VanceHubClub home page the "See what members get" link scrolls to the
// join section (or the sign-up page when the Join block is hidden); on every
// other page it opens the sign-up page. vance_hub_join_href() lives in
// inc/hub-home-settings.php; with that file missing the link keeps the anchor.
vance_hub_render_member_bar( vance_hub_is_hub_home() ? array( 'join_href' => function_exists( 'vance_hub_join_href' ) ? vance_hub_join_href() : '#vhub-join' ) : array() );
?>
