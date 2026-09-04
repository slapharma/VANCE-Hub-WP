<?php
/**
 * Single Discount template — one scheme, full detail.
 *
 * Hero is spotlight-styled (mint band, eyebrow pill, teal headline — the
 * real .vhh-hero-spotlight__eyebrow/__title classes from main.css) but sized
 * like a regular article's hero (single.php's height:300px), not the full
 * page-hero-spotlight treatment — see the comment above the hero markup.
 * Two-column body layout copied from single-vance_recipe.php; card-building
 * helpers (tier badge, apply-action resolver, Save button) come from
 * inc/discount-frontend.php so this file and the directory grid can never
 * disagree about what a scheme's apply action or save state look like.
 *
 * @package vance-health-hub
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	$row     = vance_discount_get( $post_id );

	if ( ! $row ) {
		// Meta missing (e.g. viewed before the import ran) — degrade to a
		// bare title rather than a fatal on undefined array keys.
		$row = array(
			'id' => $post_id, 'slug' => get_post_field( 'post_name', $post_id ), 'title' => get_the_title(), 'provider' => '', 'value_summary' => '',
			'cost' => '', 'what_you_get' => '', 'who_qualifies' => '', 'ibd_note' => '', 'evidence' => array(),
			'official_url' => '', 'apply_url' => '', 'apply_type' => '', 'apply_contact' => '', 'tier' => 3,
			'upcoming_change' => '', 'verified_on' => '', 'category' => null, 'region_names' => array(),
			'related_posts' => array(),
		);
	}

	$action     = vance_discount_apply_action( $row );
	$hero_image = vance_discount_hero_image( $row['slug'] );
	?>

	<main id="main-content">

	<?php
	/*
	 * Spotlight-styled hero, sized like a regular article's (single.php's
	 * `.oped-hero`, height:300px) rather than the full page-hero-spotlight
	 * treatment (photo + card + facts band, ~450-500px) — this page has
	 * borrowing the mint band + eyebrow pill + teal headline at article
	 * height keeps a single scheme page reading as content, not as a
	 * landing page. A per-scheme image (vance_discount_hero_image()) sits
	 * in a small fixed box beside the text, since most of these are
	 * provider logos rather than photography.
	 *
	 * Uses the real .vhh-hero-spotlight__eyebrow/__title classes from
	 * assets/css/main.css rather than reinventing the eyebrow pill and the
	 * teal headline colour, so this page can never drift from what "spotlight
	 * style" looks like everywhere else on the site. Height and the two extra
	 * rows (provider, tier/region) are this page's own — inline, matching
	 * this file's existing convention, not main.css's shared block.
	 */
	?>
	<section style="height:300px;min-height:0;display:flex;align-items:center;position:relative;overflow:hidden;background:linear-gradient(180deg, #ECF5F5 0%, #F6F9FA 100%);">
		<?php // max-width matches the body section's container below (not the
		// former dark hero's 900px) so the hero and body text share one left
		// edge — reported live 2026-09-04: the two visibly disagreed the first
		// time too, despite an identical max-width. Root cause: this section
		// is display:flex (for align-items:center's vertical centering), and
		// .container's own sitewide `margin: 0 auto` is flexbox's auto-margin
		// centering mechanism on a flex item — it shrank .container to its
		// CONTENT width (740px) and centered that shrunk box, rather than
		// letting it span the full 1100px the way a normal block does. The
		// body section below isn't display:flex, so it never hit this.
		// width:100% forces the flex item to fill the main axis, leaving no
		// space for the auto-margins to redistribute. ?>
		<div class="container" style="max-width:1100px;width:100%;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:24px;">
			<div style="flex:1;min-width:260px;">
				<?php if ( $row['category'] ) : ?>
					<?php // The correct action for a category pill: back to the
					// directory, pre-filtered to this scheme's own category. ?>
					<a class="vhh-hero-spotlight__eyebrow vance-discount-hero-eyebrow" href="<?php echo esc_url( add_query_arg( 'cat', $row['category']['slug'], home_url( '/ibd-discounts/' ) ) . '#discounts-grid' ); ?>"><?php echo esc_html( $row['category']['name'] ); ?></a>
				<?php endif; ?>
				<h1 class="vhh-hero-spotlight__title" style="font-size:clamp(26px,3.4vw,38px);max-width:700px;margin:0 0 8px;"><?php the_title(); ?></h1>
				<?php if ( $row['provider'] ) : ?>
					<p style="color:#3F4B4E;font-size:15px;font-weight:600;margin:0 0 14px;"><?php echo esc_html( $row['provider'] ); ?></p>
				<?php endif; ?>
				<div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
					<?php echo vance_discount_tier_badge( vance_discount_effective_tier( $row ) ); ?>
					<?php if ( $row['region_names'] && ! in_array( 'UK', $row['region_names'], true ) ) : ?>
						<span style="color:#64748B;font-size:13px;"><?php echo esc_html( implode( ', ', $row['region_names'] ) ); ?> <?php esc_html_e( 'only', 'vance-health-hub' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $hero_image ) : ?>
				<div style="flex:0 0 auto;width:220px;height:150px;background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius-surface, 24px);display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;">
					<img src="<?php echo esc_url( $hero_image['url'] ); ?>" alt="<?php echo esc_attr( $hero_image['alt'] ); ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section style="padding:48px 0 60px;">
		<div class="container" style="max-width:1100px;">
			<div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:40px;align-items:start;">

				<div>
					<?php if ( $row['value_summary'] ) : ?>
						<p style="font-size:19px;font-weight:700;color:#0f172a;margin:0 0 6px;"><?php echo esc_html( $row['value_summary'] ); ?></p>
					<?php endif; ?>
					<?php if ( $row['cost'] ) : ?>
						<p style="font-size:14px;color:#475569;margin:0 0 20px;"><?php esc_html_e( 'Cost:', 'vance-health-hub' ); ?> <?php echo esc_html( $row['cost'] ); ?></p>
					<?php endif; ?>

					<?php if ( $row['upcoming_change'] ) : ?>
						<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:var(--radius-field, 16px);padding:14px 18px;margin-bottom:24px;font-size:14px;color:#92400E;">
							<strong><?php esc_html_e( 'Upcoming change:', 'vance-health-hub' ); ?></strong> <?php echo esc_html( $row['upcoming_change'] ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $row['what_you_get'] ) : ?>
						<h2 style="font-size:20px;color:#0f172a;margin:0 0 10px;"><?php esc_html_e( 'What you get', 'vance-health-hub' ); ?></h2>
						<p style="font-size:15px;line-height:1.7;color:#334155;margin:0 0 24px;"><?php echo esc_html( $row['what_you_get'] ); ?></p>
					<?php endif; ?>

					<?php if ( $row['who_qualifies'] ) : ?>
						<h2 style="font-size:20px;color:#0f172a;margin:0 0 10px;"><?php esc_html_e( 'Who qualifies', 'vance-health-hub' ); ?></h2>
						<p style="font-size:15px;line-height:1.7;color:#334155;margin:0 0 24px;"><?php echo esc_html( $row['who_qualifies'] ); ?></p>
					<?php endif; ?>

					<?php if ( $row['evidence'] ) : ?>
						<h2 style="font-size:20px;color:#0f172a;margin:0 0 10px;"><?php esc_html_e( 'Evidence accepted', 'vance-health-hub' ); ?></h2>
						<ul style="font-size:15px;line-height:1.8;color:#334155;margin:0 0 24px;padding-left:20px;">
							<?php foreach ( $row['evidence'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $row['ibd_note'] ) : ?>
						<div style="background:#ECFDF5;border-left:4px solid #10B981;border-radius:0 var(--radius-field, 16px) var(--radius-field, 16px) 0;padding:14px 18px;margin-bottom:24px;font-size:14px;color:#065F46;">
							<strong><?php esc_html_e( 'Why this matters for IBD:', 'vance-health-hub' ); ?></strong> <?php echo esc_html( $row['ibd_note'] ); ?>
						</div>
					<?php endif; ?>

					<div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;margin-top:12px;">
						<?php echo vance_discount_render_apply_group( $action ); ?>
						<?php echo vance_discount_save_button( $row['id'] ); ?>
					</div>
				</div>

				<aside>
					<div style="background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius-surface, 24px);padding:20px;">
						<?php if ( $row['official_url'] ) : ?>
							<p style="margin:0 0 12px;"><a href="<?php echo esc_url( $row['official_url'] ); ?>" target="_blank" rel="noopener" style="font-size:14px;font-weight:600;color:var(--primary-color);"><?php esc_html_e( 'Official information', 'vance-health-hub' ); ?> &rarr;</a></p>
						<?php endif; ?>
						<?php if ( $row['verified_on'] ) : ?>
							<p style="margin:0;font-size:12px;color:#94A3B8;"><?php esc_html_e( 'Checked', 'vance-health-hub' ); ?> <?php echo esc_html( $row['verified_on'] ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $row['related_posts'] ) ) : ?>
						<div style="margin-top:20px;">
							<h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.3px;color:#475569;margin:0 0 10px;"><?php esc_html_e( 'Related reading', 'vance-health-hub' ); ?></h3>
							<ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
								<?php foreach ( $row['related_posts'] as $related_id ) :
									$related_post = get_post( $related_id );
									if ( ! $related_post || 'publish' !== $related_post->post_status ) {
										continue;
									}
									?>
									<li><a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>" style="font-size:14px;color:#0f172a;font-weight:600;"><?php echo esc_html( get_the_title( $related_post ) ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</aside>

			</div>
		</div>
	</section>

	<?php if ( 'watersure' === $row['slug'] ) : ?>
		<section id="water-companies" style="padding:0 0 60px;">
			<div class="container" style="max-width:1100px;">
				<div class="vance-discount-surface" style="max-width:700px;margin:0 auto;">
					<h2 style="font-size:18px;margin:0 0 6px;color:#0f172a;"><?php esc_html_e( 'Find your water company', 'vance-health-hub' ); ?></h2>
					<p style="font-size:13px;color:#475569;margin:0 0 16px;"><?php esc_html_e( 'WaterSure is applied for through whichever company bills your household, not centrally. Find yours below. Every link goes straight to that company\'s own WaterSure (or identically-named) page.', 'vance-health-hub' ); ?></p>
					<input type="search" id="vance-watersure-filter" placeholder="<?php esc_attr_e( 'Search by company or area…', 'vance-health-hub' ); ?>" style="width:100%;padding:9px 14px;border:1px solid #e2e8f0;border-radius:var(--radius-field, 16px);font-size:14px;margin-bottom:16px;box-sizing:border-box;">
					<ul id="vance-watersure-list" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px;">
						<?php foreach ( vance_watersure_suppliers() as $vance_ws_company ) : ?>
							<li class="vance-watersure-row" data-search="<?php echo esc_attr( strtolower( $vance_ws_company['name'] . ' ' . $vance_ws_company['regions'] ) ); ?>" style="padding:10px 0;border-bottom:1px solid #F1F5F9;">
								<a href="<?php echo esc_url( $vance_ws_company['url'] ); ?>" target="_blank" rel="noopener" style="font-size:14px;font-weight:700;color:#0f172a;text-decoration:none;"><?php echo esc_html( $vance_ws_company['name'] ); ?></a>
								<div style="font-size:12px;color:#64748B;margin-top:2px;"><?php echo esc_html( $vance_ws_company['regions'] ); ?></div>
							</li>
						<?php endforeach; ?>
					</ul>
					<p id="vance-watersure-empty" style="display:none;text-align:center;color:#64748B;font-size:13px;padding:20px 0;"><?php esc_html_e( "No companies match that search: try a shorter word, or check Citizens Advice for who supplies your address.", 'vance-health-hub' ); ?></p>
				</div>
			</div>
		</section>
		<script>
		(function () {
			var input = document.getElementById('vance-watersure-filter');
			var rows = document.querySelectorAll('.vance-watersure-row');
			var empty = document.getElementById('vance-watersure-empty');
			if (!input) { return; }
			input.addEventListener('input', function () {
				var term = input.value.toLowerCase().trim();
				var visible = 0;
				rows.forEach(function (row) {
					var match = !term || row.getAttribute('data-search').indexOf(term) !== -1;
					row.style.display = match ? '' : 'none';
					if (match) { visible++; }
				});
				empty.style.display = visible ? 'none' : 'block';
			});
		})();
		</script>
	<?php endif; ?>

	</main>

<?php endwhile; ?>

<?php get_footer(); ?>
