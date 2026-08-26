<?php
/**
 * Search results.
 *
 * Added 2026-08-26 alongside the header search field. Without this file WP
 * falls back to index.php, whose page title calls single_post_title() - which
 * prints nothing on a search request, so results arrived under a blank heading
 * with no way to refine the query.
 *
 * Every piece of copy, the hero image/overlay/height, the title colour and size
 * and the button colours are editable at
 * Appearance -> Customize -> Page - Search Results.
 *
 * Two copy fields carry tokens rather than sprintf placeholders: {query} and
 * {count}. sprintf() would fatal the page if an editor typed a stray % into the
 * field, and there is no reason a copy field should be able to do that.
 *
 * Card markup deliberately mirrors archive.php's .portal-grid / .news-card so
 * results look like every other listing on the site.
 */
get_header();

$vance_search_term  = get_search_query();
$vance_result_count = (int) $GLOBALS['wp_query']->found_posts;

/**
 * Substitute the {query}/{count} tokens.
 *
 * The query is escaped HERE, not by the caller, because the title field allows
 * HTML (wp_kses_post) and so cannot be escaped wholesale afterwards - the
 * untrusted half has to be neutralised before it is merged into the trusted
 * template.
 */
$vance_search_tokens = static function ( $template ) use ( $vance_search_term, $vance_result_count ) {
	return strtr(
		(string) $template,
		array(
			'{query}' => esc_html( $vance_search_term ),
			'{count}' => esc_html( number_format_i18n( $vance_result_count ) ),
		)
	);
};

$vance_has_term = ( '' !== trim( $vance_search_term ) );

$vance_hero_bg = vance_get_theme_mod( 'vance_search_hero_bg' );
if ( ! $vance_hero_bg ) {
	$vance_hero_bg = vance_get_theme_mod( 'vance_category_hero_image' );
}
if ( ! $vance_hero_bg ) {
	$vance_hero_bg = get_template_directory_uri() . '/assets/img/news_hero.png';
}

$vance_hero_overlay        = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_search_hero_overlay', 72 ) ) ) ) / 100;
$vance_hero_overlay_bottom = min( 1, $vance_hero_overlay + 0.06 );
$vance_hero_height         = max( 180, absint( vance_get_theme_mod( 'vance_search_hero_height', 300 ) ) );
$vance_hero_tag            = vance_get_theme_mod( 'vance_search_hero_tag', 'Search Results' );
$vance_title_color         = vance_get_theme_mod( 'vance_search_hero_title_color', '#ffffff' );
$vance_title_size          = max( 20, absint( vance_get_theme_mod( 'vance_search_hero_title_size', 42 ) ) );

$vance_hero_title = $vance_has_term
	? vance_get_theme_mod( 'vance_search_hero_title', 'Results for &#8220;{query}&#8221;' )
	: vance_get_theme_mod( 'vance_search_hero_title_empty', 'Search the Hub' );

$vance_count_copy = ( 1 === $vance_result_count )
	? vance_get_theme_mod( 'vance_search_hero_count_one', '{count} matching item.' )
	: vance_get_theme_mod( 'vance_search_hero_count_many', '{count} matching items.' );

$vance_show_form   = (bool) vance_get_theme_mod( 'vance_search_form_show', true );
$vance_placeholder = vance_get_theme_mod( 'vance_search_form_placeholder', 'Refine your search...' );
$vance_btn_label   = vance_get_theme_mod( 'vance_search_form_button', 'Search' );
$vance_btn_bg      = vance_get_theme_mod( 'vance_search_form_btn_bg', '' );
$vance_btn_color   = vance_get_theme_mod( 'vance_search_form_btn_color', '' );

// Blank colour settings must fall through to the stylesheet, not emit an empty
// declaration, so the inline style is assembled rather than interpolated.
$vance_btn_style = '';
if ( $vance_btn_bg ) {
	$vance_btn_style .= 'background:' . $vance_btn_bg . ';border-color:' . $vance_btn_bg . ';';
}
if ( $vance_btn_color ) {
	$vance_btn_style .= 'color:' . $vance_btn_color . ';';
}
?>

