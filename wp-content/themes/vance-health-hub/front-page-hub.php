<?php
/**
 * Front page, Hub layout: tools and community first.
 *
 * Loaded in place of front-page.php by vance_hub_template_include() in
 * inc/hub-layout.php, only when the "Home layout" setting is Hub (or an
 * administrator previews it with ?vhh_layout=hub). Styles: assets/css/hub-home.css.
 *
 * THE DATA RULE: every number, name and post below comes from a real query or a
 * Customizer setting. A section with nothing real behind it does not render.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$vhub_logged_in = is_user_logged_in();
$vhub_askai_url = vance_hub_page_url( 'ask-ai', '/ask-ai/' );
$vhub_survey    = vance_hub_page_url( 'gastro-health-survey', '/gastro-health-survey/' );
$vhub_signup    = home_url( '/login/?tab=signup' );
$vhub_signin    = home_url( '/login/' );
$vhub_dashboard = home_url( '/dashboard/' );
$vhub_bar       = vance_hub_member_bar();
$vhub_hero_img  = vance_hub_img( 'about/community-support.jpg' );
$vhub_survey_n  = function_exists( 'vance_quiz_steps' ) ? count( vance_quiz_steps() ) : 0;
?>

<main id="main" class="vhub">

<?php /* ---------------------------------------------------------------
 * a. Member bar. Real counts only, or the Founding Member line, or nothing.
 *    When the VanceHubClub header is active it already printed the bar under
 *    the header, so it is not printed a second time here.
 * ------------------------------------------------------------------ */ ?>
<?php
if ( ! ( function_exists( 'vance_hub_header_active' ) && vance_hub_header_active() ) ) {
	vance_hub_render_member_bar( array( 'join_href' => '#vhub-join' ) );
}
?>

<?php /* ---------------------------------------------------------------
 * b. Hero: headline, Ask VANCE-Ai, photo, survey starter. Fact band follows it.
 * ------------------------------------------------------------------ */ ?>
