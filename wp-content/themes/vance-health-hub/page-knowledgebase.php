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
	 * The site teal (#008080) is 4.77:1 on white, so it passes as it stands and
	 * this returns it unchanged. The guard is for what an admin can type into the
	 * accent field: the KB purple this page shipped with (#8e7dbe) is 3.60:1 -
	 * fine as a 4px rule, unreadable as 14px bold link text. So the block's text
	 * and its filled icon tile use the derived colour while the rule keeps
	 * whatever was chosen.
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
	function vance_kb_lobby_meta_label( $count, $unit = 'article' ) {
		if ( null === $count ) {
			return '';
		}

		$count = (int) $count;

		if ( $count < 1 ) {
			return (string) vance_get_theme_mod( 'vance_kblobby_soon_label', 'Coming soon' );
		}

		/*
		 * Spelled out per unit rather than interpolating a noun: _n() needs
		 * literal strings to be extractable for translation, and "7 articles"
		 * on the conditions hub - which is what this said before the unit
		 * existed - was simply untrue.
		 */
		switch ( $unit ) {
			case 'condition':
				return sprintf(
					/* translators: %s: number of conditions in a collection. */
					_n( '%s condition', '%s conditions', $count, 'vance-health-hub' ),
					number_format_i18n( $count )
				);

			case 'recipe':
				return sprintf(
					/* translators: %s: number of recipes in a collection. */
					_n( '%s recipe', '%s recipes', $count, 'vance-health-hub' ),
					number_format_i18n( $count )
				);
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
			return array(
				'count' => count( vance_gi_condition_cards() ),
				'unit'  => 'condition',
			);
		}

		// The meal planner lists the vance_recipe post type, so its number
		// tracks what is published rather than a figure typed in here.
		if ( 'page-gastro-recipies.php' === $template && post_type_exists( 'vance_recipe' ) ) {
			$counts = wp_count_posts( 'vance_recipe' );

			return array(
				'count' => isset( $counts->publish ) ? (int) $counts->publish : 0,
				'unit'  => 'recipe',
			);
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

		if ( $page_id < 1 ) {
			return $out;
		}

		$template = get_page_template_slug( $page_id );

		if ( 'page-gi-health.php' === $template
			&& function_exists( 'vance_gi_condition_cards' )
			&& function_exists( 'vance_gi_page_url' ) ) {

			foreach ( array_slice( vance_gi_condition_cards(), 0, $limit ) as $card ) {
				$out[] = array(
					'title' => vance_kb_lobby_text( $card['title'] ),
					'url'   => vance_gi_page_url( $card['slug'] ),
				);
			}

			return $out;
		}

		if ( 'page-gastro-recipies.php' === $template && post_type_exists( 'vance_recipe' ) ) {
			$recipes = get_posts( array(
				'post_type'        => 'vance_recipe',
				'numberposts'      => $limit,
				'post_status'      => 'publish',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			) );

			foreach ( $recipes as $recipe ) {
				$out[] = array(
					'title' => vance_kb_lobby_text( get_the_title( $recipe ) ),
					'url'   => get_permalink( $recipe ),
				);
			}
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_path' ) ) :
	/**
	 * Normalised path for comparing two menu destinations.
	 *
	 * The lobby has to answer two questions about a URL: "is this the page we
	 * are already on?" and "have we already shown this destination?". Both are
	 * about the destination, not the string, so host, scheme, query, fragment
	 * and the trailing slash are all dropped before comparing.
	 *
	 * @param string $url
	 * @return string Path with no leading/trailing slash, or '' for '#'-style
	 *                placeholder links.
	 */
	function vance_kb_lobby_path( $url ) {
		$path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );

		return trim( $path, '/' );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_children' ) ) :
	/**
	 * The menu items whose parent is $parent_id, in menu order.
	 *
	 * @param WP_Post[] $items     Full menu, as returned by wp_get_nav_menu_items().
	 * @param int       $parent_id
	 * @return WP_Post[]
	 */
	function vance_kb_lobby_children( $items, $parent_id ) {
		$out = array();

		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent === (int) $parent_id ) {
				$out[] = $item;
			}
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_is_column_heading' ) ) :
	/**
	 * Is this menu item a mega-panel column heading rather than a destination?
	 *
	 * The KNOWLEDGEBASE panel is a Max Mega Menu grid, and in that plugin a
	 * SECOND-level item renders as a column heading while its THIRD-level
	 * children render as the links beneath it. So the lobby's direct children
	 * are "Browse the library" and "By content type" - a heading pointing back
	 * at this very page, and a heading with its link disabled. Reading only the
	 * direct children therefore produced two cards that either went nowhere or
	 * went to the page the visitor was already on. That is what this detects, so
	 * the lobby can descend a level and show the real collections.
	 *
	 * A heading is recognised by where it points, never by its depth: a panel
	 * built as a plain flyout has real destinations at level two and must keep
	 * working unchanged.
	 *
	 * @param WP_Post $item      Menu item.
	 * @param int     $this_page ID of the page being rendered.
	 * @param string  $this_path Normalised path of the page being rendered.
	 * @return bool
	 */
	function vance_kb_lobby_is_column_heading( $item, $this_page, $this_path ) {
		// "Disable link" in Max Mega Menu, and WP's own custom-link placeholder.
		$url = trim( (string) $item->url );
		if ( '' === $url || '#' === $url ) {
			return true;
		}

		// Points at this page - by object, and by path for a custom link typed
		// out by hand.
		if ( 'post_type' === $item->type && (int) $item->object_id === (int) $this_page ) {
			return true;
		}

		return ( '' !== $this_path && vance_kb_lobby_path( $url ) === $this_path );
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_svg' ) ) :
	/**
	 * One inline icon from the lobby's set, by key.
	 *
	 * Outline paths on a 24x24 box, stroke-width 1.7 - the same drawing as the
	 * block icons further down, so a tool tile and a collection card do not look
	 * like they came from two different libraries. An unknown key returns '' and
	 * the caller renders no icon rather than a wrong one.
	 *
	 * @param string $key
	 * @return string
	 */
	function vance_kb_lobby_svg( $key ) {
		$paths = array(
			'calculator' => '<rect x="4" y="3" width="16" height="18" rx="2" stroke-width="1.7"/><path stroke-linecap="round" stroke-width="1.7" d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
			'clipboard'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
			'leaf'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 20c0-8 5-13 16-13 0 9-5 13-11 13a5 5 0 01-5-5zM4 20c2-4 5-6 9-7.5"/>',
			'sparkles'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9.5 3.5l1.4 3.6 3.6 1.4-3.6 1.4-1.4 3.6-1.4-3.6L4.5 8.5l3.6-1.4 1.4-3.6zM17 13l.9 2.3 2.3.9-2.3.9-.9 2.3-.9-2.3-2.3-.9 2.3-.9.9-2.3z"/>',
			'grid'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 5h6v6H4V5zm10 0h6v6h-6V5zM4 13h6v6H4v-6zm10 0h6v6h-6v-6z"/>',
		);

		return isset( $paths[ $key ] ) ? $paths[ $key ] : '';
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_topics' ) ) :
	/**
	 * The topic tiles: every CHILD category that actually carries posts.
	 *
	 * The collection cards open a whole shelf, and 85 articles in Gastro Living
	 * is not a browsable unit. The child terms are the real subjects ("Tests &
	 * Treatments", "Food & Nutrition") and, until now, nothing on this page
	 * reached them - they existed only inside an archive a visitor had to open
	 * first.
	 *
	 * hide_empty is what keeps this honest: a topic tile leading to an empty
	 * archive is worse than no tile, and five of the site's child terms are
	 * empty today.
	 *
	 * @param int $limit Maximum tiles; 0 for no limit.
	 * @return array<int, array{title:string,url:string,count:int,parent:string}>
	 */
	function vance_kb_lobby_topics( $limit = 0 ) {
		$terms = get_categories( array(
			'orderby'      => 'count',
			'order'        => 'DESC',
			'hide_empty'   => true,
			'hierarchical' => false,
		) );

		$out = array();

		foreach ( $terms as $term ) {
			if ( ! $term->parent ) {
				continue; // Top-level terms are the collection cards above.
			}

			$parent = get_term( (int) $term->parent, 'category' );

			$out[] = array(
				'title'  => vance_kb_lobby_text( $term->name ),
				'url'    => get_category_link( (int) $term->term_id ),
				'count'  => (int) $term->count,
				'parent' => ( $parent instanceof WP_Term ) ? vance_kb_lobby_text( $parent->name ) : '',
			);
		}

		if ( $limit > 0 ) {
			$out = array_slice( $out, 0, (int) $limit );
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_tools' ) ) :
	/**
	 * The tool tiles.
	 *
	 * Sourced from the primary menu's "Free Health Tools" item for the same
	 * reason the collection cards are sourced from KNOWLEDGEBASE: the nav is
	 * where these are curated, and a second hand-typed list would drift. The
	 * built-in list below is the fallback for a renamed or missing menu item,
	 * not the primary source.
	 *
	 * Menu items carry no description on this site, so the copy comes from a
	 * path-keyed map. Keyed by path rather than by title because the titles are
	 * admin-editable and the URLs are the contract.
	 *
	 * @param string $menu_label Menu item whose children are the tools.
	 * @param bool   $with_ai    Append the Ask AI tile.
	 * @return array<int, array{title:string,url:string,desc:string,icon:string}>
	 */
	function vance_kb_lobby_tools( $menu_label = 'Free Health Tools', $with_ai = true ) {
		// Copy + icon for the tools this site actually ships, keyed by path.
		$known = array(
			'malnutrition-calculator' => array(
				'icon' => 'calculator',
				'desc' => 'Screen for malnutrition risk in under two minutes, then save the score to your dashboard to track it over time.',
			),
			'gastro-health-survey'    => array(
				'icon' => 'clipboard',
				'desc' => 'A short self-assessment covering symptom patterns, dietary triggers and lifestyle, with a summary you can take to your clinician.',
			),
			'gastro-meal-planner'     => array(
				'icon' => 'leaf',
				'desc' => 'Gut-friendly recipes you can filter by condition and build into a weekly plan.',
			),
			'ask-ai'                  => array(
				'icon' => 'sparkles',
				'desc' => "Ask a question in plain English and get an evidence-based answer drawn from the Hub's own clinical library, any time of day.",
			),
		);

		$fallback = array(
			array( 'title' => 'Malnutrition Calculator', 'path' => 'malnutrition-calculator' ),
			array( 'title' => 'Gastro Health Survey',    'path' => 'gastro-health-survey' ),
			array( 'title' => 'Recipes &amp; Meal Planner',  'path' => 'gastro-meal-planner' ),
		);

		$found     = array();
		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations['primary-menu'] ) ? (int) $locations['primary-menu'] : 0;

		if ( $menu_id ) {
			$items = wp_get_nav_menu_items( $menu_id );

			if ( ! empty( $items ) ) {
				$needle    = vance_kb_lobby_slugify( $menu_label );
				$parent_id = 0;

				foreach ( $items as $item ) {
					if ( vance_kb_lobby_slugify( $item->title ) === $needle ) {
						$parent_id = (int) $item->ID;
						break;
					}
				}

				if ( $parent_id ) {
					foreach ( vance_kb_lobby_children( $items, $parent_id ) as $item ) {
						$url = trim( (string) $item->url );
						if ( '' === $url || '#' === $url ) {
							continue; // A heading, not a tool.
						}

						$found[] = array(
							'title' => vance_kb_lobby_text( $item->title ),
							'url'   => $url,
							'desc'  => vance_kb_lobby_text( $item->description ),
							'path'  => vance_kb_lobby_path( $url ),
						);
					}
				}
			}
		}

		if ( empty( $found ) ) {
			foreach ( $fallback as $tool ) {
				$found[] = array(
					'title' => vance_kb_lobby_text( $tool['title'] ),
					'url'   => home_url( '/' . $tool['path'] . '/' ),
					'desc'  => '',
					'path'  => $tool['path'],
				);
			}
		}

		/*
		 * Ask AI hangs off THE HUB panel's CTA banner rather than the tools
		 * column, so it is never among the children above - and it is the tool
		 * the hub leads with everywhere else. Appended last, and only if the
		 * page is really there.
		 */
		if ( $with_ai && ! in_array( 'ask-ai', wp_list_pluck( $found, 'path' ), true ) ) {
			$ask = get_page_by_path( 'ask-ai' );
			if ( $ask instanceof WP_Post && 'publish' === $ask->post_status ) {
				$found[] = array(
					'title' => 'Ask VANCE-Ai',
					'url'   => get_permalink( $ask ),
					'desc'  => '',
					'path'  => 'ask-ai',
				);
			}
		}

		$out = array();

		foreach ( $found as $tool ) {
			$meta = isset( $known[ $tool['path'] ] ) ? $known[ $tool['path'] ] : array();

			if ( '' === $tool['desc'] && ! empty( $meta['desc'] ) ) {
				$tool['desc'] = $meta['desc'];
			}

			// Last resort: the linked page's own excerpt, so a tool added to the
			// menu later still gets a line rather than a bare title.
			if ( '' === $tool['desc'] ) {
				$page = get_page_by_path( $tool['path'] );
				if ( $page instanceof WP_Post ) {
					$tool['desc'] = vance_kb_lobby_text( $page->post_excerpt );
				}
			}

			$out[] = array(
				'title' => $tool['title'],
				'url'   => $tool['url'],
				'desc'  => $tool['desc'],
				'icon'  => isset( $meta['icon'] ) ? $meta['icon'] : 'grid',
			);
		}

		return $out;
	}
endif;

if ( ! function_exists( 'vance_kb_lobby_latest' ) ) :
	/**
	 * The newest articles across every collection.
	 *
	 * The per-card previews answer "what is in THIS shelf"; this answers "what
	 * has the Hub published lately", which is the other reason a returning
	 * visitor opens this page.
	 *
	 * @param int $limit
	 * @return WP_Post[]
	 */
	function vance_kb_lobby_latest( $limit ) {
		$limit = (int) $limit;

		if ( $limit < 1 ) {
			return array();
		}

		return get_posts( array(
			'numberposts'      => $limit,
			'post_status'      => 'publish',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		) );
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
		$single = vance_get_theme_mod( 'vance_kblobby_accent_single', '#008080' );
		if ( ! vance_kb_lobby_rgb( $single ) ) {
			$single = '#008080';
		}

		// Only consulted in "match" mode; kept as the fallback for a category
		// with no accent of its own.
		$palette = array( '#008080', '#0EA5E9', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444' );

		$peek_limit = max( 0, min( 5, absint( vance_get_theme_mod( 'vance_kblobby_peek_count', 3 ) ) ) );
		/*
		 * Webinars & Courses defaults to "not launched" rather than hidden. The
		 * page behind it is a real Coming Soon with a waitlist form, and hiding
		 * it left that waitlist reachable only from the nav flyout - while also
		 * leaving five cards in a two-column grid, so the last row was half
		 * empty. The muted card states plainly that there is nothing to read
		 * yet.
		 */
		$hidden     = vance_kb_lobby_title_list( 'vance_kblobby_hidden_titles', '' );
		$soon_list  = vance_kb_lobby_title_list( 'vance_kblobby_soon_titles', 'Webinars and Courses' );

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
					$this_path = vance_kb_lobby_path( get_permalink( $this_page ) );

					/*
					 * Descend through mega-panel column headings. In the live
					 * KNOWLEDGEBASE panel the direct children are "Browse the
					 * library" (which points back at this page) and "By content
					 * type" (link disabled); the real collections sit one level
					 * further down. Reading only the direct children rendered a
					 * lobby of two cards that went nowhere. See
					 * vance_kb_lobby_is_column_heading() for what counts as a
					 * heading - it is about where an item points, not how deep
					 * it sits, so a plain flyout keeps working untouched.
					 */
					foreach ( vance_kb_lobby_children( $menu_items, $parent_id ) as $item ) {
						$kids = vance_kb_lobby_children( $menu_items, (int) $item->ID );

						if ( ! empty( $kids ) && vance_kb_lobby_is_column_heading( $item, $this_page, $this_path ) ) {
							foreach ( $kids as $kid ) {
								$raw[] = $kid;
							}
							continue;
						}

						$raw[] = $item;
					}

					/*
					 * Two things the flattened list carries that a lobby must
					 * not show: an entry for this very page ("All Articles" is
					 * the panel's own link home), and the same destination
					 * twice ("View all gastro conditions" repeats "Gastro
					 * Health Explained" so the column gets a footer link).
					 * Compared by path, so /a/ and https://host/a/ match, and
					 * the first spelling of each destination wins.
					 */
					$seen = array();
					$kept = array();

					foreach ( $raw as $item ) {
						$path = vance_kb_lobby_path( $item->url );

						if ( '' === $path || $path === $this_path || isset( $seen[ $path ] ) ) {
							continue;
						}

						$seen[ $path ] = true;
						$kept[]        = $item;
					}

					$raw = $kept;
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
				$unit    = 'article';
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

						$page_meta = vance_kb_lobby_page_count( $page_id );
						if ( null !== $page_meta ) {
							$count = (int) $page_meta['count'];
							$unit  = $page_meta['unit'];
						}
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
					'meta'    => vance_kb_lobby_meta_label( $count, $unit ),
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

/*
 * The four sections added below the collection cards. Each is independently
 * switchable, because what they show is only worth showing while it is true:
 * the topic strip is meaningless until child categories carry posts, and the
 * conditions strip duplicates a collection card if the GI hub is ever dropped
 * from the menu.
 */
$kb_stats_show = (bool) vance_get_theme_mod( 'vance_kblobby_stats_show', true );
$kb_stats_articles  = vance_get_theme_mod( 'vance_kblobby_stats_articles',   'Articles' );
$kb_stats_shelves   = vance_get_theme_mod( 'vance_kblobby_stats_shelves',    'Collections' );
$kb_stats_condition = vance_get_theme_mod( 'vance_kblobby_stats_conditions', 'Conditions covered' );
$kb_stats_tools     = vance_get_theme_mod( 'vance_kblobby_stats_tools',      'Free tools' );

$kb_topics_show    = (bool) vance_get_theme_mod( 'vance_kblobby_topics_show', true );
$kb_topics_eyebrow = vance_get_theme_mod( 'vance_kblobby_topics_eyebrow', 'By Topic' );
$kb_topics_title   = vance_get_theme_mod( 'vance_kblobby_topics_title', 'Go straight to a subject' );
$kb_topics_desc    = vance_get_theme_mod( 'vance_kblobby_topics_desc', 'The collections above are whole shelves. These are the subjects inside them, so you can skip a step.' );
$kb_topics_max     = max( 0, absint( vance_get_theme_mod( 'vance_kblobby_topics_max', 8 ) ) );

$kb_cond_show    = (bool) vance_get_theme_mod( 'vance_kblobby_cond_show', true );
$kb_cond_eyebrow = vance_get_theme_mod( 'vance_kblobby_cond_eyebrow', 'Conditions' );
$kb_cond_title   = vance_get_theme_mod( 'vance_kblobby_cond_title', 'Start from your condition' );
$kb_cond_desc    = vance_get_theme_mod( 'vance_kblobby_cond_desc', 'Each condition has its own guide - what it is, how it is diagnosed, and what living with it actually involves.' );
$kb_cond_link    = vance_get_theme_mod( 'vance_kblobby_cond_link_text', 'View all conditions' );

$kb_tools_show    = (bool) vance_get_theme_mod( 'vance_kblobby_tools_show', true );
$kb_tools_eyebrow = vance_get_theme_mod( 'vance_kblobby_tools_eyebrow', 'Free Tools' );
$kb_tools_title   = vance_get_theme_mod( 'vance_kblobby_tools_title', 'Turn the evidence into a number' );
$kb_tools_desc    = vance_get_theme_mod( 'vance_kblobby_tools_desc', 'The Hub is not only reading. These are free to use with no account, and you can save every result to a private dashboard once you have one.' );
$kb_tools_cta     = vance_get_theme_mod( 'vance_kblobby_tools_cta', 'Open' );
$kb_tools_menu    = vance_get_theme_mod( 'vance_kblobby_tools_menu_label', 'Free Health Tools' );
$kb_tools_with_ai = (bool) vance_get_theme_mod( 'vance_kblobby_tools_with_ai', true );

$kb_latest_show    = (bool) vance_get_theme_mod( 'vance_kblobby_latest_show', true );
$kb_latest_eyebrow = vance_get_theme_mod( 'vance_kblobby_latest_eyebrow', 'Just Published' );
$kb_latest_title   = vance_get_theme_mod( 'vance_kblobby_latest_title', 'Newest across the library' );
$kb_latest_desc    = vance_get_theme_mod( 'vance_kblobby_latest_desc', 'The most recent additions, whichever collection they landed in.' );
$kb_latest_count   = max( 0, min( 8, absint( vance_get_theme_mod( 'vance_kblobby_latest_count', 4 ) ) ) );

// Resolved once here rather than inside the markup, so the stats strip can
// count what the page is actually about to render instead of guessing.
$kb_topics     = $kb_topics_show ? vance_kb_lobby_topics( $kb_topics_max ) : array();
$kb_conditions = ( $kb_cond_show && function_exists( 'vance_gi_condition_cards' ) && function_exists( 'vance_gi_page_url' ) )
	? vance_gi_condition_cards()
	: array();
$kb_tools  = $kb_tools_show ? vance_kb_lobby_tools( $kb_tools_menu, $kb_tools_with_ai ) : array();
$kb_latest = $kb_latest_show ? vance_kb_lobby_latest( $kb_latest_count ) : array();

$kb_hub_url = function_exists( 'vance_gi_hub_url' ) ? vance_gi_hub_url() : '';
?>

<main id="main-content" class="kb-lobby">

	<?php
	/*
	 * HERO. Two designs, chosen by Appearance -> Customize -> Page -
	 * Knowledgebase -> Hero Section -> "Knowledgebase hero design". Defaults
	 * to 'classic', so deploying this changes nothing until an admin flips it.
	 * The spotlight renderer reads this page's own tag/title/description keys,
	 * and its band is the same site search this hero carries, so nothing is
	 * lost in the switch. See inc/page-hero-spotlight.php.
	 */
	if ( function_exists( 'vance_page_hero_spotlight_active' )
		&& vance_page_hero_spotlight_active( 'kblobby' ) ) :
		vance_render_page_hero_spotlight( 'kblobby' );
	else :
	?>

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

	<?php endif; ?>


	<?php
	/*
	 * SCALE STRIP
	 *
	 * Directly under the hero, because the first question this page has to
	 * answer is "is there anything in here?" - and until now the honest answer
	 * from the page itself was two empty cards.
	 *
	 * Every figure is counted at render time. None of them is editable, on
	 * purpose: a hand-typed "150+ articles" is a claim that rots, and this is a
	 * clinical site.
	 */
	if ( $kb_stats_show ) :
		$kb_post_counts = wp_count_posts( 'post' );
		$kb_stat_rows   = array(
			array( 'n' => isset( $kb_post_counts->publish ) ? (int) $kb_post_counts->publish : 0, 'label' => $kb_stats_articles ),
			array( 'n' => count( $kb_blocks ), 'label' => $kb_stats_shelves ),
			array( 'n' => function_exists( 'vance_gi_condition_cards' ) ? count( vance_gi_condition_cards() ) : 0, 'label' => $kb_stats_condition ),
			array( 'n' => count( $kb_tools ), 'label' => $kb_stats_tools ),
		);

		// A zero is not a boast, it is a gap - drop the cell rather than
		// advertise it. That also makes the strip disappear on a bare install.
		$kb_stat_rows = array_values( array_filter( $kb_stat_rows, static function ( $row ) {
			return $row['n'] > 0 && '' !== trim( (string) $row['label'] );
		} ) );

		if ( ! empty( $kb_stat_rows ) ) :
			?>
			<section class="kb-stats">
				<div class="container">
					<ul class="kb-stats__list">
						<?php foreach ( $kb_stat_rows as $kb_stat ) : ?>
							<li class="kb-stat">
								<span class="kb-stat__num"><?php echo esc_html( number_format_i18n( $kb_stat['n'] ) ); ?></span>
								<span class="kb-stat__label"><?php echo esc_html( $kb_stat['label'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>
	<?php
	// Knowledgebase Promo Block + Prime Block. Each is called at all three
	// slots and renders only in the one matching its own "Position on the
	// page" setting, so an admin can move either without touching this file.
	// Same call-at-every-slot pattern as vance_render_prime_block_categories().
	if ( function_exists( 'vance_render_promo_knowledgebase' ) ) { vance_render_promo_knowledgebase( 'below_hero' ); }
	if ( function_exists( 'vance_render_prime_block_knowledgebase' ) ) { vance_render_prime_block_knowledgebase( 'below_hero' ); }
	?>

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

	<?php
	// Knowledgebase Promo Block + Prime Block. Each is called at all three
	// slots and renders only in the one matching its own "Position on the
	// page" setting, so an admin can move either without touching this file.
	// Same call-at-every-slot pattern as vance_render_prime_block_categories().
	if ( function_exists( 'vance_render_promo_knowledgebase' ) ) { vance_render_promo_knowledgebase( 'below_intro' ); }
	if ( function_exists( 'vance_render_prime_block_knowledgebase' ) ) { vance_render_prime_block_knowledgebase( 'below_intro' ); }
	?>

	<!-- BLOCKS -->
	<?php /* id="collections": the spotlight hero's first button points at it,
	         and it is a sensible skip target from anywhere else too. */ ?>
	<section id="collections" class="kb-lobby-blocks">
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


	<!-- TOPICS -->
	<?php if ( ! empty( $kb_topics ) ) : ?>
		<section class="kb-topics">
			<div class="container">
				<div class="kb-sec-head">
					<?php if ( $kb_topics_eyebrow ) : ?>
						<span class="kb-sec-head__eyebrow"><?php echo esc_html( $kb_topics_eyebrow ); ?></span>
					<?php endif; ?>
					<h2 class="kb-sec-head__title"><?php echo esc_html( $kb_topics_title ); ?></h2>
					<?php if ( $kb_topics_desc ) : ?>
						<p class="kb-sec-head__desc"><?php echo esc_html( $kb_topics_desc ); ?></p>
					<?php endif; ?>
				</div>

				<ul class="kb-topic-grid">
					<?php foreach ( $kb_topics as $kb_topic ) : ?>
						<li>
							<a class="kb-topic" href="<?php echo esc_url( $kb_topic['url'] ); ?>">
								<span class="kb-topic__title"><?php echo esc_html( $kb_topic['title'] ); ?></span>
								<span class="kb-topic__meta">
									<?php
									// Parent first: "Tests & Treatments" means little
									// on its own, "Gastro Living" says which shelf it
									// came off.
									if ( $kb_topic['parent'] ) {
										echo esc_html( $kb_topic['parent'] ) . '<span class="kb-topic__dot" aria-hidden="true"> &middot; </span>';
									}
									echo esc_html( vance_kb_lobby_meta_label( $kb_topic['count'] ) );
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<!-- CONDITIONS -->
	<?php if ( ! empty( $kb_conditions ) ) : ?>
		<section class="kb-conditions">
			<div class="container">
				<div class="kb-sec-head">
					<?php if ( $kb_cond_eyebrow ) : ?>
						<span class="kb-sec-head__eyebrow"><?php echo esc_html( $kb_cond_eyebrow ); ?></span>
					<?php endif; ?>
					<h2 class="kb-sec-head__title"><?php echo esc_html( $kb_cond_title ); ?></h2>
					<?php if ( $kb_cond_desc ) : ?>
						<p class="kb-sec-head__desc"><?php echo esc_html( $kb_cond_desc ); ?></p>
					<?php endif; ?>
				</div>

				<ul class="kb-cond-grid">
					<?php foreach ( $kb_conditions as $kb_cond ) : ?>
						<li>
							<a class="kb-cond" href="<?php echo esc_url( vance_gi_page_url( $kb_cond['slug'] ) ); ?>">
								<span class="kb-cond__title"><?php echo esc_html( vance_kb_lobby_text( $kb_cond['title'] ) ); ?></span>
								<span class="kb-cond__desc"><?php echo esc_html( wp_trim_words( vance_kb_lobby_text( $kb_cond['desc'] ), 16, '...' ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $kb_hub_url && $kb_cond_link ) : ?>
					<p class="kb-sec-more">
						<a href="<?php echo esc_url( $kb_hub_url ); ?>">
							<?php echo esc_html( $kb_cond_link ); ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<!-- TOOLS -->
	<?php if ( ! empty( $kb_tools ) ) : ?>
		<section class="kb-tools">
			<div class="container">
				<div class="kb-sec-head kb-sec-head--invert">
					<?php if ( $kb_tools_eyebrow ) : ?>
						<span class="kb-sec-head__eyebrow"><?php echo esc_html( $kb_tools_eyebrow ); ?></span>
					<?php endif; ?>
					<h2 class="kb-sec-head__title"><?php echo esc_html( $kb_tools_title ); ?></h2>
					<?php if ( $kb_tools_desc ) : ?>
						<p class="kb-sec-head__desc"><?php echo esc_html( $kb_tools_desc ); ?></p>
					<?php endif; ?>
				</div>

				<ul class="kb-tool-grid">
					<?php foreach ( $kb_tools as $kb_tool ) : ?>
						<?php $kb_tool_icon = vance_kb_lobby_svg( $kb_tool['icon'] ); ?>
						<li>
							<a class="kb-tool" href="<?php echo esc_url( $kb_tool['url'] ); ?>">
								<?php if ( $kb_tool_icon ) : ?>
									<span class="kb-tool__icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><?php echo $kb_tool_icon; // phpcs:ignore WordPress.Security.EscapeOutput -- hardcoded SVG paths from vance_kb_lobby_svg(). ?></svg>
									</span>
								<?php endif; ?>
								<span class="kb-tool__title"><?php echo esc_html( $kb_tool['title'] ); ?></span>
								<?php if ( $kb_tool['desc'] ) : ?>
									<span class="kb-tool__desc"><?php echo esc_html( wp_trim_words( $kb_tool['desc'], 22, '...' ) ); ?></span>
								<?php endif; ?>
								<span class="kb-tool__cta">
									<?php echo esc_html( $kb_tools_cta ); ?>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<!-- LATEST -->
	<?php if ( ! empty( $kb_latest ) ) : ?>
		<section class="kb-latest">
			<div class="container">
				<div class="kb-sec-head">
					<?php if ( $kb_latest_eyebrow ) : ?>
						<span class="kb-sec-head__eyebrow"><?php echo esc_html( $kb_latest_eyebrow ); ?></span>
					<?php endif; ?>
					<h2 class="kb-sec-head__title"><?php echo esc_html( $kb_latest_title ); ?></h2>
					<?php if ( $kb_latest_desc ) : ?>
						<p class="kb-sec-head__desc"><?php echo esc_html( $kb_latest_desc ); ?></p>
					<?php endif; ?>
				</div>

				<?php
				/*
				 * .news-card / .card-image / .card-content / .card-stretched-link
				 * are the site's existing post tile, borrowed wholesale rather
				 * than reinvented here: the classes carry the whole-card click
				 * target and, through the "Article cards stay square" rule at the
				 * end of main.css, the square corners every post tile on the site
				 * has. A private tile class would have had to be added to that
				 * list to stay in the system.
				 */
				?>
				<div class="kb-latest-grid">
					<?php foreach ( $kb_latest as $kb_post ) : ?>
						<?php
						$kb_post_cats = get_the_category( $kb_post->ID );
						$kb_post_cat  = ! empty( $kb_post_cats ) ? vance_kb_lobby_text( $kb_post_cats[0]->name ) : '';
						$kb_post_img  = get_the_post_thumbnail_url( $kb_post, 'medium_large' );
						?>
						<article class="news-card kb-latest-card">
							<?php // No thumbnail: a flat tinted panel, not a broken frame. The card's border-top: none on .card-content needs something above it. ?>
							<div class="card-image kb-latest-card__image<?php echo $kb_post_img ? '' : ' kb-latest-card__image--empty'; ?>"<?php echo $kb_post_img ? ' style="background-image: url(\'' . esc_url( $kb_post_img ) . '\');"' : ''; ?>></div>

							<div class="card-content">
								<?php if ( $kb_post_cat ) : ?>
									<span class="kb-latest-card__cat"><?php echo esc_html( $kb_post_cat ); ?></span>
								<?php endif; ?>
								<h3 class="kb-latest-card__title">
									<a class="card-stretched-link" href="<?php echo esc_url( get_permalink( $kb_post ) ); ?>"><?php echo esc_html( vance_kb_lobby_text( get_the_title( $kb_post ) ) ); ?></a>
								</h3>
								<time class="kb-latest-card__date" datetime="<?php echo esc_attr( get_the_date( 'c', $kb_post ) ); ?>"><?php echo esc_html( get_the_date( '', $kb_post ) ); ?></time>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

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

	<?php
	// Knowledgebase Promo Block + Prime Block. Each is called at all three
	// slots and renders only in the one matching its own "Position on the
	// page" setting, so an admin can move either without touching this file.
	// Same call-at-every-slot pattern as vance_render_prime_block_categories().
	if ( function_exists( 'vance_render_promo_knowledgebase' ) ) { vance_render_promo_knowledgebase( 'above_footer' ); }
	if ( function_exists( 'vance_render_prime_block_knowledgebase' ) ) { vance_render_prime_block_knowledgebase( 'above_footer' ); }
	?>

</main>

<?php get_footer(); ?>
