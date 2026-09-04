<?php
/**
 * Single Discount template — one scheme, full detail.
 *
 * Layout pattern (dark hero, two-column body) copied from
 * single-vance_recipe.php; card-building helpers (tier badge, apply-action
 * resolver, Save button) come from inc/discount-frontend.php so this file
 * and the directory grid can never disagree about what a scheme's apply
 * action or save state look like.
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

	$action = vance_discount_apply_action( $row );
	?>

	<main id="main-content">

	<section style="position:relative;background:linear-gradient(135deg, rgba(10,25,41,0.90) 0%, rgba(0,80,80,0.86) 100%); padding:110px 0 60px; color:#fff;">
		<div class="container" style="max-width:900px;">
			<?php if ( $row['category'] ) : ?>
				<span style="display:inline-block;background:rgba(255,255,255,0.15);color:#fff;font-size:12px;font-weight:700;padding:5px 14px;border-radius:var(--radius-pill, 999px);letter-spacing:0.3px;text-transform:uppercase;margin-bottom:16px;"><?php echo esc_html( $row['category']['name'] ); ?></span>
			<?php endif; ?>
			<h1 style="font-family:'Outfit',sans-serif;font-size:clamp(28px,4.2vw,44px);font-weight:900;color:#fff;margin:0 0 12px;line-height:1.15;"><?php the_title(); ?></h1>
			<?php if ( $row['provider'] ) : ?>
				<p style="color:rgba(255,255,255,0.85);font-size:15px;font-weight:600;margin:0 0 18px;"><?php echo esc_html( $row['provider'] ); ?></p>
			<?php endif; ?>
			<div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
				<?php echo vance_discount_tier_badge( vance_discount_effective_tier( $row ) ); ?>
				<?php if ( $row['region_names'] && ! in_array( 'UK', $row['region_names'], true ) ) : ?>
					<span style="color:rgba(255,255,255,0.8);font-size:13px;"><?php echo esc_html( implode( ', ', $row['region_names'] ) ); ?> <?php esc_html_e( 'only', 'vance-health-hub' ); ?></span>
				<?php endif; ?>
			</div>
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

	</main>

<?php endwhile; ?>

<?php get_footer(); ?>
