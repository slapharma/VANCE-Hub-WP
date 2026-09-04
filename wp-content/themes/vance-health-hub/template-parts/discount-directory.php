<?php
/**
 * IBD Discounts & Freebies — directory grid: category chips, region select,
 * search, and the card grid itself.
 *
 * Category chips follow recipe-hub-app.php's exact pattern (`?cat=` links,
 * server-side filtered so the grid is right with JS disabled) — see
 * docs/DISCOUNTS_UI_SPEC.md §3. Region and text search are JS-only
 * enhancements layered on top via assets/js/discounts.js, reading the
 * `data-cat` / `data-region` / `data-search` attributes
 * inc/discount-frontend.php already puts on every card.
 *
 * Self-contained, no arguments — same contract as recipe-hub-app.php.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vance_dc_cat_filter = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : '';
$vance_dc_categories = vance_discount_categories_in_use();
$vance_dc_rows       = vance_discount_directory_data();
$vance_dc_base_url   = get_permalink();

$vance_dc_regions = array(
	''         => __( 'All regions', 'vance-health-hub' ),
	'uk'       => __( 'UK-wide', 'vance-health-hub' ),
	'england'  => __( 'England', 'vance-health-hub' ),
	'wales'    => __( 'Wales', 'vance-health-hub' ),
	'scotland' => __( 'Scotland', 'vance-health-hub' ),
	'ni'       => __( 'Northern Ireland', 'vance-health-hub' ),
);
?>
<section class="vance-discount-section" id="discounts-grid">
	<div class="container">
		<div class="vance-discount-controls">
			<div class="vance-discount-chips">
				<a class="vance-discount-chip<?php echo ( '' === $vance_dc_cat_filter ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $vance_dc_base_url . '#discounts-grid' ); ?>"><?php esc_html_e( 'All', 'vance-health-hub' ); ?></a>
				<?php foreach ( $vance_dc_categories as $cat ) : ?>
					<a class="vance-discount-chip<?php echo ( $vance_dc_cat_filter === $cat['slug'] ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'cat', $cat['slug'], $vance_dc_base_url ) . '#discounts-grid' ); ?>"><?php echo esc_html( $cat['name'] ); ?></a>
				<?php endforeach; ?>
			</div>
			<div class="vance-discount-controls__right">
				<select class="vance-discount-region-select" id="vance-discount-region">
					<?php foreach ( $vance_dc_regions as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" class="vance-discount-search" id="vance-discount-search" placeholder="<?php esc_attr_e( 'Search schemes…', 'vance-health-hub' ); ?>">
			</div>
		</div>

		<div class="vance-discount-grid" id="vance-discount-grid">
			<?php
			$vance_dc_shown = 0;
			foreach ( $vance_dc_rows as $row ) :
				if ( $vance_dc_cat_filter && ( ! $row['category'] || $vance_dc_cat_filter !== $row['category']['slug'] ) ) {
					continue; // No-JS fallback: server-side filter when JS hasn't taken over.
				}
				$vance_dc_shown++;
				echo vance_render_discount_card( $row ); // phpcs:ignore WordPress.Security.EscapeOutput — vance_render_discount_card() escapes internally.
			endforeach;
			?>
		</div>

		<div class="vance-discount-empty" id="vance-discount-empty" hidden>
			<p><?php esc_html_e( 'No schemes match those filters.', 'vance-health-hub' ); ?></p>
			<a href="<?php echo esc_url( $vance_dc_base_url . '#discounts-grid' ); ?>" id="vance-discount-clear-filters"><?php esc_html_e( 'Clear filters', 'vance-health-hub' ); ?></a>
		</div>

		<?php if ( 0 === $vance_dc_shown ) : ?>
			<p class="vance-discount-empty-serverside">
				<?php echo $vance_dc_cat_filter
					? esc_html__( 'No schemes match that category.', 'vance-health-hub' )
					: esc_html__( 'No schemes are published yet — check back soon.', 'vance-health-hub' ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
