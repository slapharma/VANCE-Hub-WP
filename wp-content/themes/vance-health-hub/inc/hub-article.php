<?php
/**
 * VanceHubClub article layout: an opt-in variant of single.php.
 *
 * Off by default. With the Customizer checkbox "Article pages: use the
 * VanceHubClub layout" (Home layout section) unticked, nothing in this file
 * changes any article: no markup, no stylesheet, no body class, no filter
 * output. An administrator can preview it on one article without saving:
 * add ?vhh_layout=hub to the article address (?vhh_layout=classic forces it
 * off). The preview goes through vance_hub_resolve_layout() in
 * inc/hub-layout.php, so a visitor's query value is never read.
 *
 * When the layout is active on an article (is_singular( 'post' )):
 *
 *   - body class vhub-article, and assets/css/hub-article.css;
 *   - one "Try this now" tool prompt in the article body, after the second
 *     top-level paragraph (after the content when there are fewer than four),
 *     added by a the_content filter at display time. Nothing rewrites
 *     post_content, and the filter runs only for the main post in the main
 *     loop, so feeds, REST, excerpts, the VANCE-Ai sources, the citation
 *     checker and the schema never see it (they read post_content directly
 *     or run outside the loop);
 *   - three cards at the top of the article sidebar, through the
 *     vance_article_sidebar_top action in single.php: Ask VANCE-Ai, the tools
 *     for this subject, and the Customizer "Inside the Hub this week" tiles
 *     (only when at least one tile has a title).
 *
 * The Ask card reuses assets/js/hub-home.js (its form handler is generic), so
 * there is no article script of its own.
 *
 * THE DATA RULE. Every tool, URL, image and count comes from vance_hub_tools()
 * and the Customizer; a card with nothing behind it does not render.
 *
 * To back the feature out, delete the require line in functions.php.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The shared preview resolver (vance_hub_preview_query).
require_once __DIR__ . '/hub-preview.php';

/* =========================================================================
 * Pure helpers (no WordPress calls apart from escaping and translation)
 * ====================================================================== */

/**
 * Decide whether the article layout is on.
 *
 * Reuses the home layout's preview resolution: the saved checkbox stands in
 * for the saved layout, and an administrator's ?vhh_layout=hub|classic wins
 * over it. Any other query value, or a viewer who may not preview, leaves the
 * saved checkbox in charge.
 *
 * @param mixed $mod_value   Saved vance_hub_articles mod.
 * @param mixed $query_value Value of ?vhh_layout= ('' when absent or unread).
 * @param bool  $can_preview True when the viewer may preview layouts.
 * @return bool
 */
function vance_hub_article_resolve( $mod_value, $query_value, $can_preview ) {
	$saved = vance_hub_sanitize_checkbox( $mod_value ) ? 'hub' : 'classic';
	return 'hub' === vance_hub_resolve_layout( $saved, $query_value, $can_preview );
}

/**
 * The category slugs that point an article at each tool. The first tool whose
 * list meets the article's categories (ancestors included) wins; an article
 * that meets none gets the default tool.
 *
 * Slugs confirmed against the live category list on 2026-11-18:
 * food-nutrition (child of content-gastro-living), nutrition-diet (child of
 * content-gastro-living, empty today) and gastro-recipes (top level, empty
 * today). A slug with no posts costs nothing and starts working when an
 * editor files an article there.
 *
 * @return array<string, string[]> Tool key (vance_hub_tools() 'mod') => slugs.
 */
function vance_hub_article_tool_map() {
	$map = apply_filters( 'vance_hub_article_tool_map', array(
		'recipes' => array( 'food-nutrition', 'nutrition-diet', 'gastro-recipes' ),
	) );
	return is_array( $map ) ? $map : array();
}

/**
 * Pick a tool for a set of category slugs.
 *
 * @param string[] $slugs   The article's category slugs, ancestors included.
 * @param array    $map     vance_hub_article_tool_map() shape.
 * @param string   $default Tool key when nothing matches.
 * @return string Tool key.
 */
