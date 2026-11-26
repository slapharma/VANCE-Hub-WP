<?php
/**
 * VanceHubClub admin menu: one place for every VanceHubClub control.
 *
 * Adds "Vance Club Layout" under the VanceHealthHub admin menu. The screen shows
 * the state of each switch, one link that opens the VanceHubClub section of
 * the Customizer, and preview links for the three places the layout applies
 * (home page, an article, the member dashboard).
 *
 * The switches section only READS. Every setting is still saved by the
 * Customizer, so there is one store and one sanitizer for each value. The
 * preview mode and share link boxes at the end come from inc/hub-preview.php,
 * which owns their (nonce-protected) actions.
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
 * Each default comes from vance_hub_switch_default() (inc/hub-layout.php), the
 * same function the Customizer registrations and the gates read. A switch that
 * was never saved shows its default here, as it does on the site.
 *
 * @return array[] Rows of mod name, default and label.
 */
function vance_hub_admin_switches() {
	$rows = array(
		'vance_hub_show_promo'     => __( 'Home page: promo block', 'vance-health-hub' ),
		'vance_hub_show_sections'  => __( 'Home page: "Latest by section"', 'vance-health-hub' ),
		'vance_hub_show_more'      => __( 'Home page: "More to read"', 'vance-health-hub' ),
		'vance_hub_articles'       => __( 'Article pages: VanceHubClub layout', 'vance-health-hub' ),
		'vance_hub_show_dashboard' => __( 'Member dashboard: VanceHubClub home', 'vance-health-hub' ),
		'vance_hub_dash_member_no' => __( 'Member dashboard: member number', 'vance-health-hub' ),
		'vance_hub_dash_chart'     => __( 'Member dashboard: results chart', 'vance-health-hub' ),
		'vance_hub_dash_checkin'   => __( 'Member dashboard: daily check-in', 'vance-health-hub' ),
		'vance_hub_dash_flare_plan' => __( 'Member dashboard: flare plan', 'vance-health-hub' ),
		'vance_hub_dashboard_tabs' => __( 'Member dashboard: VanceHubClub look on every tab', 'vance-health-hub' ),
		'vance_hub_tool_result'    => __( 'Self-Assessment: VanceHubClub result page', 'vance-health-hub' ),
		'vance_hub_header'         => __( 'Site header: VanceHubClub header', 'vance-health-hub' ),
		'vance_hub_community'      => __( 'Community hub', 'vance-health-hub' ),
	);
	$out = array();
	foreach ( $rows as $mod => $label ) {
		$out[] = array( 'mod' => $mod, 'default' => vance_hub_switch_default( $mod ), 'label' => $label );
	}
	return $out;
}

/**
 * The state of each switch.
 *
 * 'on' is the part's own saved switch. 'live' is what the site does: the part
 * is live only when the Home layout master switch is on as well.
 *
 * @return array[] Rows of label, on (bool) and live (bool).
 */
function vance_hub_admin_status() {
	$master = vance_hub_master_on();
	$rows   = array();
	foreach ( vance_hub_admin_switches() as $switch ) {
		$on     = (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $switch['mod'], $switch['default'] ) );
		$rows[] = array(
			'label' => $switch['label'],
			'on'    => $on,
			'live'  => $master && $on,
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
		__( 'Vance Club Layout', 'vance-health-hub' ),
		__( 'Vance Club Layout', 'vance-health-hub' ),
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
		<h1><?php esc_html_e( 'Vance Club Layout', 'vance-health-hub' ); ?></h1>
		<p>
			<?php esc_html_e( 'Master switch (Home layout):', 'vance-health-hub' ); ?>
			<strong><?php echo esc_html( $is_hub ? __( 'VanceHubClub: on', 'vance-health-hub' ) : __( 'VanceHealthHub (classic): every VanceHubClub part is off', 'vance-health-hub' ) ); ?></strong>
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
					<td><strong><?php
						if ( $row['live'] ) {
							esc_html_e( 'On', 'vance-health-hub' );
						} elseif ( $row['on'] ) {
							esc_html_e( 'Off (master switch is off)', 'vance-health-hub' );
						} else {
							esc_html_e( 'Off', 'vance-health-hub' );
						}
					?></strong></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Preview', 'vance-health-hub' ); ?></h2>
		<p><?php esc_html_e( 'These links show the VanceHubClub layout on one page, to you only. They do not change the site. To see the layout on every page, use preview mode below. To show it to a colleague without a login, use a share link.', 'vance-health-hub' ); ?></p>
		<p>
			<?php foreach ( vance_hub_admin_preview_links() as $link ) : ?>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
			<?php endforeach; ?>
		</p>
		<?php
		// Preview mode and the share link (inc/hub-preview.php). Shown only to
		// users with edit_theme_options.
		if ( function_exists( 'vance_hub_preview_admin_box' ) ) {
			vance_hub_preview_admin_box();
		}
		?>
	</div>
	<?php
}
