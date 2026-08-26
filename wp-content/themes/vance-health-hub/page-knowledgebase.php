<?php
/**
 * Template Name: Knowledgebase Lobby
 *
 * Landing page behind the KNOWLEDGEBASE nav item: one large block button per
 * destination that currently lives in that menu's flyout, so the flyout and the
 * lobby can never drift apart.
 *
 * To activate: create a Page titled "Knowledgebase", slug `knowledgebase`,
 * choose "Knowledgebase Lobby" as the template, then repoint the KNOWLEDGEBASE
 * menu item at it (it is currently a custom link to the site root).
 *
 * Where the blocks come from, in order of preference:
 *   1. The children of the primary menu's KNOWLEDGEBASE item — matched first by
 *      the menu item that points at THIS page, then by title, so it resolves
 *      both before and after the menu is repointed.
 *   2. If that menu item can't be found (menu renamed, Mega Menu Pro off,
 *      location unassigned), every top-level category with posts.
 *
 * Copy: Appearance -> Customize -> Page - Knowledgebase.
 * Per-category accent colours are shared with the homepage Knowledge Base
 * sections (`vance_kb_accent_{term_id}`), so a block matches its section.
 */

if ( ! function_exists( 'vance_kb_lobby_slugify' ) ) :
	/**
	 * Fold a label to a comparison key: "Knowledge Base", "KNOWLEDGEBASE" and
	 * "Knowledge-base" must all match the same menu item.
	 */
	function vance_kb_lobby_slugify( $text ) {
		return preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $text ) );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_ink' ) ) :
	/**
	 * Darken an accent until white-background text on it clears WCAG AA (4.5:1).
	 *
	 * The shared accent palette carries amber (#F59E0B, 2.1:1 on white) and sky
	 * (#0EA5E9, 2.8:1). Those are fine as a 5px rule but unreadable as 14px
	 * bold link text, so the block's text and its filled icon tile use this
	 * derived colour instead of the raw accent. Teal (#008080, 4.8:1) and the
	 * other dark stops come back unchanged.
	 *
	 * 4.5:1 against white means (1.0 + 0.05) / (L + 0.05) >= 4.5, i.e. a
	 * relative luminance of at most 0.1833.
	 *
	 * @param string $hex Accent colour, #rgb or #rrggbb.
	 * @return string A #rrggbb colour safe for text on white.
	 */
	function vance_kb_lobby_ink( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '#006666'; // --primary-hover; safe fallback for junk input.
		}

		$rgb = array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);

		$luminance = static function ( $channels ) {
			$linear = array();
			foreach ( $channels as $c ) {
				$c        = $c / 255;
				$linear[] = ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
			}
			return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
		};

		// Multiplicative darkening keeps the hue; 40 steps of 0.94 reaches black
		// from any starting colour, so the loop always terminates.
		$steps = 0;
		while ( $luminance( $rgb ) > 0.1833 && $steps < 40 ) {
			$rgb = array_map(
				static function ( $c ) {
					return (int) floor( $c * 0.94 );
				},
				$rgb
			);
			$steps++;
		}

		return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_items' ) ) :
	/**
	 * Resolve the lobby's blocks.
	 *
	 * @return array List of array( title, url, desc, meta, accent, term_id ).
	 */
	function vance_kb_lobby_items() {
		$palette = array( '#008080', '#0EA5E9', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444' );
		$blocks  = array();

		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations['primary-menu'] ) ? (int) $locations['primary-menu'] : 0;
		$children  = array();

		if ( $menu_id ) {
			$menu_items = wp_get_nav_menu_items( $menu_id );

			if ( ! empty( $menu_items ) ) {
				$this_page = (int) get_queried_object_id();
				$needle    = vance_kb_lobby_slugify(
					vance_get_theme_mod( 'vance_kblobby_menu_label', 'Knowledgebase' )
				);
				$parent_id = 0;

				foreach ( $menu_items as $item ) {
					// Strongest signal: the menu item IS this page.
					if ( 'post_type' === $item->type && (int) $item->object_id === $this_page ) {
						$parent_id = (int) $item->ID;
						break;
					}
					// Weaker: same label. Keep the first hit only.
					if ( ! $parent_id && vance_kb_lobby_slugify( $item->title ) === $needle ) {
						$parent_id = (int) $item->ID;
					}
				}

				if ( $parent_id ) {
					foreach ( $menu_items as $item ) {
						if ( (int) $item->menu_item_parent === $parent_id ) {
							$children[] = $item;
						}
					}
				}
			}
		}

		if ( ! empty( $children ) ) {
			foreach ( $children as $i => $item ) {
				$desc    = trim( (string) $item->description );
				$meta    = '';
				$term_id = 0;
				$accent  = $palette[ $i % count( $palette ) ];

				if ( 'taxonomy' === $item->type && 'category' === $item->object ) {
					$term = get_term( (int) $item->object_id, 'category' );
					if ( $term instanceof WP_Term ) {
						$term_id = (int) $term->term_id;
						if ( '' === $desc ) {
							$desc = trim( wp_strip_all_tags( $term->description ) );
						}
						$meta = sprintf(
							/* translators: %s: number of articles in a category. */
							_n( '%s article', '%s articles', (int) $term->count, 'vance-health-hub' ),
							number_format_i18n( (int) $term->count )
						);
						// Share the homepage section's accent so a block and the
						// section it leads to read as the same thing.
						$accent = vance_get_theme_mod( "vance_kb_accent_{$term_id}", $accent );
					}
				} elseif ( 'post_type' === $item->type && '' === $desc ) {
					$linked = get_post( (int) $item->object_id );
					if ( $linked instanceof WP_Post ) {
						$desc = trim( wp_strip_all_tags( $linked->post_excerpt ) );
					}
				}

				$blocks[] = array(
					'title'   => $item->title,
					'url'     => $item->url,
					'desc'    => $desc,
					'meta'    => $meta,
					'accent'  => $accent ? $accent : $palette[ $i % count( $palette ) ],
					'term_id' => $term_id,
				);
			}

			return $blocks;
		}

		// Fallback - every top-level category that has posts.
		$uncat   = get_category_by_slug( 'uncategorized' );
		$exclude = $uncat ? array( $uncat->term_id ) : array();

		$cats = get_categories( array(
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true,
			'exclude'    => $exclude,
		) );

		foreach ( $cats as $i => $cat ) {
			$accent = vance_get_theme_mod( "vance_kb_accent_{$cat->term_id}", $palette[ $i % count( $palette ) ] );

			$blocks[] = array(
				'title'   => $cat->name,
				'url'     => get_category_link( $cat->term_id ),
				'desc'    => trim( wp_strip_all_tags( $cat->description ) ),
				'meta'    => sprintf(
					/* translators: %s: number of articles in a category. */
					_n( '%s article', '%s articles', (int) $cat->count, 'vance-health-hub' ),
					number_format_i18n( (int) $cat->count )
				),
				'accent'  => $accent ? $accent : $palette[ $i % count( $palette ) ],
				'term_id' => (int) $cat->term_id,
			);
		}

		return $blocks;
	}
