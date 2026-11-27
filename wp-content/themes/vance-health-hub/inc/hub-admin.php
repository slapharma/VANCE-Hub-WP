<?php
/**
 * VanceHubClub admin menu: one place for every VanceHubClub control.
 *
 * Adds "Vance Club Layout" under the VanceHealthHub admin menu. The screen shows
 * the state of each switch in the same groups as the VanceHubClub panel of
 * the Customizer, a link to each of those sections, and preview links for the three places the layout applies
 * (home page, an article, the member dashboard).
 *
 * The switches section only READS. Every setting is still saved by the
 * Customizer, so there is one store and one sanitizer for each value. The
 * preview mode and share link boxes at the end come from inc/hub-preview.php,
 * which owns their (nonce-protected) actions.
 *
 * Needs inc/hub-layout.php (vance_hub_saved_layout, vance_hub_sanitize_checkbox)
 * and inc/hub-home-settings.php (vance_hub_block_order, vance_hub_home_blocks)
 * and inc/hub-golive.php (vance_hub_golive_timestamp).
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The on/off switches, in display order.
 *
 * The order, the section and the label of each switch come from
 * vance_hub_customizer_switches() (inc/hub-layout.php), which the Customizer
 * registrations also read. This screen and the Customizer thus show the same
 * words in the same order.
 *
 * Each default comes from vance_hub_switch_default() (inc/hub-layout.php), the
 * same function the Customizer registrations and the gates read. A switch that
 * was never saved shows its default here, as it does on the site.
 *
 * @return array[] Rows of mod name, default, label and section id.
 */
function vance_hub_admin_switches() {
	$out = array();
	foreach ( vance_hub_customizer_switches() as $mod => $switch ) {
		$out[] = array(
			'mod'     => $mod,
			'default' => vance_hub_switch_default( $mod ),
			'label'   => $switch['label'],
			'section' => $switch['section'],
		);
	}
	return $out;
}

/**
 * The state of each switch.
 *
 * 'on' is the part's own saved switch. 'live' is what the site does: the part
 * is live only when the Site layout master switch is on as well.
 *
 * @return array[] Rows of label, section id, on (bool) and live (bool).
 */
function vance_hub_admin_status() {
	$master = vance_hub_master_on();
	$rows   = array();
	foreach ( vance_hub_admin_switches() as $switch ) {
		$on     = (bool) vance_hub_sanitize_checkbox( vance_get_theme_mod( $switch['mod'], $switch['default'] ) );
		$rows[] = array(
			'label'   => $switch['label'],
			'section' => $switch['section'],
			'on'      => $on,
			'live'    => $master && $on,
		);
	}
	return $rows;
}

/**
 * The switch states in groups, one group for each Customizer section that
 * has a switch.
 *
 * @return array[] Section id => title and rows (rows as in vance_hub_admin_status()).
 */
function vance_hub_admin_status_groups() {
	$sections = vance_hub_customizer_sections();
	$groups   = array();
	foreach ( vance_hub_admin_status() as $row ) {
		$id = $row['section'];
		if ( ! isset( $groups[ $id ] ) ) {
			$groups[ $id ] = array(
				'title' => isset( $sections[ $id ] ) ? $sections[ $id ]['title'] : '',
				'rows'  => array(),
			);
		}
		$groups[ $id ]['rows'][] = $row;
	}
	return $groups;
}

/**
 * The home page blocks that print, and the ones that do not.
 *
 * Reads vance_hub_block_order() and vance_hub_home_blocks()
 * (inc/hub-home-settings.php). This screen only reads: the order is set in the
 * Customizer.
 *
 * @return array{on: string[], off: string[]} Labels of the blocks that print (in
 *                                          print order) and of the blocks that do not.
 */
function vance_hub_admin_home_blocks() {
	$blocks = vance_hub_home_blocks();
	$on     = array();
	foreach ( vance_hub_block_order() as $id ) {
		if ( isset( $blocks[ $id ] ) ) {
			$on[] = $blocks[ $id ];
			unset( $blocks[ $id ] );
		}
	}
	return array(
		'on'  => $on,
		'off' => array_values( $blocks ),
	);
}

/**
 * The go-live line of the screen (inc/hub-golive.php). This screen only reads.
 *
 * 'planned': a valid date in the future is saved and the layout is not yet
 * VanceHubClub. 'done': the layout is VanceHubClub and a scheduled switch is
 * recorded. Any other state gives an empty text, and the screen prints nothing.
 *
 * @param int|null $now Now, as a Unix timestamp. Null means the current time.
 * @return array{state: string, text: string} State ('planned', 'done' or 'none') and the sentence.
 */
function vance_hub_admin_golive( $now = null ) {
	$now    = null === $now ? time() : (int) $now;
	$tz     = wp_timezone();
	$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$is_hub = 'hub' === vance_hub_saved_layout();

	$planned = vance_hub_golive_timestamp( vance_get_theme_mod( 'vance_hub_golive_at', '' ), $tz );
	if ( ! $is_hub && $planned > $now ) {
		return array(
			'state' => 'planned',
			/* translators: 1: date and time, 2: site time zone name. */
			'text'  => sprintf( __( 'Go-live: planned for %1$s (%2$s).', 'vance-health-hub' ), wp_date( $format, $planned, $tz ), wp_timezone_string() ),
		);
	}

	$done = (int) vance_get_theme_mod( 'vance_hub_golive_done_at', 0 );
	if ( $is_hub && $done > 0 ) {
		return array(
			'state' => 'done',
			/* translators: %s: date and time. */
			'text'  => sprintf( __( 'VanceHubClub went live by schedule on %s.', 'vance-health-hub' ), wp_date( $format, $done, $tz ) ),
		);
	}

	return array(
		'state' => 'none',
		'text'  => '',
	);
}

