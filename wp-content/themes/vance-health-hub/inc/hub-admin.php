<?php
/**
 * VanceHubClub admin menu: one place for every VanceHubClub control.
 *
 * Adds "VanceHubClub" under the VanceHealthHub admin menu. The screen shows
 * the state of each switch, one link that opens the VanceHubClub section of
 * the Customizer, and preview links for the three places the layout applies
 * (home page, an article, the member dashboard).
 *
 * The screen only READS. Every setting is still saved by the Customizer, so
 * there is one store and one sanitizer for each value.
 *
 * Needs inc/hub-layout.php (vance_hub_saved_layout, vance_hub_sanitize_checkbox).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The on/off switches, in display order.
 *
 * Each default MUST equal the default in the Customizer registration
 * (hub-layout.php, hub-article.php, hub-dashboard-features.php). A switch that
 * was never saved shows its default here, as it does on the site.
 *
 * @return array[] Rows of mod name, default and label.
 */
function vance_hub_admin_switches() {
	return array(
		array( 'mod' => 'vance_hub_show_promo', 'default' => true, 'label' => __( 'Home page: promo block', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_show_sections', 'default' => true, 'label' => __( 'Home page: "Latest by section"', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_show_more', 'default' => true, 'label' => __( 'Home page: "More to read"', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_articles', 'default' => false, 'label' => __( 'Article pages: VanceHubClub layout', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_show_dashboard', 'default' => false, 'label' => __( 'Member dashboard: VanceHubClub home', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_dash_member_no', 'default' => false, 'label' => __( 'Member dashboard: member number', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_dash_chart', 'default' => false, 'label' => __( 'Member dashboard: results chart', 'vance-health-hub' ) ),
		array( 'mod' => 'vance_hub_dash_checkin', 'default' => false, 'label' => __( 'Member dashboard: weekly check-in', 'vance-health-hub' ) ),
	);
}

/**
 * The saved state of each switch.
 *
 * @return array[] Rows of label and on (bool).
 */
function vance_hub_admin_status() {
	$rows = array();
	foreach ( vance_hub_admin_switches() as $switch ) {
		$rows[] = array(
			'label' => $switch['label'],
			'on'    => (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $switch['mod'], $switch['default'] ) ),
		);
	}
	return $rows;
}

/**
 * Address of the VanceHubClub section in the Customizer.
 *
 * @return string
 */
function vance_hub_admin_customizer_url() {
	return add_query_arg( array( 'autofocus[section]' => 'vance_home_layout' ), admin_url( 'customize.php' ) );
}

/**
 * Preview links. A link is left out when its page does not exist.
 *
 * @return array[] Rows of label and url.
 */
function vance_hub_admin_preview_links() {
	$links = array(
		array(
			'label' => __( 'Home page', 'vance-health-hub' ),
			'url'   => add_query_arg( 'vhh_layout', 'hub', home_url( '/' ) ),
		),
	);

	$posts = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	if ( ! empty( $posts ) ) {
		$article = get_permalink( (int) $posts[0] );
		if ( $article ) {
			$links[] = array(
				'label' => __( 'Newest article', 'vance-health-hub' ),
				'url'   => add_query_arg( 'vhh_layout', 'hub', $article ),
			);
		}
	}

	$dashboard = get_page_by_path( 'dashboard' );
	if ( $dashboard ) {
		$address = get_permalink( $dashboard );
		if ( $address ) {
			$links[] = array(
				'label' => __( 'Member dashboard', 'vance-health-hub' ),
				'url'   => add_query_arg( 'vhh_layout', 'hub', $address ),
			);
		}
	}

	return $links;
}

/**
 * Register the menu item. Priority 1000: the parent menu is added at 999.
 */
function vance_hub_admin_menu() {
	add_submenu_page(
		'vance-content-hub',
		__( 'VanceHubClub', 'vance-health-hub' ),
		__( 'VanceHubClub', 'vance-health-hub' ),
		'manage_options',
		'vance-hub-club',
		'vance_hub_admin_render'
	);
}
add_action( 'admin_menu', 'vance_hub_admin_menu', 1000 );

/**
 * The screen.
 */
function vance_hub_admin_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$is_hub = 'hub' === vance_hub_saved_layout();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'VanceHubClub', 'vance-health-hub' ); ?></h1>
		<p>
			<?php esc_html_e( 'Site layout now:', 'vance-health-hub' ); ?>
			<strong><?php echo esc_html( $is_hub ? __( 'VanceHubClub', 'vance-health-hub' ) : __( 'VanceHealthHub (classic)', 'vance-health-hub' ) ); ?></strong>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( vance_hub_admin_customizer_url() ); ?>"><?php esc_html_e( 'Change VanceHubClub settings', 'vance-health-hub' ); ?></a>
		</p>

		<h2><?php esc_html_e( 'Switches', 'vance-health-hub' ); ?></h2>
		<table class="widefat striped" style="max-width:640px">
			<tbody>
			<?php foreach ( vance_hub_admin_status() as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['label'] ); ?></td>
					<td><strong><?php echo esc_html( $row['on'] ? __( 'On', 'vance-health-hub' ) : __( 'Off', 'vance-health-hub' ) ); ?></strong></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Preview', 'vance-health-hub' ); ?></h2>
		<p><?php esc_html_e( 'These links show the VanceHubClub layout to you only. They do not change the site.', 'vance-health-hub' ); ?></p>
		<p>
			<?php foreach ( vance_hub_admin_preview_links() as $link ) : ?>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
			<?php endforeach; ?>
		</p>
	</div>
	<?php
}