<section class="vhub-hero" style="<?php echo esc_attr( vance_hub_hero_style( vance_get_theme_mod( 'vance_hero_spotlight_bg_from', '#ECF5F5' ), vance_get_theme_mod( 'vance_hero_spotlight_bg_to', '#F6F9FA' ) ) ); ?>" aria-labelledby="vhub-hero-title">
	<div class="vhub-wrap vhub-hero__inner">
		<div class="vhub-hero__copy">
			<div class="vhub-hero__head"<?php if ( $vhub_hero_img ) : ?> style="--vhub-hero-photo: url('<?php echo esc_url( $vhub_hero_img ); ?>');"<?php endif; ?>>
				<p class="vhub-pill vhub-pill--purple"><?php esc_html_e( 'Your Gastro Health Community', 'vance-health-hub' ); ?></p>
				<h1 id="vhub-hero-title" class="vhub-hero__title"><?php esc_html_e( 'What do you need help with', 'vance-health-hub' ); ?> <span class="vhub-hero__accent"><?php esc_html_e( 'today?', 'vance-health-hub' ); ?></span></h1>
			</div>
			<p class="vhub-hero__sub"><?php esc_html_e( 'Take a short gut health survey, plan meals, read the science and get plain answers from our VANCE-Ai.', 'vance-health-hub' ); ?></p>

			<?php
			/* The input has no name on purpose: Ask AI takes no query parameter,
			   and a health question must not end up in a URL (server logs,
			   analytics). With JavaScript, hub-home.js opens the site-wide
			   VANCE-Ai chat and asks the question. Without it, the form opens
			   the VANCE-Ai page. */
			?>
			<form class="vhub-ask" action="<?php echo esc_url( $vhub_askai_url ); ?>" method="get" data-vhub-ask-form>
				<label class="vhub-ask__label" for="vhub-ask-input"><?php esc_html_e( 'Ask VANCE-Ai a question', 'vance-health-hub' ); ?></label>
				<div class="vhub-ask__box">
					<input id="vhub-ask-input" class="vhub-ask__input" type="text" autocomplete="off" placeholder="<?php esc_attr_e( 'For example: what can I eat during a flare?', 'vance-health-hub' ); ?>">
					<button class="vhub-ask__btn" type="submit"><?php esc_html_e( 'Ask', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></button>
				</div>
			</form>
		</div>

		<div class="vhub-hero__media">
			<?php if ( $vhub_hero_img ) : ?>
				<img class="vhub-hero__img" src="<?php echo esc_url( $vhub_hero_img ); ?>" width="800" height="650" alt="<?php esc_attr_e( 'Five people sit in a circle of chairs and talk; one woman holds a clipboard', 'vance-health-hub' ); ?>">
			<?php endif; ?>
			<div class="vhub-survey<?php echo $vhub_hero_img ? '' : ' vhub-survey--flat'; ?>">
				<div class="vhub-survey__meta">
					<p class="vhub-survey__eyebrow"><?php esc_html_e( 'Gastro Health Survey', 'vance-health-hub' ); ?></p>
					<?php if ( $vhub_survey_n > 0 ) : ?>
						<p class="vhub-survey__count"><?php
							/* translators: %s: number of survey questions. */
							echo esc_html( sprintf( _n( '%s question', '%s questions', $vhub_survey_n, 'vance-health-hub' ), number_format_i18n( $vhub_survey_n ) ) );
						?></p>
					<?php endif; ?>
				</div>
				<h2 class="vhub-survey__title"><?php esc_html_e( 'Not sure where to start?', 'vance-health-hub' ); ?></h2>
				<p class="vhub-survey__text"><?php esc_html_e( 'Answer a few short questions about you and your gut. The Hub then points you to the guides, tools and recipes that fit.', 'vance-health-hub' ); ?></p>
				<a class="vhub-btn vhub-btn--purple" href="<?php echo esc_url( $vhub_survey ); ?>"><?php esc_html_e( 'Start the survey', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
				<p class="vhub-survey__note"><?php esc_html_e( 'You can change any answer later. This is not a diagnosis.', 'vance-health-hub' ); ?></p>
			</div>
		</div>
	</div>
</section>

<?php /* ---------------------------------------------------------------
 * d. Fact band. Three static facts, each checked against the code.
 * ------------------------------------------------------------------ */ ?>
<div class="vhub-wrap vhub-facts-wrap">
	<ul class="vhub-facts">
		<li class="vhub-fact">
			<span class="vhub-fact__icon"><?php echo vance_hub_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="vhub-fact__text"><strong><?php esc_html_e( 'Free Forever', 'vance-health-hub' ); ?></strong><span><?php esc_html_e( 'Every tool available 24/7', 'vance-health-hub' ); ?></span></span>
		</li>
		<li class="vhub-fact">
			<span class="vhub-fact__icon vhub-fact__icon--purple"><?php echo vance_hub_icon( 'person' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="vhub-fact__text"><strong><?php esc_html_e( 'Activity driven', 'vance-health-hub' ); ?></strong><span><?php esc_html_e( 'Save searches, answers and results.', 'vance-health-hub' ); ?></span></span>
		</li>
		<li class="vhub-fact">
			<span class="vhub-fact__icon"><?php echo vance_hub_icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="vhub-fact__text"><strong><?php esc_html_e( 'Verified Science', 'vance-health-hub' ); ?></strong><span><?php esc_html_e( 'Every clinical source referenced.', 'vance-health-hub' ); ?></span></span>
		</li>
	</ul>
</div>

<?php /* ---------------------------------------------------------------
 * c. Four tool tiles. Real URLs; the recipe count is live.
 * ------------------------------------------------------------------ */ ?>
<section class="vhub-tools-section" aria-labelledby="vhub-tools-title">
	<h2 id="vhub-tools-title" class="screen-reader-text vhub-sr"><?php esc_html_e( 'Free tools', 'vance-health-hub' ); ?></h2>
	<ul class="vhub-wrap vhub-tools">
		<?php foreach ( vance_hub_tools() as $vhub_tool ) : ?>
			<li class="vhub-tool vhub-tool--<?php echo esc_attr( $vhub_tool['mod'] ); ?>">
				<a class="vhub-tool__link" href="<?php echo esc_url( $vhub_tool['href'] ); ?>">
					<?php if ( $vhub_tool['img'] ) : ?>
						<img class="vhub-tool__img" src="<?php echo esc_url( $vhub_tool['img'] ); ?>" alt="<?php echo esc_attr( $vhub_tool['alt'] ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<span class="vhub-tool__body">
						<?php echo vance_hub_icon( $vhub_tool['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted static SVG. ?>
						<h3 class="vhub-tool__title"><?php echo esc_html( $vhub_tool['title'] ); ?></h3>
						<span class="vhub-tool__desc"><?php echo esc_html( $vhub_tool['desc'] ); ?></span>
						<span class="vhub-tool__cta"><?php echo esc_html( $vhub_tool['cta'] ); ?> <span aria-hidden="true">&rarr;</span></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>

<?php /* ---------------------------------------------------------------
 * e. Inside the Hub this week. Customizer tiles only; empty by default.
 * ------------------------------------------------------------------ */
$vhub_tiles = vance_hub_member_tiles();
if ( ! empty( $vhub_tiles ) ) : ?>
<section class="vhub-wrap vhub-section vhub-inside" aria-labelledby="vhub-inside-title">
	<div class="vhub-section__head">
		<div class="vhub-section__intro">
			<p class="vhub-pill vhub-pill--purple"><?php esc_html_e( 'Inside the Hub this week', 'vance-health-hub' ); ?></p>
			<h2 id="vhub-inside-title" class="vhub-h2"><?php esc_html_e( 'What members have open now', 'vance-health-hub' ); ?></h2>
			<p class="vhub-lead"><?php esc_html_e( 'The tools are free for everyone. These are for members. Membership is free.', 'vance-health-hub' ); ?></p>
		</div>
		<?php if ( $vhub_logged_in ) : ?>
			<a class="vhub-btn vhub-btn--purple" href="<?php echo esc_url( $vhub_dashboard ); ?>"><?php esc_html_e( 'Go to My Hub', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
		<?php else : ?>
			<a class="vhub-btn vhub-btn--purple" href="<?php echo esc_url( $vhub_signup ); ?>" data-vhub-join><?php esc_html_e( 'Take your place', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
		<?php endif; ?>
	</div>
	<ul class="vhub-member-tiles">
		<?php foreach ( $vhub_tiles as $vhub_tile ) :
			$vhub_tag = '' !== $vhub_tile['link'] ? 'a' : 'div';
			?>
			<li>
				<<?php echo $vhub_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 'a' or 'div'. ?> class="vhub-member-tile"<?php if ( 'a' === $vhub_tag ) : ?> href="<?php echo esc_url( $vhub_tile['link'] ); ?>"<?php endif; ?>>
					<?php if ( '' !== $vhub_tile['badge'] ) : ?>
						<span class="vhub-member-tile__badge"><?php echo esc_html( $vhub_tile['badge'] ); ?></span>
					<?php endif; ?>
					<h3 class="vhub-member-tile__title"><?php echo esc_html( $vhub_tile['title'] ); ?></h3>
					<?php if ( '' !== $vhub_tile['text'] ) : ?>
						<span class="vhub-member-tile__text"><?php echo esc_html( $vhub_tile['text'] ); ?></span>
					<?php endif; ?>
					<?php if ( 'a' === $vhub_tag ) : ?>
						<span class="vhub-member-tile__cta"><?php esc_html_e( 'Open', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></span>
					<?php endif; ?>
				</<?php echo $vhub_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * g. Conditions: photo tiles and a list, from the real registry.
 * ------------------------------------------------------------------ */
$vhub_cards = function_exists( 'vance_gi_condition_cards' ) ? vance_gi_condition_cards() : array();
if ( ! empty( $vhub_cards ) && function_exists( 'vance_gi_page_url' ) ) :
	list( $vhub_cond_tiles, $vhub_cond_list ) = vance_hub_split_conditions(
		$vhub_cards,
		vance_condition_priority()
	);
	?>
<section class="vhub-wrap vhub-section vhub-conditions" aria-labelledby="vhub-conditions-title">
	<div class="vhub-section__intro">
		<h2 id="vhub-conditions-title" class="vhub-h2"><?php esc_html_e( 'Recently diagnosed? Learn about your condition', 'vance-health-hub' ); ?></h2>
		<p class="vhub-lead"><?php
			/* translators: %s: number of gut conditions with a guide. */
			echo esc_html( sprintf( _n( 'An easy-to-follow guide to %s common gut condition.', 'Easy-to-follow guides to %s common gut conditions.', count( $vhub_cards ), 'vance-health-hub' ), number_format_i18n( count( $vhub_cards ) ) ) );
		?></p>
	</div>
	<ul class="vhub-cond-grid">
		<?php foreach ( $vhub_cond_tiles as $vhub_c ) :
			$vhub_c_tile = vance_hub_condition_tile( $vhub_c );
			?>
			<li>
				<a class="vhub-cond-tile" href="<?php echo esc_url( vance_gi_page_url( $vhub_c['slug'] ) ); ?>">
					<?php if ( $vhub_c_tile['img'] ) : ?>
						<img class="vhub-cond-tile__img" src="<?php echo esc_url( $vhub_c_tile['img'] ); ?>" alt="<?php echo esc_attr( $vhub_c_tile['alt'] ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<span class="vhub-cond-tile__body">
						<span class="vhub-cond-tile__name"><?php echo esc_html( $vhub_c_tile['name'] ); ?></span>
						<span class="vhub-cond-tile__cta"><?php esc_html_e( 'Read the guide', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
		<li class="vhub-cond-list">
			<ul>
				<?php foreach ( $vhub_cond_list as $vhub_c ) : ?>
					<li><a class="vhub-cond-list__link" href="<?php echo esc_url( vance_gi_page_url( $vhub_c['slug'] ) ); ?>"><?php echo esc_html( wp_specialchars_decode( $vhub_c['title'] ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( function_exists( 'vance_gi_hub_url' ) ) : ?>
				<a class="vhub-cond-list__all" href="<?php echo esc_url( vance_gi_hub_url() ); ?>"><?php esc_html_e( 'View all conditions', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</li>
	</ul>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * f. Community block: real community quotes (the classic home page's
 *    testimonial source) plus the newest real recipes. No forum, poll or
 *    "members made this" data exists, so those parts are not built. With no
 *    quotes, the whole block is omitted.
 * ------------------------------------------------------------------ */
$vhub_quotes = vance_hub_testimonials( 4 );
if ( ! empty( $vhub_quotes ) ) :
	$vhub_recipe_groups = array_filter( array(
		array(
			'eyebrow' => __( 'New in the recipe hub', 'vance-health-hub' ),
			'title'   => __( 'Recently Added Recipes', 'vance-health-hub' ),
			'items'   => vance_hub_recent_recipes( 2 ),
		),
		array(
			'eyebrow' => __( 'Most read', 'vance-health-hub' ),
			'title'   => __( 'Most Popular Recipes', 'vance-health-hub' ),
			'items'   => vance_hub_popular_recipes( 2 ),
		),
	), function ( $vhub_g ) {
		return ! empty( $vhub_g['items'] );
	} );
	$vhub_meal_url = vance_hub_page_url( 'gastro-meal-planner', '/gastro-meal-planner/' );
	?>
<section class="vhub-community" aria-labelledby="vhub-community-title">
	<div class="vhub-wrap vhub-community__inner">
		<div class="vhub-community__head">
			<div class="vhub-section__intro">
				<p class="vhub-pill vhub-pill--teal"><?php esc_html_e( 'The Gastro Community', 'vance-health-hub' ); ?></p>
				<h2 id="vhub-community-title" class="vhub-h2 vhub-h2--light"><?php echo esc_html( (string) vance_get_theme_mod( 'vance_testimonial_heading', 'What Our Community Says' ) ); ?></h2>
			</div>
			<?php if ( ! empty( $vhub_recipe_groups ) ) : ?>
				<div class="vhub-community__actions">
					<a class="vhub-community__btn" href="<?php echo esc_url( $vhub_meal_url ); ?>"><?php esc_html_e( 'Build a gastro supportive meal plan', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
					<a class="vhub-community__more" href="<?php echo esc_url( $vhub_meal_url ); ?>"><?php esc_html_e( 'Browse the recipes', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
				</div>
			<?php endif; ?>
		</div>
		<ul class="vhub-community__cards">
			<?php foreach ( $vhub_quotes as $vhub_quote ) : ?>
				<li class="vhub-community__card vhub-community__card--quote">
					<figure class="vhub-quote">
						<blockquote class="vhub-quote__text"><p><?php echo esc_html( $vhub_quote['quote'] ); ?></p></blockquote>
						<?php if ( '' !== $vhub_quote['name'] || '' !== $vhub_quote['role'] ) : ?>
							<figcaption class="vhub-quote__by">
								<?php if ( '' !== $vhub_quote['name'] ) : ?><strong><?php echo esc_html( $vhub_quote['name'] ); ?></strong><?php endif; ?>
								<?php if ( '' !== $vhub_quote['role'] ) : ?><span><?php echo esc_html( $vhub_quote['role'] ); ?></span><?php endif; ?>
							</figcaption>
						<?php endif; ?>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $vhub_recipe_groups ) ) : ?>
			<div class="vhub-community__recipes">
				<?php foreach ( $vhub_recipe_groups as $vhub_group ) : ?>
					<section class="vhub-community__group">
						<h3 class="vhub-community__h3"><?php echo esc_html( $vhub_group['title'] ); ?></h3>
						<ul class="vhub-recipe-list">
							<?php foreach ( $vhub_group['items'] as $vhub_recipe ) : ?>
								<li><a class="vhub-recipe" href="<?php echo esc_url( $vhub_recipe['url'] ); ?>">
									<?php if ( '' !== $vhub_recipe['img'] ) : ?>
										<img class="vhub-recipe__img" src="<?php echo esc_url( $vhub_recipe['img'] ); ?>" alt="<?php echo esc_attr( $vhub_recipe['alt'] ); ?>" width="56" height="56" loading="lazy" decoding="async">
									<?php endif; ?>
									<span class="vhub-recipe__text">
										<span class="vhub-recipe__name"><?php echo esc_html( $vhub_recipe['title'] ); ?></span>
										<?php if ( '' !== $vhub_recipe['meal'] ) : ?><span class="vhub-recipe__meal"><?php echo esc_html( $vhub_recipe['meal'] ); ?></span><?php endif; ?>
									</span>
								</a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * f2. Promo block: the same Customizer-driven block the VanceHealthHub home
 *     page shows (inc/promo-block.php, "Promo Block" settings). It prints
 *     nothing when its own "show" setting is off, so the output is buffered
 *     and the wrapper renders only when there is a block to wrap. Off for
 *     this layout alone with "Hub: show the promo block".
 * ------------------------------------------------------------------ */
$vhub_promo = '';
if ( function_exists( 'vance_render_promo_home' ) && vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_show_promo', true ) ) ) {
	ob_start();
	vance_render_promo_home();
	$vhub_promo = trim( (string) ob_get_clean() );
}
if ( '' !== $vhub_promo ) : ?>
<div class="vhub-promo">
	<?php echo $vhub_promo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by vance_render_promo_block(). ?>
</div>
<?php endif; ?>


<?php /* ---------------------------------------------------------------
 * h. Library: the newest real articles. No "Goes with" lines: no mapping
 *    from article to tool exists.
 * ------------------------------------------------------------------ */
$vhub_posts = vance_hub_library_posts();
// Every article already on the page. The blocks below skip these, so no post
// appears twice.
$vhub_shown = array_column( $vhub_posts, 'id' );
if ( ! empty( $vhub_posts ) ) :
	$vhub_feat = array_shift( $vhub_posts );
	?>
<section class="vhub-wrap vhub-section vhub-library" aria-labelledby="vhub-library-title">
	<div class="vhub-library__head">
		<h2 id="vhub-library-title" class="vhub-library__title"><?php esc_html_e( 'From the library', 'vance-health-hub' ); ?></h2>
		<a class="vhub-textlink" href="<?php echo esc_url( vance_hub_page_url( 'knowledgebase', '/knowledgebase/' ) ); ?>"><?php esc_html_e( 'Browse the library', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
	</div>
	<div class="vhub-library__grid">
		<a class="vhub-feat" href="<?php echo esc_url( $vhub_feat['url'] ); ?>">
			<?php /* The theme's convention (inc/thumbnail-alt.php): the attachment's own description, or alt="" when it has none. alt="" is right here because the headline inside this link names it. */ ?>
			<?php if ( '' !== $vhub_feat['img'] ) : ?>
				<img class="vhub-feat__img" src="<?php echo esc_url( $vhub_feat['img'] ); ?>" alt="<?php echo esc_attr( $vhub_feat['alt'] ); ?>" loading="lazy" decoding="async">
			<?php endif; ?>
			<span class="vhub-feat__body">
				<span class="vhub-tag vhub-tag--teal"><?php echo esc_html( '' !== $vhub_feat['cat'] ? $vhub_feat['cat'] : __( 'Latest', 'vance-health-hub' ) ); ?></span>
				<h3 class="vhub-feat__title"><?php echo esc_html( $vhub_feat['title'] ); ?></h3>
			</span>
		</a>
		<?php if ( ! empty( $vhub_posts ) ) : ?>
			<ul class="vhub-lib-list">
				<?php foreach ( $vhub_posts as $vhub_p ) : ?>
					<li><a class="vhub-lib-item" href="<?php echo esc_url( $vhub_p['url'] ); ?>">
						<?php if ( '' !== $vhub_p['cat'] ) : ?><span class="vhub-tag vhub-tag--purple"><?php echo esc_html( $vhub_p['cat'] ); ?></span><?php endif; ?>
						<h3 class="vhub-lib-item__title"><?php echo esc_html( $vhub_p['title'] ); ?></h3>
					</a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * h2. Latest by section: one column per content section the site really
 *     has (category term found, posts found), newest first, none repeated
 *     from the library above. Off with "Hub: show Latest by section".
 * ------------------------------------------------------------------ */
$vhub_sections = vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_show_sections', true ) )
	? vance_hub_section_columns( $vhub_shown, 3 )
	: array();
if ( ! empty( $vhub_sections ) ) : ?>
<section class="vhub-wrap vhub-section vhub-secs" aria-labelledby="vhub-secs-title">
	<div class="vhub-library__head">
		<h2 id="vhub-secs-title" class="vhub-library__title"><?php esc_html_e( 'Latest by section', 'vance-health-hub' ); ?></h2>
	</div>
	<div class="vhub-secs__grid">
		<?php foreach ( $vhub_sections as $vhub_sec ) : ?>
			<div class="vhub-sec">
				<h3 class="vhub-sec__title">
					<?php if ( '' !== $vhub_sec['url'] ) : ?>
						<a class="vhub-sec__link" href="<?php echo esc_url( $vhub_sec['url'] ); ?>"><?php echo esc_html( $vhub_sec['name'] ); ?> <span aria-hidden="true">&rarr;</span></a>
					<?php else : ?>
						<?php echo esc_html( $vhub_sec['name'] ); ?>
					<?php endif; ?>
				</h3>
				<ul class="vhub-sec__list">
					<?php foreach ( $vhub_sec['posts'] as $vhub_p ) : ?>
						<li><a class="vhub-sec-card" href="<?php echo esc_url( $vhub_p['url'] ); ?>">
							<?php if ( '' !== $vhub_p['img'] ) : ?>
								<img class="vhub-sec-card__img" src="<?php echo esc_url( $vhub_p['img'] ); ?>" alt="<?php echo esc_attr( $vhub_p['alt'] ); ?>" width="96" height="96" loading="lazy" decoding="async">
							<?php endif; ?>
							<div class="vhub-sec-card__body">
								<h4 class="vhub-sec-card__title"><?php echo esc_html( $vhub_p['title'] ); ?></h4>
								<p class="vhub-sec-card__meta">
									<?php if ( '' !== $vhub_p['date'] ) : ?><time datetime="<?php echo esc_attr( $vhub_p['iso'] ); ?>"><?php echo esc_html( $vhub_p['date'] ); ?></time><?php endif; ?>
									<?php if ( $vhub_p['read'] > 0 ) : ?>
										<?php if ( '' !== $vhub_p['date'] ) : ?><span aria-hidden="true">&middot;</span><?php endif; ?>
										<span><?php
											/* translators: %s: reading time in minutes. */
											echo esc_html( sprintf( _n( '%s min read', '%s min read', $vhub_p['read'], 'vance-health-hub' ), number_format_i18n( $vhub_p['read'] ) ) );
										?></span>
									<?php endif; ?>
								</p>
							</div>
						</a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * h3. More to read: up to six further recent posts not shown above.
 *     Phones show the first four (hub-home.css). Off with
 *     "Hub: show More to read".
 * ------------------------------------------------------------------ */
$vhub_more = vance_hub_sanitize_checkbox( vance_get_theme_mod( 'vance_hub_show_more', true ) )
	? vance_hub_more_posts( $vhub_shown, 6 )
	: array();
if ( ! empty( $vhub_more ) ) : ?>
<section class="vhub-wrap vhub-section vhub-more" aria-labelledby="vhub-more-title">
	<div class="vhub-library__head">
		<h2 id="vhub-more-title" class="vhub-library__title"><?php esc_html_e( 'More to read', 'vance-health-hub' ); ?></h2>
	</div>
	<ul class="vhub-more__grid">
		<?php foreach ( $vhub_more as $vhub_p ) : ?>
			<li><a class="vhub-card" href="<?php echo esc_url( $vhub_p['url'] ); ?>">
				<div class="vhub-card__media">
					<?php if ( '' !== $vhub_p['img'] ) : ?>
						<img class="vhub-card__img" src="<?php echo esc_url( $vhub_p['img'] ); ?>" alt="<?php echo esc_attr( $vhub_p['alt'] ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
				</div>
				<div class="vhub-card__meta">
					<time class="vhub-card__date" datetime="<?php echo esc_attr( $vhub_p['iso'] ); ?>"><?php echo esc_html( $vhub_p['date'] ); ?></time>
					<?php if ( ! empty( $vhub_p['read'] ) ) : ?>
						<span aria-hidden="true">&middot;</span>
						<span class="vhub-card__read"><?php echo esc_html( sprintf( /* translators: %s: minutes to read */ __( '%s min read', 'vance-health-hub' ), number_format_i18n( $vhub_p['read'] ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( $vhub_p['views'] > 0 ) : ?>
						<span aria-hidden="true">&middot;</span>
						<span class="vhub-card__views"><?php echo esc_html( sprintf( /* translators: %s: formatted view count */ _n( '%s read', '%s reads', $vhub_p['views'], 'vance-health-hub' ), number_format_i18n( $vhub_p['views'] ) ) ); ?></span>
					<?php endif; ?>
				</div>
				<div class="vhub-card__body">
					<?php if ( '' !== $vhub_p['cat'] ) : ?><span class="vhub-tag vhub-tag--purple"><?php echo esc_html( $vhub_p['cat'] ); ?></span><?php endif; ?>
					<h3 class="vhub-card__title"><?php echo esc_html( $vhub_p['title'] ); ?></h3>
				</div>
			</a></li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php /* ---------------------------------------------------------------
 * i. Join band. Logged-out: opens the theme's existing join modal
 *    (openGuestModal() in footer.php); the link is the no-JS fallback.
 *    Logged-in: a link to the dashboard.
 * ------------------------------------------------------------------ */ ?>
<?php if ( ! $vhub_logged_in ) : ?>
<section id="vhub-join" class="vhub-join" aria-labelledby="vhub-join-title">
	<div class="vhub-wrap vhub-join__inner">
		<div class="vhub-join__copy">
			<p class="vhub-pill vhub-pill--white"><?php esc_html_e( 'Membership is free', 'vance-health-hub' ); ?></p>
			<h2 id="vhub-join-title" class="vhub-h2 vhub-h2--light"><?php esc_html_e( 'Your place is ready.', 'vance-health-hub' ); ?></h2>
			<p class="vhub-join__text"><?php esc_html_e( 'Members keep their survey answers, tool results and meal plans. They can save articles and notes, and their VANCE-Ai chats stay in their dashboard.', 'vance-health-hub' ); ?></p>
			<?php if ( '' !== $vhub_bar['deadline'] ) : ?>
				<p class="vhub-join__founding"><?php
					/* translators: %s: Founding Member deadline date. */
					echo esc_html( sprintf( __( 'Join by %s and keep the Founding Member mark.', 'vance-health-hub' ), vance_hub_format_date( $vhub_bar['deadline'] ) ) );
				?></p>
			<?php endif; ?>
		</div>
		<div class="vhub-join__card">
			<p class="vhub-join__card-title"><?php esc_html_e( 'Take your place', 'vance-health-hub' ); ?></p>
			<a class="vhub-btn vhub-btn--purple vhub-btn--block" href="<?php echo esc_url( $vhub_signup ); ?>" data-vhub-join><?php esc_html_e( 'Create my free account', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<p class="vhub-join__signin"><?php esc_html_e( 'Already a member?', 'vance-health-hub' ); ?> <a href="<?php echo esc_url( $vhub_signin ); ?>"><?php esc_html_e( 'Sign in', 'vance-health-hub' ); ?></a></p>
		</div>
	</div>
</section>
<?php else : ?>
<section class="vhub-join vhub-join--member" aria-labelledby="vhub-join-title">
	<div class="vhub-wrap vhub-join__inner">
		<div class="vhub-join__copy">
			<h2 id="vhub-join-title" class="vhub-h2 vhub-h2--light"><?php esc_html_e( 'Pick up where you left off.', 'vance-health-hub' ); ?></h2>
			<p class="vhub-join__text"><?php esc_html_e( 'Your saved articles, notes, results and chats are in your dashboard.', 'vance-health-hub' ); ?></p>
		</div>
		<a class="vhub-btn vhub-btn--white" href="<?php echo esc_url( $vhub_dashboard ); ?>"><?php esc_html_e( 'Go to My Hub', 'vance-health-hub' ); ?> <span aria-hidden="true">&rarr;</span></a>
	</div>
</section>
<?php endif; ?>

</main>

<?php
get_footer();
