<?php
/**
 * Single Recipe template — native replacement for the iframed recipe export.
 *
 * @package vance-health-hub
 */

get_header();

while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();

	$hero_bg = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'full' ) : get_template_directory_uri() . '/assets/img/patient_hero.png';

	$cat_terms = get_the_terms( $post_id, 'vance_recipe_cat' );
	$category  = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0] : null;
	$tag_terms = get_the_terms( $post_id, 'vance_recipe_tag' );
	$tags      = ( $tag_terms && ! is_wp_error( $tag_terms ) ) ? $tag_terms : array();

	$servings = get_post_meta( $post_id, '_vance_recipe_servings', true );
	$prep     = get_post_meta( $post_id, '_vance_recipe_prep_min', true );
	$cook     = get_post_meta( $post_id, '_vance_recipe_cook_min', true );
	$total    = ( '' !== $prep ? (int) $prep : 0 ) + ( '' !== $cook ? (int) $cook : 0 );

	$ingredients = get_post_meta( $post_id, '_vance_recipe_ingredients', true );
	$ingredients = is_array( $ingredients ) ? $ingredients : array();
	$method      = get_post_meta( $post_id, '_vance_recipe_method', true );
	$method      = is_array( $method ) ? $method : array();

	$credit_line = vance_recipe_credit_line_html( $post_id );
	$json_ld     = vance_recipe_json_ld( $post_id );
	?>

	<?php if ( $json_ld ) : ?>
		<?php echo $json_ld; // phpcs:ignore -- pre-escaped JSON-LD from vance_recipe_json_ld(). ?>
	<?php endif; ?>

	<main id="main-content">

	<section style="position:relative;background:linear-gradient(135deg, rgba(10,25,41,0.90) 0%, rgba(0,80,80,0.86) 100%), url('<?php echo esc_url( $hero_bg ); ?>') no-repeat center center; background-size:cover; padding:110px 0 60px; color:#fff;">
		<div class="container" style="max-width:900px;">
			<?php if ( $category ) : ?>
				<span style="display:inline-block;background:rgba(255,255,255,0.15);color:#fff;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;letter-spacing:0.3px;text-transform:uppercase;margin-bottom:16px;"><?php echo esc_html( $category->name ); ?></span>
			<?php endif; ?>
			<h1 style="font-family:'Outfit',sans-serif;font-size:clamp(30px,4.5vw,48px);font-weight:900;color:#fff;margin:0 0 16px;line-height:1.15;"><?php the_title(); ?></h1>

			<?php if ( $tags ) : ?>
				<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px;">
					<?php foreach ( $tags as $tag ) : ?>
						<span style="background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.9);font-size:12px;font-weight:600;padding:4px 12px;border-radius:16px;"><?php echo esc_html( $tag->name ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div style="display:flex;flex-wrap:wrap;gap:24px;font-size:14px;color:rgba(255,255,255,0.85);font-weight:600;">
				<?php if ( $total ) : ?>
					<span>&#9201; <?php echo esc_html( $total ); ?> min total</span>
				<?php endif; ?>
				<?php if ( '' !== $servings ) : ?>
					<span>&#127860; Serves <?php echo esc_html( $servings ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section style="padding:48px 0 60px;">
		<div class="container" style="max-width:1100px;">
			<div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:40px;align-items:start;">

				<div style="min-width:0;">
					<?php
					$content = get_the_content();
					if ( $content ) {
						echo '<div style="font-size:16px;line-height:1.8;color:#334155;margin-bottom:36px;">' . apply_filters( 'the_content', $content ) . '</div>'; // phpcs:ignore -- standard content filter output.
					}
					?>

					<?php if ( $ingredients ) : ?>
						<h2 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:800;color:#0A1929;margin:0 0 16px;">Ingredients</h2>
						<?php foreach ( $ingredients as $section ) :
							$section_name = isset( $section['section'] ) ? trim( (string) $section['section'] ) : '';
							$items        = isset( $section['items'] ) ? (array) $section['items'] : array();
							if ( ! $items ) {
								continue;
							}
							?>
							<?php if ( $section_name ) : ?>
								<h3 style="font-size:15px;font-weight:700;color:var(--primary-color);margin:20px 0 10px;"><?php echo esc_html( $section_name ); ?></h3>
							<?php endif; ?>
							<ul style="list-style:none;margin:0 0 8px;padding:0;">
								<?php foreach ( $items as $item ) : ?>
									<li style="display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:15px;color:#334155;">
										<span style="flex:none;width:6px;height:6px;border-radius:50%;background:var(--primary-color);margin-top:8px;"></span>
										<span><?php echo esc_html( $item ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( $method ) : ?>
						<h2 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:800;color:#0A1929;margin:36px 0 16px;">Method</h2>
						<ol style="list-style:none;margin:0;padding:0;">
							<?php foreach ( $method as $i => $step ) : ?>
								<li style="display:flex;gap:14px;padding:0 0 18px;">
									<span style="flex:none;width:28px;height:28px;border-radius:50%;background:var(--primary-color);color:#fff;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;"><?php echo (int) $i + 1; ?></span>
									<span style="padding-top:3px;font-size:15px;line-height:1.7;color:#334155;"><?php echo esc_html( $step ); ?></span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>

					<div style="margin-top:32px;">
						<a href="<?php echo esc_url( home_url( '/gastro-meal-planner/?add=' . get_post_field( 'post_name', $post_id ) . '#planner' ) ); ?>" style="display:inline-flex;align-items:center;gap:8px;background:var(--primary-color);color:#fff;font-weight:700;font-size:15px;padding:14px 28px;border-radius:8px;text-decoration:none;">
							Add to meal plan
						</a>
					</div>

					<div class="va-article-disclaimer" style="margin-top:40px;padding:20px 24px;background:#EEF6F6;border-left:4px solid var(--primary-color);font-size:14px;line-height:1.75;color:#475569;">
						<strong style="color:var(--secondary-color);">For general information only.</strong> This recipe is for general information and is not a substitute for professional medical or dietary advice. Always talk to your GP, dietitian or healthcare team before making significant changes to your diet, especially if you are managing a gastrointestinal condition.
					</div>
				</div>

				<aside style="min-width:0;">
					<?php echo vance_recipe_nutrition_panel_html( $post_id ); // phpcs:ignore -- pre-escaped in vance_recipe_nutrition_panel_html(). ?>
					<?php if ( $credit_line ) : ?>
						<p style="margin:14px 4px 0;font-size:11px;color:#94a3b8;line-height:1.6;"><?php echo $credit_line; // phpcs:ignore -- pre-escaped in vance_recipe_credit_line_html(). ?></p>
					<?php endif; ?>
				</aside>

			</div>
		</div>
	</section>

	<?php if ( $category ) :
		$related = new WP_Query(
			array(
				'post_type'      => 'vance_recipe',
				'posts_per_page' => 3,
				'post__not_in'   => array( $post_id ),
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'vance_recipe_cat',
						'field'    => 'term_id',
						'terms'    => $category->term_id,
					),
				),
			)
		);
		if ( $related->have_posts() ) :
			?>
			<section style="padding:50px 0 70px;background:#f8fafc;border-top:1px solid #e2e8f0;">
				<div class="container" style="max-width:1100px;">
					<h3 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:800;color:#0A1929;margin:0 0 26px;">More <?php echo esc_html( $category->name ); ?> Recipes</h3>
					<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							$thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
							?>
							<a href="<?php the_permalink(); ?>" style="text-decoration:none;display:flex;flex-direction:column;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border:1px solid #e2e8f0;">
								<div style="height:160px;background-color:#cbd5e1;background-image:url('<?php echo esc_url( $thumb ); ?>');background-size:cover;background-position:center;"></div>
								<div style="padding:18px;">
									<h4 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;line-height:1.4;"><?php the_title(); ?></h4>
								</div>
							</a>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
			<?php
			wp_reset_postdata();
		endif;
	endif;
	?>

	</main>

<?php
endwhile;

get_footer();