endif;

get_header();

$kb_blocks = vance_kb_lobby_items();

/*
 * Block icons. Cycled by position rather than guessed from the title: category
 * names are editable and a wrong-but-confident icon reads worse than a neutral
 * one. Stroke-only 24x24 paths, matching the tool cards on /free-health-tools/.
 */
$kb_icons = array(
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 6l9-3 9 3-9 3-9-3zm0 6l9 3 9-3M3 18l9 3 9-3"/>',
);

$kb_hero_bg = vance_get_theme_mod( 'vance_kblobby_hero_bg' );
if ( ! $kb_hero_bg ) {
	$kb_hero_bg = get_template_directory_uri() . '/assets/img/research_hero.png';
}
$kb_hero_overlay        = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_kblobby_hero_overlay', 72 ) ) ) ) / 100;
$kb_hero_overlay_bottom = min( 1, $kb_hero_overlay + 0.12 );
$kb_hero_tag            = vance_get_theme_mod( 'vance_kblobby_hero_tag', 'Knowledgebase' );
$kb_hero_title          = vance_get_theme_mod( 'vance_kblobby_hero_title', 'The whole <span class="highlight">evidence library</span>, one door' );
$kb_hero_desc           = vance_get_theme_mod( 'vance_kblobby_hero_desc', 'Clinical reviews, gastro living guides, health news and courses - every collection in the Vance Medical Hub, grouped so you can go straight to the one you need.' );

$kb_intro_eyebrow = vance_get_theme_mod( 'vance_kblobby_intro_eyebrow', 'Start Here' );
$kb_intro_title   = vance_get_theme_mod( 'vance_kblobby_intro_title', 'Pick a collection' );
$kb_intro_desc    = vance_get_theme_mod( 'vance_kblobby_intro_desc', 'Every collection below is curated and clinically reviewed. Not sure where to begin? Search across all of them at once.' );
?>