function vance_hub_article_pick_tool( $slugs, $map, $default = 'survey' ) {
	$slugs = array_filter( (array) $slugs, 'is_string' );
	foreach ( (array) $map as $tool => $tool_slugs ) {
		if ( ! is_string( $tool ) || '' === $tool || ! is_array( $tool_slugs ) ) {
			continue;
		}
		if ( array_intersect( $slugs, array_filter( $tool_slugs, 'is_string' ) ) ) {
			return $tool;
		}
	}
	return $default;
}

/**
 * Byte offsets just after each top-level paragraph's closing tag.
 *
 * "Top-level" means a <p> that is a direct child of the content, not one
 * inside a blockquote, list, figure, table or block wrapper. Comments
 * (including block delimiters) are skipped, the contents of script, style,
 * textarea and title are skipped whole, void and self-closed elements do not
 * nest, an end tag closes back to its matching start tag (so an unclosed
 * inline tag inside a paragraph does not throw the count off), and a
 * block-level start tag closes an open paragraph, as a browser does.
 *
 * @param string $html Filtered post content.
 * @return int[]
 */
function vance_hub_article_paragraph_ends( $html ) {
	$html     = (string) $html;
	$len      = strlen( $html );
	$stack    = array();
	$ends     = array();
	$void     = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' );
	$raw      = array( 'script', 'style', 'textarea', 'title' );
	$closes_p = array( 'address', 'article', 'aside', 'blockquote', 'details', 'div', 'dl', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'ul' );
	$pos      = 0;

	while ( $pos < $len ) {
		$lt = strpos( $html, '<', $pos );
		if ( false === $lt ) {
			break;
		}
		if ( 0 === substr_compare( $html, '<!--', $lt, 4 ) ) {
			$close = strpos( $html, '-->', $lt + 4 );
			if ( false === $close ) {
				break;
			}
			$pos = $close + 3;
			continue;
		}
		if ( ! preg_match( '/\G<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/', $html, $m, 0, $lt ) ) {
			$pos = $lt + 1;
			continue;
		}
		$after = $lt + strlen( $m[0] );
		$name  = strtolower( $m[2] );

		if ( '/' === $m[1] ) {
			$at = array_search( $name, array_reverse( $stack, true ), true );
			if ( false !== $at ) {
				$stack = array_slice( $stack, 0, $at );
				// Closing a <p> that was the outermost open element: top level.
				if ( 'p' === $name && 0 === count( $stack ) ) {
					$ends[] = $after;
				}
			}
			$pos = $after;
			continue;
		}

		if ( in_array( $name, $raw, true ) ) {
			$close = stripos( $html, '</' . $name, $after );
			if ( false === $close ) {
				break;
			}
			$gt  = strpos( $html, '>', $close );
			$pos = false === $gt ? $len : $gt + 1;
			continue;
		}

		// A block start tag closes an open paragraph, as a browser does.
		if ( in_array( $name, $closes_p, true ) && ! empty( $stack ) && 'p' === end( $stack ) ) {
			array_pop( $stack );
			if ( 0 === count( $stack ) ) {
				$ends[] = $lt;
			}
		}

		$self = '/' === substr( rtrim( $m[3] ), -1 );
		if ( ! $self && ! in_array( $name, $void, true ) ) {
			$stack[] = $name;
		}
		$pos = $after;
	}
	return $ends;
}

/**
 * Insert a block into post content.
 *
 * After the $after-th top-level paragraph when the content has at least $min
 * of them; otherwise after the content. Content that already holds the block's
 * marker is returned unchanged, so running the filter twice inserts once.
 * Empty content and an empty block are returned unchanged.
 *
 * @param string $html  Filtered post content.
 * @param string $block Block markup (carries data-vhub-aprompt).
 * @param int    $after Paragraph to insert after.
 * @param int    $min   Fewest paragraphs for an in-body insert.
 * @return string
 */
function vance_hub_article_insert( $html, $block, $after = 2, $min = 4 ) {
	$html  = (string) $html;
	$block = (string) $block;
	if ( '' === $block || '' === trim( $html ) ) {
		return $html;
	}
	if ( false !== strpos( $html, 'data-vhub-aprompt' ) ) {
		return $html;
	}
	$ends = vance_hub_article_paragraph_ends( $html );
	if ( count( $ends ) >= max( $min, $after ) ) {
		$at = $ends[ $after - 1 ];
		return substr( $html, 0, $at ) . "\n" . $block . "\n" . substr( $html, $at );
	}
	return rtrim( $html ) . "\n" . $block . "\n";
}