/**
 * The master line of the screen: the saved Site layout in words.
 *
 * @param string $saved The saved layout: 'classic', 'members' or 'hub'.
 * @return string
 */
function vance_hub_admin_master_text( $saved ) {
	if ( 'hub' === $saved ) {
		return __( 'VanceHubClub: on', 'vance-health-hub' );
	}
	if ( 'members' === $saved ) {
		return __( 'VanceHubClub: on for logged-in members only', 'vance-health-hub' );
	}
	return __( 'VanceHealthHub (classic): every VanceHubClub part is off', 'vance-health-hub' );
}

/**
 * The note under the master line. Only the 'members' state has one: the switch
 * rows show what a logged-in member sees, which is what the administrator who
 * reads this screen is.
 *
 * @param string $saved The saved layout: 'classic', 'members' or 'hub'.
 * @return string
 */
function vance_hub_admin_master_note( $saved ) {
	if ( 'members' !== $saved ) {
		return '';
	}
	return __( 'Visitors see the classic layout. The switches below show what a logged-in member sees.', 'vance-health-hub' );
}

/**
 * Address of the VanceHubClub panel in the Customizer.
 *
 * @return string
 */
function vance_hub_admin_customizer_url() {
	return add_query_arg( array( 'autofocus[panel]' => VANCE_HUB_CUSTOMIZER_PANEL ), admin_url( 'customize.php' ) );
}

/**
 * Address of one section of the VanceHubClub panel in the Customizer.
 *
 * @param string $section Section id.
 * @return string
 */
function vance_hub_admin_section_url( $section ) {
	return add_query_arg( array( 'autofocus[section]' => $section ), admin_url( 'customize.php' ) );
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
	$saved = vance_hub_saved_layout();
	$note  = vance_hub_admin_master_note( $saved );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Vance Club Layout', 'vance-health-hub' ); ?></h1>
		<p>
			<?php esc_html_e( 'Master switch (Site layout):', 'vance-health-hub' ); ?>
			<strong><?php echo esc_html( vance_hub_admin_master_text( $saved ) ); ?></strong>
			<a href="<?php echo esc_url( vance_hub_admin_section_url( 'vance_home_layout' ) ); ?>"><?php esc_html_e( 'Change', 'vance-health-hub' ); ?></a>
		</p>
		<?php if ( '' !== $note ) : ?>
		<p><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
		<?php
		$golive = vance_hub_admin_golive();
		if ( '' !== $golive['text'] ) :
			?>
		<p>
			<?php echo esc_html( $golive['text'] ); ?>
			<?php if ( 'planned' === $golive['state'] ) : ?>
				<a href="<?php echo esc_url( vance_hub_admin_section_url( 'vance_home_layout' ) ); ?>"><?php esc_html_e( 'Change', 'vance-health-hub' ); ?></a>
			<?php endif; ?>
		</p>
		<?php endif; ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( vance_hub_admin_customizer_url() ); ?>"><?php esc_html_e( 'Change VanceHubClub settings', 'vance-health-hub' ); ?></a>
		</p>

		<?php
		$home_blocks = vance_hub_admin_home_blocks();
		?>
		<p>
			<?php esc_html_e( 'Home page blocks:', 'vance-health-hub' ); ?>
			<strong><?php echo esc_html( empty( $home_blocks['on'] ) ? __( 'none', 'vance-health-hub' ) : implode( ', ', $home_blocks['on'] ) ); ?></strong>
			<?php if ( ! empty( $home_blocks['off'] ) ) : ?>
				<?php
				/* translators: %s: labels of the home page blocks that are hidden, separated by commas. */
				echo esc_html( sprintf( __( '(hidden: %s)', 'vance-health-hub' ), implode( ', ', $home_blocks['off'] ) ) );
				?>
			<?php endif; ?>
			<a href="<?php echo esc_url( vance_hub_admin_section_url( 'vance_hub_sec_home' ) ); ?>"><?php esc_html_e( 'Change', 'vance-health-hub' ); ?></a>
		</p>

		<h2><?php esc_html_e( 'Switches', 'vance-health-hub' ); ?></h2>
		<p><?php esc_html_e( 'The groups and the names are the same as in the Customizer (VanceHubClub panel).', 'vance-health-hub' ); ?></p>
		<?php foreach ( vance_hub_admin_status_groups() as $section_id => $group ) : ?>
			<h3>
				<?php echo esc_html( $group['title'] ); ?>
				<a style="font-weight:400;font-size:13px;margin-left:8px" href="<?php echo esc_url( vance_hub_admin_section_url( $section_id ) ); ?>"><?php esc_html_e( 'Change', 'vance-health-hub' ); ?></a>
			</h3>
			<table class="widefat striped" style="max-width:640px">
				<tbody>
				<?php foreach ( $group['rows'] as $row ) : ?>
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
		<?php endforeach; ?>

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