<main id="main-content" class="kb-lobby">

	<!-- HERO -->
	<section class="hero kb-lobby-hero" style="padding: 72px 0 96px; min-height: 320px; display: flex; align-items: center; background: linear-gradient(rgba(10,25,41,<?php echo esc_attr( $kb_hero_overlay ); ?>), rgba(10,25,41,<?php echo esc_attr( $kb_hero_overlay_bottom ); ?>)), url('<?php echo esc_url( $kb_hero_bg ); ?>') no-repeat center center; background-size: cover;">
		<div class="container">
			<div class="hero-content" style="max-width: 760px;">
				<?php if ( $kb_hero_tag ) : ?>
					<span class="tag-label"><?php echo esc_html( $kb_hero_tag ); ?></span>
				<?php endif; ?>
				<h1><?php echo wp_kses_post( $kb_hero_title ); ?></h1>
				<?php if ( $kb_hero_desc ) : ?>
					<p><?php echo esc_html( $kb_hero_desc ); ?></p>
				<?php endif; ?>

				<form role="search" method="get" class="vance-search-again" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input
						type="search"
						name="s"
						class="vance-search-again__field"
						placeholder="<?php esc_attr_e( 'Search the whole knowledgebase...', 'vance-health-hub' ); ?>"
						aria-label="<?php esc_attr_e( 'Search the knowledgebase', 'vance-health-hub' ); ?>">
					<button type="submit" class="vance-search-again__submit"><?php esc_html_e( 'Search', 'vance-health-hub' ); ?></button>
				</form>
			</div>
		</div>
	</section>

	<!-- INTRO -->
	<section class="kb-lobby-intro">
		<div class="container">
			<div class="kb-lobby-intro__inner">
				<?php if ( $kb_intro_eyebrow ) : ?>
					<span class="kb-lobby-intro__eyebrow"><?php echo esc_html( $kb_intro_eyebrow ); ?></span>
				<?php endif; ?>
				<h2 class="kb-lobby-intro__title"><?php echo esc_html( $kb_intro_title ); ?></h2>
				<?php if ( $kb_intro_desc ) : ?>
					<p class="kb-lobby-intro__desc"><?php echo esc_html( $kb_intro_desc ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- BLOCK BUTTONS -->
	<section class="kb-lobby-blocks">
		<div class="container">
			<?php if ( empty( $kb_blocks ) ) : ?>

				<p class="kb-lobby-empty">
					<?php esc_html_e( 'No knowledgebase collections are published yet. Add categories under the KNOWLEDGEBASE menu item and they will appear here.', 'vance-health-hub' ); ?>
				</p>

			<?php else : ?>

				<div class="kb-lobby-grid">
					<?php foreach ( $kb_blocks as $i => $block ) : ?>
						<a class="kb-block" href="<?php echo esc_url( $block['url'] ); ?>" style="--kb-accent: <?php echo esc_attr( $block['accent'] ); ?>; --kb-accent-ink: <?php echo esc_attr( vance_kb_lobby_ink( $block['accent'] ) ); ?>;">
							<span class="kb-block__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><?php echo $kb_icons[ $i % count( $kb_icons ) ]; // phpcs:ignore WordPress.Security.EscapeOutput -- hardcoded SVG paths, defined above. ?></svg>
							</span>

							<span class="kb-block__body">
								<span class="kb-block__title"><?php echo esc_html( $block['title'] ); ?></span>
								<?php if ( $block['desc'] ) : ?>
									<span class="kb-block__desc"><?php echo esc_html( wp_trim_words( $block['desc'], 26, '...' ) ); ?></span>
								<?php endif; ?>
							</span>

							<span class="kb-block__foot">
								<?php if ( $block['meta'] ) : ?>
									<span class="kb-block__meta"><?php echo esc_html( $block['meta'] ); ?></span>
								<?php endif; ?>
								<span class="kb-block__cta">
									<?php esc_html_e( 'Browse', 'vance-health-hub' ); ?>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
								</span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>

			<?php endif; ?>
		</div>
	</section>

	<?php
	// Any editorial copy typed into the Page itself renders under the blocks, so
	// the lobby stays editable without touching this template.
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			if ( '' !== trim( get_the_content() ) ) :
				?>
				<section class="kb-lobby-content">
					<div class="container">
						<div class="kb-lobby-content__inner"><?php the_content(); ?></div>
					</div>
				</section>
				<?php
			endif;
		endwhile;
	endif;
	?>

</main>

<?php get_footer(); ?>