/**
 * The prompt's own words for a tool. Each line is already on the site and
 * says only what the tool does. They differ from the tool tile wording, which
 * the sidebar tools card uses, so the page does not say the same thing twice.
 *
 *   survey  - the hub home's survey card (front-page-hub.php). The survey has
 *             no result page: it saves answers and points to guides, tools and
 *             recipes, which is what this says.
 *   recipes - the promo block's tool card (inc/promo-block.php).
 *
 * @param string $tool Tool key.
 * @return array{title: string, text: string}|array Empty for an unknown key.
 */
function vance_hub_article_prompt_copy( $tool ) {
	$copy = array(
		'survey'  => array(
			'title' => __( 'Not sure where to start?', 'vance-health-hub' ),
			'text'  => __( 'Answer a few short questions about you and your gut. The Hub then points you to the guides, tools and recipes that fit.', 'vance-health-hub' ),
		),
		'recipes' => array(
			'title' => __( 'Gut-friendly recipes', 'vance-health-hub' ),
			'text'  => __( 'Browse gut-friendly recipes with full nutrition data and build a weekly meal plan.', 'vance-health-hub' ),
		),
	);
	return isset( $copy[ $tool ] ) ? $copy[ $tool ] : array();
}

/**
 * One tool's row from a vance_hub_tools() list, by key.
 *
 * @param array  $tools vance_hub_tools() rows.
 * @param string $key   Tool key ('mod').
 * @return array Empty when the key is not there or the row has no URL.
 */
function vance_hub_article_find_tool( $tools, $key ) {
	foreach ( (array) $tools as $tool ) {
		if ( is_array( $tool ) && isset( $tool['mod'] ) && $key === $tool['mod'] && ! empty( $tool['href'] ) ) {
			return $tool;
		}
	}
	return array();
}

/**
 * The in-article prompt.
 *
 * Marked vhh-ui so the annotations plugin leaves it out of the text it
 * anchors highlights to (vhh-annotations/assets/js/anchoring.js isSkippable())
 * and out of the insertion gaps (insertion-annotator.js refreshBlocks()).
 * With it, a highlight's quote, prefix and suffix read exactly as they do with
 * the layout off.
 *
 * The title is a styled div, not a heading: a heading here would enter the
 * article's own outline between two of the author's sections. The eyebrow,
 * the title and the sidebar hint are divs rather than paragraphs because
 * main.css forces `.container p { font-size: 16px !important }` on phones,
 * and these must keep their own sizes without an !important of their own.
 *
 * @param array $tool vance_hub_tools() row.
 * @param array $copy vance_hub_article_prompt_copy() row.
 * @return string '' when either is missing.
 */
function vance_hub_article_prompt_html( $tool, $copy ) {
	if ( empty( $tool['href'] ) || empty( $copy['title'] ) || empty( $copy['text'] ) || empty( $tool['cta'] ) ) {
		return '';
	}
	$mod  = isset( $tool['mod'] ) ? (string) $tool['mod'] : '';
	$html = '<aside class="vhub-aprompt vhub-aprompt--' . esc_attr( $mod ) . ' vhh-ui" data-vhub-aprompt aria-label="' . esc_attr__( 'Try this now', 'vance-health-hub' ) . '">';
	if ( ! empty( $tool['img'] ) ) {
		// A background, not an <img>: the annotations plugin's image annotator
		// offers every <img> in the article body for comment, and .vhh-ui does
		// not exempt images. The photo is decoration; the text carries the
		// meaning, so it is hidden from assistive technology.
		// Percent-encode the characters that could end the CSS url('...') early.
		// esc_url() turns a quote into an HTML entity, and the browser decodes
		// that back to a quote before it reads the style.
		$src   = str_replace( array( "'", '(', ')' ), array( '%27', '%28', '%29' ), (string) $tool['img'] );
		$html .= '<div class="vhub-aprompt__img" aria-hidden="true" style="background-image:url(\'' . esc_url( $src ) . '\')"></div>';
	}
	$html .= '<div class="vhub-aprompt__body">';
	$html .= '<div class="vhub-aprompt__eyebrow">' . esc_html__( 'Try this now', 'vance-health-hub' ) . '</div>';
	$html .= '<div class="vhub-aprompt__title">' . esc_html( $copy['title'] ) . '</div>';
	$html .= '<p class="vhub-aprompt__text">' . esc_html( $copy['text'] ) . '</p>';
	$html .= '<a class="vhub-aprompt__btn" href="' . esc_url( $tool['href'] ) . '">' . esc_html( $tool['cta'] ) . ' <span aria-hidden="true">&rarr;</span></a>';
	$html .= '</div></aside>';
	return $html;
}

