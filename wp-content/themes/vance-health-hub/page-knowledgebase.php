<?php
/**
 * Template Name: Knowledgebase Lobby
 *
 * Landing page behind the KNOWLEDGEBASE nav item: one large block per
 * destination in that menu's flyout, so the flyout and the lobby cannot drift
 * apart.
 *
 * Where the blocks come from, in order of preference:
 *   1. The children of the primary menu's KNOWLEDGEBASE item — matched first by
 *      the menu item that points at THIS page, then by title, so it resolves
 *      both before and after the menu is repointed.
 *   2. If that menu item can't be found (menu renamed, Mega Menu Pro off,
 *      location unassigned), every top-level category with posts.
 *
 * Each block previews what is actually inside it — the newest few articles for a
 * category, the first few conditions for the GI Health hub. That preview is the
 * point of the page: a grid of eight words tells a visitor nothing about which
 * door to open, and the titles do.
 *
 * Everything configurable lives in Appearance -> Customize -> Page -
 * Knowledgebase: copy, cards per row, accent colour, which collections are
 * hidden or flagged as not launched, and how many preview links to show.
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

if ( ! function_exists( 'vance_kb_lobby_title_key' ) ) :
	/**
	 * Comparison key for the admin-entered title lists (hidden / not launched).
	 *
	 * vance_kb_lobby_slugify() STRIPS '&' rather than reading it as a word, so
	 * "Webinars & Courses" and "Webinars and Courses" fold to different keys and
	 * one of the two spellings would silently never match. Fold the ampersand
	 * first so either spelling works.
	 */
	function vance_kb_lobby_title_key( $text ) {
		return vance_kb_lobby_slugify( str_replace( '&', ' and ', (string) $text ) );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_title_list' ) ) :
	/**
	 * Turn a one-per-line Customizer textarea into a list of comparison keys.
	 *
	 * @param string $mod     Theme mod name.
	 * @param string $default Default textarea contents.
	 * @return string[]
	 */
	function vance_kb_lobby_title_list( $mod, $default = '' ) {
		$out = array();

		foreach ( preg_split( '/\r\n|\r|\n/', (string) vance_get_theme_mod( $mod, $default ) ) as $line ) {
			$key = vance_kb_lobby_title_key( $line );
			if ( '' !== $key ) {
				$out[] = $key;
			}
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_text' ) ) :
	/**
	 * Plain text for a value this template will esc_html() itself.
	 *
	 * Post and term titles come out of WP with entities already in them
	 * ("Crohn&#8217;s Disease"). Passing that straight to esc_html() escapes the
	 * ampersand a second time, so the page renders the literal characters
	 * "&#8217;" instead of an apostrophe. Decode first, then strip tags (that
	 * order, so an encoded "&lt;script&gt;" is decoded and THEN removed rather
	 * than surviving as markup), and let the caller escape once at output.
	 *
	 * Decoding first also stops wp_trim_words() cutting through the middle of an
	 * entity and leaving a fragment on the card.
	 *
	 * @param string $text
	 * @return string
	 */
	function vance_kb_lobby_text( $text ) {
		$decoded = html_entity_decode( (string) $text, ENT_QUOTES, get_bloginfo( 'charset' ) );

		return trim( wp_strip_all_tags( $decoded ) );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_ink' ) ) :
	/**
	 * Darken an accent until white-background text on it clears WCAG AA (4.5:1).
	 *
	 * The site's KB purple (#8e7dbe) is 3.60:1 on white — fine as a 4px rule, and
	 * unreadable as 14px bold link text. So the block's text and its filled icon
	 * tile use this derived colour while the rule keeps the true brand colour.
	 *
	 * 4.5:1 against white means (1.0 + 0.05) / (L + 0.05) >= 4.5, i.e. a relative
	 * luminance of at most 0.1833.
	 *
	 * @param string $hex Accent colour, #rgb or #rrggbb.
	 * @return string A #rrggbb colour safe for text on white.
	 */
	function vance_kb_lobby_ink( $hex ) {
		$rgb = vance_kb_lobby_rgb( $hex );

		if ( null === $rgb ) {
			return '#006666'; // --primary-hover; safe fallback for junk input.
		}

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

if ( ! function_exists( 'vance_kb_lobby_rgb' ) ) :
	/**
	 * Parse #rgb / #rrggbb to an [r, g, b] array, or null if it isn't a colour.
	 *
	 * @param string $hex
	 * @return int[]|null
	 */
	function vance_kb_lobby_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_wash' ) ) :
	/**
	 * A very pale tint of the accent, for the card's hover fill.
	 *
	 * Emitted as rgba() from PHP rather than done in CSS: colour-mix() only
	 * reached Safari in 16.2, and an opacity on the card itself would fade its
	 * text along with its background.
	 *
	 * @param string $hex
	 * @param float  $alpha
	 * @return string
	 */
	function vance_kb_lobby_wash( $hex, $alpha = 0.06 ) {
		$rgb = vance_kb_lobby_rgb( $hex );

		if ( null === $rgb ) {
			return 'rgba(0, 128, 128, ' . $alpha . ')';
		}

		return sprintf( 'rgba(%d, %d, %d, %s)', $rgb[0], $rgb[1], $rgb[2], $alpha );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_meta_label' ) ) :
	/**
	 * The small eyebrow above a block's title.
	 *
	 * @param int|null $count Items behind the block, or null when the block has
	 *                        nothing countable (a plain page). null is NOT the
	 *                        same as 0: an uncountable block simply gets no line,
	 *                        while a countable-but-empty one gets the
	 *                        not-launched-yet label rather than "0 articles".
	 * @return string Empty string for no line at all.
	 */
	function vance_kb_lobby_meta_label( $count ) {
		if ( null === $count ) {
			return '';
		}

		$count = (int) $count;

		if ( $count < 1 ) {
			return (string) vance_get_theme_mod( 'vance_kblobby_soon_label', 'Coming soon' );
		}

		return sprintf(
			/* translators: %s: number of articles in a collection. */
			_n( '%s article', '%s articles', $count, 'vance-health-hub' ),
			number_format_i18n( $count )
		);
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_page_count' ) ) :
	/**
	 * How many items a linked PAGE puts behind a block, or null if that page
	 * isn't a listing we know how to count.
	 *
	 * Only the GI Health hub is countable today: it renders one card per entry in
	 * vance_gi_condition_cards(), so the number tracks that registry instead of
	 * being typed in here and going stale the next time a condition is added.
	 * Matched on the page's assigned template rather than its slug, because the
	 * page can be renamed.
	 *
	 * @param int $page_id
	 * @return int|null
	 */
	function vance_kb_lobby_page_count( $page_id ) {
		$template = get_page_template_slug( $page_id );

		if ( 'page-gi-health.php' === $template && function_exists( 'vance_gi_condition_cards' ) ) {
			return count( vance_gi_condition_cards() );
		}

		return null;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_peek' ) ) :
	/**
	 * What is actually inside a block: up to $limit destinations to preview.
	 *
	 * A category previews its newest posts; the GI Health hub previews the first
	 * few conditions it lists. Anything else previews nothing rather than
	 * inventing a list.
	 *
	 * @param int $term_id Category term id, or 0.
	 * @param int $page_id Linked page id, or 0.
	 * @param int $limit   Maximum entries; 0 disables the preview entirely.
	 * @return array<int, array{title:string,url:string}>
	 */
	function vance_kb_lobby_peek( $term_id, $page_id, $limit ) {
		$limit = (int) $limit;
		if ( $limit < 1 ) {
			return array();
		}

		$out = array();

		if ( $term_id > 0 ) {
			$posts = get_posts( array(
				'category'         => $term_id,
				'numberposts'      => $limit,
				'post_status'      => 'publish',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			) );

			foreach ( $posts as $post_obj ) {
				$out[] = array(
					'title' => vance_kb_lobby_text( get_the_title( $post_obj ) ),
					'url'   => get_permalink( $post_obj ),
				);
			}

			return $out;
		}

		if ( $page_id > 0
			&& 'page-gi-health.php' === get_page_template_slug( $page_id )
			&& function_exists( 'vance_gi_condition_cards' )
			&& function_exists( 'vance_gi_page_url' ) ) {

			foreach ( array_slice( vance_gi_condition_cards(), 0, $limit ) as $card ) {
				$out[] = array(
					'title' => vance_kb_lobby_text( $card['title'] ),
					'url'   => vance_gi_page_url( $card['slug'] ),
				);
			}
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_items' ) ) :
	/**
	 * Resolve the lobby's blocks.
	 *
	 * @return array List of array( title, url, desc, meta, soon, accent, ink,
	 *               wash, peek, term_id ).
	 */
	function vance_kb_lobby_items() {
		/*
		 * Accent colour. "single" paints every block the same brand colour, which
		 * is the default: with one colour per collection, the palette became the
		 * loudest thing on the page and implied a category system that does not
		 * exist. "match" restores the old behaviour, where each block borrows the
		 * accent its homepage Knowledge Base section already uses.
		 */
		$mode   = vance_get_theme_mod( 'vance_kblobby_accent_mode', 'single' );
		$single = vance_get_theme_mod( 'vance_kblobby_accent_single', '#8e7dbe' );
		if ( ! vance_kb_lobby_rgb( $single ) ) {
			$single = '#8e7dbe';
		}

		// Only consulted in "match" mode; kept as the fallback for a category
		// with no accent of its own.
		$palette = array( '#008080', '#0EA5E9', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444' );

		$peek_limit = max( 0, min( 5, absint( vance_get_theme_mod( 'vance_kblobby_peek_count', 3 ) ) ) );
		$hidden     = vance_kb_lobby_title_list( 'vance_kblobby_hidden_titles', 'Webinars and Courses' );
		$soon_list  = vance_kb_lobby_title_list( 'vance_kblobby_soon_titles', '' );

		$blocks = array();
		$raw    = array();

		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations['primary-menu'] ) ? (int) $locations['primary-menu'] : 0;

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
							$raw[] = $item;
						}
					}
				}
			}
		}

		if ( ! empty( $raw ) ) {
			$i = 0;

			foreach ( $raw as $item ) {
				// Hidden collections leave the lobby but stay in the menu — the
				// nav still needs to reach a page the lobby isn't promoting.
				if ( in_array( vance_kb_lobby_title_key( $item->title ), $hidden, true ) ) {
					continue;
				}

				$desc    = vance_kb_lobby_text( $item->description );
				$count   = null; // null = nothing countable behind this block.
				$term_id = 0;
				$page_id = 0;
				$accent  = ( 'single' === $mode ) ? $single : $palette[ $i % count( $palette ) ];

				if ( 'taxonomy' === $item->type && 'category' === $item->object ) {
					$term = get_term( (int) $item->object_id, 'category' );
					if ( $term instanceof WP_Term ) {
						$term_id = (int) $term->term_id;
						if ( '' === $desc ) {
							$desc = vance_kb_lobby_text( $term->description );
						}
						$count = (int) $term->count;
						if ( 'match' === $mode ) {
							$accent = vance_get_theme_mod( "vance_kb_accent_{$term_id}", $accent );
						}
					}
				} elseif ( 'post_type' === $item->type ) {
					$linked = get_post( (int) $item->object_id );
					if ( $linked instanceof WP_Post ) {
						$page_id = (int) $linked->ID;
						if ( '' === $desc ) {
							$desc = vance_kb_lobby_text( $linked->post_excerpt );
						}
						$count = vance_kb_lobby_page_count( $page_id );
					}
				}

				// An explicit "not launched" flag wins over any count: a page can
				// be a real listing and still not be ready to promote.
				if ( in_array( vance_kb_lobby_title_key( $item->title ), $soon_list, true ) ) {
					$count = 0;
				}

				if ( ! vance_kb_lobby_rgb( $accent ) ) {
					$accent = $single;
				}

				$blocks[] = array(
					'title'   => vance_kb_lobby_text( $item->title ),
					'url'     => $item->url,
					'desc'    => $desc,
					'meta'    => vance_kb_lobby_meta_label( $count ),
					'soon'    => ( null !== $count && (int) $count < 1 ),
					'accent'  => $accent,
					'ink'     => vance_kb_lobby_ink( $accent ),
					'wash'    => vance_kb_lobby_wash( $accent ),
					'peek'    => vance_kb_lobby_peek( $term_id, $page_id, $peek_limit ),
					'term_id' => $term_id,
				);

				$i++;
			}

			return $blocks;
		}

		// Fallback — every top-level category that has posts.
		$uncat   = get_category_by_slug( 'uncategorized' );
		$exclude = $uncat ? array( $uncat->term_id ) : array();

		$cats = get_categories( array(
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true,
			'exclude'    => $exclude,
		) );

		$i = 0;

		foreach ( $cats as $cat ) {
			if ( in_array( vance_kb_lobby_title_key( $cat->name ), $hidden, true ) ) {
				continue;
			}

			$accent = ( 'single' === $mode )
				? $single
				: vance_get_theme_mod( "vance_kb_accent_{$cat->term_id}", $palette[ $i % count( $palette ) ] );

			if ( ! vance_kb_lobby_rgb( $accent ) ) {
				$accent = $single;
			}

			$blocks[] = array(
				'title'   => vance_kb_lobby_text( $cat->name ),
				'url'     => get_category_link( $cat->term_id ),
				'desc'    => vance_kb_lobby_text( $cat->description ),
				'meta'    => vance_kb_lobby_meta_label( (int) $cat->count ),
				'soon'    => ( (int) $cat->count < 1 ),
				'accent'  => $accent,
				'ink'     => vance_kb_lobby_ink( $accent ),
				'wash'    => vance_kb_lobby_wash( $accent ),
				'peek'    => vance_kb_lobby_peek( (int) $cat->term_id, 0, $peek_limit ),
				'term_id' => (int) $cat->term_id,
			);

			$i++;
		}

		return $blocks;
	}
endif;

get_header();

$kb_blocks = vance_kb_lobby_items();

/*
 * Block icons. Cycled by position rather than guessed from the title: category
 * names are editable and a wrong-but-confident icon reads worse than a neutral
 * one. With every block now sharing one accent, these are what tell the cards
 * apart at a glance, so they matter more than they did.
 */
$kb_icons = array(
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
	'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 6l9-3 9 3-9 3-9-3zm0 6l9 3 9-3M3 18l9 3 9-3"/>',
);

$kb_per_row = max( 1, min( 3, absint( vance_get_theme_mod( 'vance_kblobby_per_row', 2 ) ) ) );

$kb_hero_bg = vance_get_theme_mod( 'vance_kblobby_hero_bg' );
if ( ! $kb_hero_bg ) {
	$kb_hero_bg = get_template_directory_uri() . '/assets/img/research_hero.png';
}
$kb_hero_overlay        = max( 0, min( 100, absint( vance_get_theme_mod( 'vance_kblobby_hero_overlay', 72 ) ) ) ) / 100;
$kb_hero_overlay_bottom = min( 1, $kb_hero_overlay + 0.12 );
$kb_hero_tag            = vance_get_theme_mod( 'vance_kblobby_hero_tag', 'Knowledgebase' );
$kb_hero_title          = vance_get_theme_mod( 'vance_kblobby_hero_title', 'The whole <span class="highlight">evidence library</span>, one door' );
$kb_hero_desc           = vance_get_theme_mod( 'vance_kblobby_hero_desc', 'Clinical reviews, gastro living guides and health news - every collection in the Vance Medical Hub, grouped so you can go straight to the one you need.' );

$kb_intro_eyebrow = vance_get_theme_mod( 'vance_kblobby_intro_eyebrow', 'Start Here' );
$kb_intro_title   = vance_get_theme_mod( 'vance_kblobby_intro_title', 'Pick a collection' );
$kb_intro_desc    = vance_get_theme_mod( 'vance_kblobby_intro_desc', 'Every collection below is curated and clinically reviewed. Each card shows what is newest inside it, so you can jump straight to an article or open the whole shelf.' );

$kb_peek_label = vance_get_theme_mod( 'vance_kblobby_peek_label', 'Latest inside' );
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

	<!-- BLOCKS -->
	<section class="kb-lobby-blocks">
		<div class="container">
			<?php if ( empty( $kb_blocks ) ) : ?>

				<p class="kb-lobby-empty">
					<?php esc_html_e( 'No knowledgebase collections are published yet. Add categories under the KNOWLEDGEBASE menu item and they will appear here.', 'vance-health-hub' ); ?>
				</p>

			<?php else : ?>

				<div class="kb-lobby-grid kb-lobby-grid--<?php echo (int) $kb_per_row; ?>">
					<?php foreach ( $kb_blocks as $i => $block ) : ?>
						<?php
						/*
						 * <article> with a stretched title link, not one big <a>:
						 * the preview entries below are real links, and an <a>
						 * cannot legally contain another. Same pattern the news
						 * cards use for their taxonomy chips (main.css
						 * .card-stretched-link).
						 */
						?>
						<article class="kb-block<?php echo ! empty( $block['soon'] ) ? ' kb-block--soon' : ''; ?>"
							style="--kb-accent: <?php echo esc_attr( $block['accent'] ); ?>; --kb-accent-ink: <?php echo esc_attr( $block['ink'] ); ?>; --kb-accent-wash: <?php echo esc_attr( $block['wash'] ); ?>;">

							<div class="kb-block__head">
								<span class="kb-block__icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><?php echo $kb_icons[ $i % count( $kb_icons ) ]; // phpcs:ignore WordPress.Security.EscapeOutput -- hardcoded SVG paths, defined above. ?></svg>
								</span>

								<div class="kb-block__headings">
									<?php if ( $block['meta'] ) : ?>
										<span class="kb-block__meta"><?php echo esc_html( $block['meta'] ); ?></span>
									<?php endif; ?>
									<h3 class="kb-block__title">
										<a class="kb-block__link" href="<?php echo esc_url( $block['url'] ); ?>"><?php echo esc_html( $block['title'] ); ?></a>
									</h3>
								</div>
							</div>

							<?php if ( $block['desc'] ) : ?>
								<p class="kb-block__desc"><?php echo esc_html( wp_trim_words( $block['desc'], 24, '...' ) ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $block['peek'] ) ) : ?>
								<div class="kb-block__peek">
									<span class="kb-block__peek-label"><?php echo esc_html( $kb_peek_label ); ?></span>
									<ul class="kb-block__peek-list">
										<?php foreach ( $block['peek'] as $peek ) : ?>
											<li>
												<a href="<?php echo esc_url( $peek['url'] ); ?>"><?php echo esc_html( wp_trim_words( $peek['title'], 14, '...' ) ); ?></a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php // Decorative: the title above is the real link, so this must not be announced as a second one. ?>
							<span class="kb-block__cta" aria-hidden="true">
								<?php esc_html_e( 'Browse all', 'vance-health-hub' ); ?>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
							</span>
						</article>
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