<main id="main-content">

	<section class="hero search-hero" style="height: <?php echo esc_attr( $vance_hero_height ); ?>px; min-height: 0; display: flex; align-items: center; padding: 0; position: relative; overflow: hidden;">
		<div style="position: absolute; inset: 0; background-image: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $vance_hero_overlay ); ?>), rgba(20,40,70,<?php echo esc_attr( $vance_hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $vance_hero_bg ); ?>'); background-position: center center; background-size: cover; background-repeat: no-repeat; z-index: 1;"></div>
		<div class="container" style="position: relative; z-index: 2; width: 100%;">
			<div class="hero-content" style="max-width: 760px;">
				<?php if ( $vance_hero_tag ) : ?>
					<span class="tag-label"><?php echo esc_html( $vance_hero_tag ); ?></span>
				<?php endif; ?>

				<h1 style="font-size: <?php echo esc_attr( $vance_title_size ); ?>px; color: <?php echo esc_attr( $vance_title_color ); ?>; font-weight: 700; margin: 8px 0 0; line-height: 1.15;">
					<?php echo wp_kses_post( $vance_search_tokens( $vance_hero_title ) ); ?>
				</h1>

				<?php if ( $vance_count_copy ) : ?>
					<p style="color: rgba(255,255,255,0.82); margin: 10px 0 0; font-size: 16px;">
						<?php echo esc_html( $vance_search_tokens( $vance_count_copy ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $vance_show_form ) : ?>
				<form role="search" method="get" class="vance-search-again" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input
						type="search"
						name="s"
						class="vance-search-again__field"
						value="<?php echo esc_attr( $vance_search_term ); ?>"
						placeholder="<?php echo esc_attr( $vance_placeholder ); ?>"
						aria-label="<?php esc_attr_e( 'Search the Hub', 'vance-health-hub' ); ?>">
					<button type="submit" class="vance-search-again__submit"<?php echo $vance_btn_style ? ' style="' . esc_attr( $vance_btn_style ) . '"' : ''; ?>><?php echo esc_html( $vance_btn_label ); ?></button>
				</form>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="container" style="padding: 60px 20px;">
		<?php if ( have_posts() ) : ?>

			<div class="portal-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					$vance_thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'news-card' ); ?> data-vhh-post-id="<?php echo (int) get_the_ID(); ?>">
						<div class="card-image" style="background-image: url('<?php echo esc_url( $vance_thumb ); ?>'); background-color: #e2e8f0; position: relative;">
							<?php
							if ( function_exists( 'vance_card_eyebrow_html' ) ) {
								echo vance_card_eyebrow_html( get_the_ID(), true );
							}
							?>
						</div>

						<div class="card-content">
							<header class="entry-header">
								<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark" class="card-stretched-link" style="font-size: 20px;">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
							</header>

							<div class="entry-content">
								<?php the_excerpt(); ?>
							</div>

							<?php
							if ( function_exists( 'vance_card_meta_footer_html' ) ) {
								echo vance_card_meta_footer_html( get_the_ID() );
							}
							?>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="pagination" style="margin-top: 40px;">
				<?php the_posts_pagination(); ?>
			</div>

		<?php else : ?>

			<div class="vance-search-empty">
				<h2><?php echo esc_html( vance_get_theme_mod( 'vance_search_empty_title', 'Nothing matched that search' ) ); ?></h2>
				<p><?php echo esc_html( vance_get_theme_mod( 'vance_search_empty_desc', 'Try a broader term, check the spelling, or start from one of these:' ) ); ?></p>

				<?php
				if ( vance_get_theme_mod( 'vance_search_empty_show_cats', true ) ) :
					$vance_uncat        = get_category_by_slug( 'uncategorized' );
					$vance_empty_number = max( 1, absint( vance_get_theme_mod( 'vance_search_empty_cat_count', 6 ) ) );

					$vance_empty_cats = get_categories( array(
						'orderby'    => 'count',
						'order'      => 'DESC',
						'number'     => $vance_empty_number,
						'hide_empty' => true,
						'exclude'    => $vance_uncat ? array( $vance_uncat->term_id ) : array(),
					) );

					if ( ! empty( $vance_empty_cats ) ) :
						?>
						<div class="vance-search-empty__links">
							<?php foreach ( $vance_empty_cats as $vance_empty_cat ) : ?>
								<a href="<?php echo esc_url( get_category_link( $vance_empty_cat->term_id ) ); ?>"><?php echo esc_html( $vance_empty_cat->name ); ?></a>
							<?php endforeach; ?>
						</div>
						<?php
					endif;
				endif;
				?>
			</div>

		<?php endif; ?>
	</div>

</main>

<?php get_footer(); ?>
