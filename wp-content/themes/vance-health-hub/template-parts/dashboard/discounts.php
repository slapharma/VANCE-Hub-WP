<?php
/**
 * "My Discounts" dashboard tab — eligibility summary, Access Folder
 * checklist, saved schemes list. Self-contained: computes its own data from
 * the current user rather than relying on caller locals, same contract as
 * template-parts/recipe-hub-app.php (get_template_part() loads this in
 * load_template()'s own scope, so a caller's locals are invisible here
 * regardless).
 *
 * Markup/class names follow docs/DISCOUNTS_UI_SPEC.md §6. AJAX actions are
 * inc/discount-dashboard.php's vance_toggle_discount / vance_set_discount_status
 * / vance_save_access_folder, wired up in assets/js/discounts.js.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vance_dt_user_id = get_current_user_id();
$vance_dt_folder  = vance_discount_access_folder( $vance_dt_user_id );
$vance_dt_match   = vance_discount_match( $vance_dt_user_id );
$vance_dt_saved   = get_user_meta( $vance_dt_user_id, '_sla_saved_discounts', true );
$vance_dt_saved   = is_array( $vance_dt_saved ) ? $vance_dt_saved : array();
$vance_dt_statuses = get_user_meta( $vance_dt_user_id, '_sla_discount_status', true );
$vance_dt_statuses = is_array( $vance_dt_statuses ) ? $vance_dt_statuses : array();
$vance_dt_nonce    = wp_create_nonce( 'vance_dashboard_nonce' );

$vance_dt_status_labels = array(
	'interested' => __( 'Interested', 'vance-health-hub' ),
	'applied'    => __( 'Applied', 'vance-health-hub' ),
	'received'   => __( 'Received', 'vance-health-hub' ),
	'declined'   => __( 'Declined', 'vance-health-hub' ),
);
?>
<div class="vance-discount-dashboard" data-nonce="<?php echo esc_attr( $vance_dt_nonce ); ?>">

	<!-- Eligibility summary -->
	<div class="vance-discount-surface vance-discount-eligibility-summary">
		<?php if ( empty( $vance_dt_folder ) ) : ?>
			<p><?php esc_html_e( 'Fill in your Access Folder below and this line will tell you how many schemes you likely qualify for.', 'vance-health-hub' ); ?></p>
		<?php else : ?>
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %d: number of schemes */
						_n(
							'Based on your Access Folder, you likely qualify for <strong>%d</strong> of these schemes.',
							'Based on your Access Folder, you likely qualify for <strong>%d</strong> of these schemes.',
							count( $vance_dt_match['likely'] ),
							'vance-health-hub'
						),
						count( $vance_dt_match['likely'] )
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<!-- Saved schemes -->
	<div class="vance-discount-surface vance-discount-saved-list">
		<h3><?php esc_html_e( 'Saved schemes', 'vance-health-hub' ); ?></h3>
		<?php if ( empty( $vance_dt_saved ) ) : ?>
			<div class="vance-discount-empty">
				<p><?php esc_html_e( "You haven't saved any schemes yet. Browse the directory to get started.", 'vance-health-hub' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/ibd-discounts/' ) ); ?>"><?php esc_html_e( 'Browse the directory', 'vance-health-hub' ); ?></a>
			</div>
		<?php else : ?>
			<div class="vance-discount-saved-grid">
				<?php foreach ( array_reverse( $vance_dt_saved ) as $vance_dt_post_id ) :
					$vance_dt_row = vance_discount_get( $vance_dt_post_id );
					if ( ! $vance_dt_row ) {
						continue; // Saved before an import re-sync removed/renamed it.
					}
					$vance_dt_status = isset( $vance_dt_statuses[ $vance_dt_post_id ]['status'] ) ? $vance_dt_statuses[ $vance_dt_post_id ]['status'] : 'interested';
					?>
					<div class="vance-discount-card vance-discount-card--compact">
						<div class="vance-discount-card__top">
							<?php echo vance_discount_tier_badge( vance_discount_effective_tier( $vance_dt_row ) ); ?>
							<select class="vance-discount-status-select" data-post-id="<?php echo esc_attr( $vance_dt_row['id'] ); ?>">
								<?php foreach ( $vance_dt_status_labels as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vance_dt_status, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<a href="<?php echo esc_url( $vance_dt_row['permalink'] ); ?>" class="vance-discount-card__title-link">
							<h3 class="vance-discount-card__title"><?php echo esc_html( $vance_dt_row['title'] ); ?></h3>
						</a>
						<?php if ( $vance_dt_row['value_summary'] ) : ?>
							<p class="vance-discount-card__value"><?php echo esc_html( $vance_dt_row['value_summary'] ); ?></p>
						<?php endif; ?>
						<div class="vance-discount-card__actions">
							<?php echo vance_discount_save_button( $vance_dt_row['id'] ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Access Folder -->
	<div class="vance-discount-surface vance-discount-folder">
		<h3><?php esc_html_e( 'Access Folder', 'vance-health-hub' ); ?></h3>
		<p class="vance-discount-folder-intro"><?php esc_html_e( 'Tick what you already hold. Nothing here is a document upload, just a checklist that tells you which schemes are worth a closer look.', 'vance-health-hub' ); ?></p>

		<div class="vance-discount-folder-region">
			<label for="vance-discount-folder-region"><?php esc_html_e( 'Region', 'vance-health-hub' ); ?></label>
			<select id="vance-discount-folder-region">
				<?php
				$vance_dt_regions = array(
					''         => __( 'Not set', 'vance-health-hub' ),
					'uk'       => __( 'UK-wide (no single nation)', 'vance-health-hub' ),
					'england'  => __( 'England', 'vance-health-hub' ),
					'wales'    => __( 'Wales', 'vance-health-hub' ),
					'scotland' => __( 'Scotland', 'vance-health-hub' ),
					'ni'       => __( 'Northern Ireland', 'vance-health-hub' ),
				);
				$vance_dt_region_val = isset( $vance_dt_folder['region'] ) ? $vance_dt_folder['region'] : '';
				foreach ( $vance_dt_regions as $slug => $label ) :
					?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $vance_dt_region_val, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="vance-discount-folder-grid">
			<?php foreach ( vance_discount_signal_labels() as $vance_dt_key => $vance_dt_label ) : ?>
				<div class="vance-discount-folder-row">
					<span><?php echo esc_html( $vance_dt_label ); ?></span>
					<button type="button" class="vance-discount-folder-toggle<?php echo ! empty( $vance_dt_folder[ $vance_dt_key ] ) ? ' is-on' : ''; ?>" role="switch" aria-checked="<?php echo ! empty( $vance_dt_folder[ $vance_dt_key ] ) ? 'true' : 'false'; ?>" data-signal="<?php echo esc_attr( $vance_dt_key ); ?>" aria-label="<?php echo esc_attr( $vance_dt_label ); ?>">
						<span class="vance-discount-folder-toggle__thumb"></span>
					</button>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="vance-discount-folder-status" id="vance-discount-folder-status" role="status" aria-live="polite"></p>
	</div>

</div>