/**
 * Sidebar card: ask VANCE-Ai about this article.
 *
 * The input has no name, so a health question never reaches a URL. With
 * JavaScript, hub-home.js opens the site-wide chat and asks the question; the
 * chat already sends the article's ID as context (CFG.postId, set from the
 * queried post in functions.php), so no title is passed. Without JavaScript the
 * form opens the VANCE-Ai page.
 *
 * @param string $askai_url URL of the VANCE-Ai page.
 * @param bool   $show_hint True when highlight-to-ask is switched on.
 * @return string
 */
function vance_hub_article_ask_card_html( $askai_url, $show_hint ) {
	if ( '' === (string) $askai_url ) {
		return '';
	}
	$html  = '<section class="oped-sidebar-block vhub-acard vhub-acard--ask" aria-labelledby="vhub-acard-ask-title">';
	$html .= '<h4 id="vhub-acard-ask-title">' . esc_html__( 'Ask VANCE-Ai about this article', 'vance-health-hub' ) . '</h4>';
	$html .= '<form class="vhub-aask" action="' . esc_url( $askai_url ) . '" method="get" data-vhub-ask-form>';
	$html .= '<label class="vhub-aask__label" for="vhub-aask-input">' . esc_html__( 'Your question', 'vance-health-hub' ) . '</label>';
	$html .= '<div class="vhub-aask__box">';
	$html .= '<input id="vhub-aask-input" class="vhub-aask__input" type="text" autocomplete="off" placeholder="' . esc_attr__( 'What does this mean?', 'vance-health-hub' ) . '">';
	$html .= '<button class="vhub-aask__btn" type="submit">' . esc_html__( 'Ask', 'vance-health-hub' ) . ' <span aria-hidden="true">&rarr;</span></button>';
	$html .= '</div></form>';
	if ( $show_hint ) {
		$html .= '<div class="vhub-acard__hint">' . esc_html__( 'Tip: highlight any word or passage as you read to ask about it directly.', 'vance-health-hub' ) . '</div>';
	}
	$html .= '</section>';
	return $html;
}

/**
 * Sidebar card: the tools for this subject. Each row is a link with a
 * thumbnail, the tool's name and its one-line description, from
 * vance_hub_tools(). Rows without a URL are dropped; no rows, no card.
 *
 * @param array $tools vance_hub_tools() rows, in display order.
 * @return string
 */
function vance_hub_article_tools_card_html( $tools ) {
	$rows = '';
	foreach ( (array) $tools as $tool ) {
		if ( ! is_array( $tool ) || empty( $tool['href'] ) || empty( $tool['title'] ) ) {
			continue;
		}
		$rows .= '<li><a class="vhub-atool vhub-atool--' . esc_attr( isset( $tool['mod'] ) ? $tool['mod'] : '' ) . '" href="' . esc_url( $tool['href'] ) . '">';
		if ( ! empty( $tool['img'] ) ) {
			$rows .= '<img class="vhub-atool__img" src="' . esc_url( $tool['img'] ) . '" alt="' . esc_attr( isset( $tool['alt'] ) ? $tool['alt'] : '' ) . '" width="72" height="72" loading="lazy" decoding="async">';
		}
		$rows .= '<span class="vhub-atool__body"><span class="vhub-atool__name">' . esc_html( $tool['title'] ) . '</span>';
		if ( ! empty( $tool['desc'] ) ) {
			$rows .= '<span class="vhub-atool__desc">' . esc_html( $tool['desc'] ) . '</span>';
		}
		$rows .= '</span></a></li>';
	}
	if ( '' === $rows ) {
		return '';
	}
	return '<section class="oped-sidebar-block vhub-acard vhub-acard--tools" aria-labelledby="vhub-acard-tools-title">'
		. '<h4 id="vhub-acard-tools-title">' . esc_html__( 'Tools for this subject', 'vance-health-hub' ) . '</h4>'
		. '<ul class="vhub-atools">' . $rows . '</ul></section>';
}

/**
 * Sidebar card: "Inside the Hub this week", from the hub home's Customizer
 * tiles (vance_hub_filter_tiles() output). No tiles, no card.
 *
 * @param array $tiles Filtered tiles.
 * @return string
 */
function vance_hub_article_tiles_card_html( $tiles ) {
	if ( empty( $tiles ) || ! is_array( $tiles ) ) {
		return '';
	}
	$rows = '';
	foreach ( $tiles as $tile ) {
		if ( ! is_array( $tile ) || empty( $tile['title'] ) ) {
			continue;
		}
		$link  = isset( $tile['link'] ) ? (string) $tile['link'] : '';
		$href  = '' !== $link ? esc_url( $link ) : '';
		$tag   = '' !== $href ? 'a' : 'div';
		$rows .= '<li><' . $tag . ' class="vhub-atile"' . ( 'a' === $tag ? ' href="' . $href . '"' : '' ) . '>';
		if ( ! empty( $tile['badge'] ) ) {
			$rows .= '<span class="vhub-atile__badge">' . esc_html( $tile['badge'] ) . '</span>';
		}
		$rows .= '<span class="vhub-atile__title">' . esc_html( $tile['title'] ) . '</span>';
		if ( ! empty( $tile['text'] ) ) {
			$rows .= '<span class="vhub-atile__text">' . esc_html( $tile['text'] ) . '</span>';
		}
		$rows .= '</' . $tag . '></li>';
	}
	if ( '' === $rows ) {
		return '';
	}
	return '<section class="oped-sidebar-block vhub-acard vhub-acard--tiles" aria-labelledby="vhub-acard-tiles-title">'
		. '<h4 id="vhub-acard-tiles-title">' . esc_html__( 'Inside the Hub this week', 'vance-health-hub' ) . '</h4>'
		. '<ul class="vhub-atiles">' . $rows . '</ul></section>';
}

/* =========================================================================
 * The switch
 * ====================================================================== */

/**
 * True when the VanceHubClub article layout is active for this request.
 *
 * Same capability-first read as vance_hub_layout_active(): a visitor's
 * ?vhh_layout= is never looked at, and an array value is ignored.
 *
 * @return bool
 */
function vance_hub_article_active() {
	if ( ! is_singular( 'post' ) ) {
		return false;
	}
	$query = vance_hub_preview_query();
	return vance_hub_article_resolve( vance_hub_switch_on( 'vance_hub_articles' ), $query, '' !== $query );
}

/**
 * True when single.php treats the post as a Patient Downloads handout. Same
 * test as single.php ($vpd_is_download_post).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function vance_hub_article_is_download( $post_id ) {
	$file = (string) get_post_meta( $post_id, '_vpd_pdf_file', true );
	return '' !== $file && file_exists( get_template_directory() . '/assets/downloads/' . $file );
}

/**
 * Stylesheet and script, only when the article layout renders. The script is
 * the hub home's (the Ask form and [data-vhub-join] handlers are generic).
 */
function vance_hub_article_enqueue_assets() {
	if ( ! vance_hub_article_active() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style(
		'vance-hub-article',
		$uri . '/assets/css/hub-article.css',
		array( 'vance-main-style', 'vance-oped-template' ),
		@filemtime( $dir . '/assets/css/hub-article.css' ) ?: '1.0.0'
	);
	wp_enqueue_script(
		'vance-hub-home',
		$uri . '/assets/js/hub-home.js',
		array(),
		@filemtime( $dir . '/assets/js/hub-home.js' ) ?: '1.0.0',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_article_enqueue_assets', 20 );

/**
 * Body class for the article layout.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function vance_hub_article_body_class( $classes ) {
	if ( vance_hub_article_active() ) {
		$classes[] = 'vhub-article';
	}
	return $classes;
}
add_filter( 'body_class', 'vance_hub_article_body_class' );

/* =========================================================================
 * The in-article prompt
 * ====================================================================== */

/**
 * The article's category slugs, with every ancestor's slug.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function vance_hub_article_category_slugs( $post_id ) {
	$slugs = array();
	foreach ( (array) get_the_category( $post_id ) as $term ) {
		if ( ! is_object( $term ) || empty( $term->slug ) ) {
			continue;
		}
		$slugs[] = (string) $term->slug;
		if ( ! empty( $term->term_id ) ) {
			foreach ( (array) get_ancestors( (int) $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
				$ancestor = get_term( (int) $ancestor_id, 'category' );
				if ( is_object( $ancestor ) && ! empty( $ancestor->slug ) ) {
					$slugs[] = (string) $ancestor->slug;
				}
			}
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * The tool key for an article.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function vance_hub_article_tool_key( $post_id ) {
	return vance_hub_article_pick_tool( vance_hub_article_category_slugs( $post_id ), vance_hub_article_tool_map() );
}

/**
 * True when the the_content filter may add the prompt in this call.
 *
 * Only the main post, rendered by the main query's loop on a single article,
 * with the layout on. Each guard closes one path the prompt must never reach:
 *
 *   is_admin / REST_REQUEST / wp_doing_ajax / is_feed - editor previews, the
 *       REST API (ai-visibility.php's markdown routes apply the_content there),
 *       admin-ajax (dashboard-functions.php applies it there) and feeds;
 *   in_the_loop + is_main_query - wp_head (AIOSEO description, schema, og
 *       tags) and wp_footer run outside the loop, and so does the annotations
 *       plugin's review page, which never starts the loop;
 *   the get_the_excerpt / wp_trim_excerpt filters - an excerpt built from the
 *       content (single.php's Read Next, patient-download-hero.php) runs the
 *       content filters too;
 *   get_the_ID() === queried object - secondary loops inside the main loop
 *       (Read Next, related posts) render other posts;
 *   password-protected posts render a form, not the article.
 *
 * The VANCE-Ai sources (askai-functions.php, askai-kb.php), the citation
 * checker (citation-check.php) and article-conditions.php read
 * $post->post_content directly and never run this filter at all.
 *
 * @return bool
 */
function vance_hub_article_should_filter() {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || is_feed() ) {
		return false;
	}
	if ( ! in_the_loop() || ! is_main_query() ) {
		return false;
	}
	if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {
		return false;
	}
	// A the_content call made from inside another one (a shortcode or block
	// that filters some other string while the article is being built) is not
	// the article body.
	if ( vance_hub_article_depth() > 1 ) {
		return false;
	}
	if ( ! vance_hub_article_active() ) {
		return false;
	}
	$post_id = (int) get_the_ID();
	if ( ! $post_id || $post_id !== (int) get_queried_object_id() ) {
		return false;
	}
	// One prompt per article per request. A later the_content call for the
	// same post (a sidebar widget, a direct wp_trim_excerpt()) gets none.
	if ( ! empty( $GLOBALS['vance_hub_article_prompted'][ $post_id ] ) ) {
		return false;
	}
	if ( post_password_required( $post_id ) || vance_hub_article_is_download( $post_id ) ) {
		return false;
	}
	return true;
}

/**
 * the_content filter: add the tool prompt to the article body.
 *
 * Priority 30: after blocks (9), wpautop (10), shortcodes (11) and the DOI
 * links (20), so the paragraphs it counts are the ones the reader sees.
 *
 * @param string $content Filtered content.
 * @return string
 */
function vance_hub_article_content_filter( $content ) {
	if ( ! vance_hub_article_should_filter() ) {
		return $content;
	}
	$key  = vance_hub_article_tool_key( (int) get_the_ID() );
	$tool = vance_hub_article_find_tool( vance_hub_tools(), $key );
	if ( empty( $tool ) ) {
		$key  = 'survey';
		$tool = vance_hub_article_find_tool( vance_hub_tools(), $key );
	}
	$out = vance_hub_article_insert( $content, vance_hub_article_prompt_html( $tool, vance_hub_article_prompt_copy( $key ) ) );
	// Record it only for a real filter run (depth 1), and only when the prompt
	// went in, so a call that added nothing does not use up the article's turn.
	if ( 1 === vance_hub_article_depth() && $out !== $content ) {
		$GLOBALS['vance_hub_article_prompted'][ (int) get_the_ID() ] = true;
	}
	return $out;
}
add_filter( 'the_content', 'vance_hub_article_content_filter', 30 );

/**
 * How many the_content calls are in progress: 1 for the outermost call, 2 or
 * more for a call made from inside another. 0 outside the filter.
 *
 * Read from the stack of running filters that WordPress keeps. WordPress
 * unwinds that stack on every exit path, so this needs no counter of its own
 * and cannot be left at a wrong value.
 *
 * @return int
 */
function vance_hub_article_depth() {
	$running = isset( $GLOBALS['wp_current_filter'] ) && is_array( $GLOBALS['wp_current_filter'] ) ? $GLOBALS['wp_current_filter'] : array();
	return count( array_keys( $running, 'the_content', true ) );
}

/* =========================================================================
 * The sidebar cards
 * ====================================================================== */

/**
 * The three cards at the top of the article sidebar (single.php fires
 * vance_article_sidebar_top first thing inside aside.oped-sidebar).
 */
function vance_hub_article_sidebar_top() {
	if ( ! vance_hub_article_active() ) {
		return;
	}
	$post_id = (int) get_the_ID();
	$html    = vance_hub_article_ask_card_html(
		vance_hub_page_url( 'ask-ai', '/ask-ai/' ),
		(bool) vance_get_theme_mod( 'vance_askai_highlight_enable', true )
	);

	// A Patient Downloads handout keeps its own sidebar (the sibling handouts)
	// directly under the Ask card: no tools card there, as no tool prompt.
	if ( ! vance_hub_article_is_download( $post_id ) ) {
		$tools  = vance_hub_tools();
		$key    = vance_hub_article_tool_key( $post_id );
		$first  = vance_hub_article_find_tool( $tools, $key );
		if ( empty( $first ) ) {
			$key   = 'survey';
			$first = vance_hub_article_find_tool( $tools, $key );
		}
		$second_key = (string) apply_filters( 'vance_hub_article_second_tool', 'discounts', $key );
		if ( $second_key === $key ) {
			$second_key = 'survey' === $key ? 'recipes' : 'survey';
		}
		$second = vance_hub_article_find_tool( $tools, $second_key );
		$html  .= vance_hub_article_tools_card_html( array_filter( array( $first, $second ) ) );
	}

	$html .= vance_hub_article_tiles_card_html( vance_hub_member_tiles() );
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped by the card builders.
}
add_action( 'vance_article_sidebar_top', 'vance_hub_article_sidebar_top' );

/* =========================================================================
 * Customizer
 * ====================================================================== */

/**
 * The article-layout checkbox, in the existing "Home layout" section.
 * Priority 9 puts it above the section's other controls (all at 10).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vance_hub_article_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'vance_hub_articles', array(
		'default'           => vance_hub_switch_default( 'vance_hub_articles' ),
		'sanitize_callback' => 'vance_hub_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'vance_hub_articles', array(
		'label'       => __( 'Article pages: use the VanceHubClub layout', 'vance-health-hub' ),
		'description' => __( 'Off: articles look as they do now. On: every article gets the VanceHubClub look, a "Try this now" tool prompt in the text and Ask VANCE-Ai and tool cards at the top of the sidebar. It works only when the Home layout master switch is on VanceHubClub. Administrators can preview it on one article without saving: add ?vhh_layout=hub to the article address. To see it on every page, turn on preview mode (VanceHubClub admin page or "Club preview" in the admin bar).', 'vance-health-hub' ),
		'section'     => 'vance_home_layout',
		'type'        => 'checkbox',
		'priority'    => 9,
	) );
}
add_action( 'customize_register', 'vance_hub_article_customize_register', 11 );
