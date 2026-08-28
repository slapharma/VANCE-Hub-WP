<?php
/**
 * Promo Block — one renderer, three instances.
 *
 * There used to be two unrelated components both called "promo": the wide
 * two-column band on the homepage (`.promo-block-section`, flex, 3 layouts, no
 * eyebrow, plain link CTA, heavily colour-configurable) and the glass card on
 * category archives (`.vance-cat-promo`, grid, 5 layouts, eyebrow, a CTA that
 * can open a tool in the modal, no colour controls at all). They shared nothing
 * — not markup, CSS, settings or renderer.
 *
 * They are now one block. The CATEGORY design is the source of truth: its
 * markup, its class family, its five layouts, its eyebrow and its tool-modal
 * CTA. What the homepage block had that the category one lacked — band and card
 * background colours, text colour, the border controls and the width switch —
 * is folded in as optional styling available to all three.
 *
 * Instances and their setting keys:
 *   Homepage       vance_promo_*              (prefix)
 *   Knowledgebase  vance_kbpromo_*            (prefix)
 *   Categories     vance_cat_promo_*_{termId} (term-suffixed)
 *
 * Addressing differs between them — one is a prefix, the other a suffix — so an
 * instance is described by a KEY CLOSURE mapping a logical field name to its
 * setting id, not by a prefix string. The same closure is handed to the
 * Customizer registrar in functions.php, which is what makes it impossible for
 * the controls and the front end to address different settings.
 *
 * No values were migrated. The prefix instances keep their historical
 * `button_text` / `button_link` names behind the logical `cta_label` / `link`
 * fields, and their old layout values (left/right/top) are normalised on read.
 * Every field the category block never had defaults to empty, so a category
 * promo renders byte-identically to how it did before this merge.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The five layouts, taken from the category block. 'image_left' is the
 * historical default and stays it.
 */
function vance_promo_layout_choices() {
	return array(
		'image_left'  => __( 'Image left, text right',            'vance-health-hub' ),
		'image_right' => __( 'Image right, text left',            'vance-health-hub' ),
		'stacked'     => __( 'Image on top, text below',          'vance-health-hub' ),
		'banner'      => __( 'Full-width banner (text on image)', 'vance-health-hub' ),
		'text'        => __( 'Text only (compact strip)',         'vance-health-hub' ),
	);
}

/** Back-compat alias — functions.php and older call sites use this name. */
function vance_cat_promo_layout_choices() {
	return vance_promo_layout_choices();
}

/** CTA targets that open in the unified glass tool modal (inc/tool-modal.php). */
function vance_promo_tool_choices() {
	return array(
		''                        => __( 'Link to a custom URL', 'vance-health-hub' ),
		'ibd-recipes'             => __( 'Open: Recipes & Meal Planner', 'vance-health-hub' ),
		'malnutrition-calculator' => __( 'Open: Malnutrition Screener', 'vance-health-hub' ),
		'healthcare-quiz'         => __( 'Open: Gastro Health Survey', 'vance-health-hub' ),
	);
}

/** Where each tool lives, for the href behind the modal trigger. */
function vance_promo_tool_urls() {
	return array(
		'ibd-recipes'             => home_url( '/gastro-meal-planner/' ),
		'malnutrition-calculator' => home_url( '/malnutrition-calculator/' ),
		'healthcare-quiz'         => home_url( '/gastro-health-survey/' ),
	);
}

/**
 * Where the block may sit on the Knowledgebase page.
 *
 * Shared with inc/prime-block.php, whose Knowledgebase instance uses the same
 * slots. Not the archive's vance_prime_block_placement_choices().
 */
function vance_kb_page_placement_choices() {
	return array(
		'below_hero'   => __( 'Below the hero, above the intro', 'vance-health-hub' ),
		'below_intro'  => __( 'Below the intro, above the category blocks', 'vance-health-hub' ),
		'above_footer' => __( 'Above the footer (end of the page)', 'vance-health-hub' ),
	);
}

/**
 * Key closure for a prefix-addressed instance (homepage, Knowledgebase).
 *
 * cta_label and link are deliberately mapped onto the historical button_text /
 * button_link names. Renaming them would have orphaned every saved value on the
 * live homepage for no gain — the logical name is what the code reads, the
 * stored name is what the database already has.
 *
 * @param string $prefix e.g. 'vance_promo_'.
 * @return callable fn(string $field): string
 */
function vance_promo_keys_prefixed( $prefix ) {
	$legacy = array(
		'cta_label' => 'button_text',
		'link'      => 'button_link',
	);
	return function ( $field ) use ( $prefix, $legacy ) {
		return $prefix . ( isset( $legacy[ $field ] ) ? $legacy[ $field ] : $field );
	};
}

/**
 * Key closure for a term-addressed instance (category archives).
 *
 * @param int $term_id
 * @return callable fn(string $field): string
 */
function vance_promo_keys_term( $term_id ) {
	$term_id = (int) $term_id;
	return function ( $field ) use ( $term_id ) {
		return 'vance_cat_promo_' . $field . '_' . $term_id;
	};
}

/**
 * Accept the homepage block's old layout values.
 *
 * It offered "Image Position: Left / Right / Top", which mean exactly what
 * image_left / image_right / stacked mean. Normalising on read means the live
 * homepage keeps rendering the way it was configured without anyone editing a
 * setting, and the sanitizer below accepts both spellings so a stored legacy
 * value is never rejected either.
 */
function vance_promo_normalise_layout( $layout ) {
	$legacy = array( 'left' => 'image_left', 'right' => 'image_right', 'top' => 'stacked' );
	$layout = (string) $layout;
	if ( isset( $legacy[ $layout ] ) ) {
		$layout = $legacy[ $layout ];
	}
	return array_key_exists( $layout, vance_promo_layout_choices() ) ? $layout : 'image_left';
}

/** Sanitizer for the layout select — accepts legacy and current spellings. */
function vance_promo_sanitize_layout( $value ) {
	return vance_promo_normalise_layout( $value );
}

/**
 * Resolve one instance's settings.
 *
 * Reads through vance_get_theme_mod() for the prefix instances (they may have
 * legacy sla_* twins) and core get_theme_mod() semantics for everything else —
 * vance_get_theme_mod falls through to the same place, so one call is correct
 * for both.
 *
 * @param callable $key Field-name to setting-id mapper.
 * @return array
 */
function vance_promo_block_vals( $key ) {
	return array(
		'layout'       => vance_promo_normalise_layout( vance_get_theme_mod( $key( 'layout' ), 'image_left' ) ),
		'eyebrow'      => trim( (string) vance_get_theme_mod( $key( 'eyebrow' ), '' ) ),
		'heading'      => trim( (string) vance_get_theme_mod( $key( 'heading' ), '' ) ),
		'text'         => trim( (string) vance_get_theme_mod( $key( 'text' ), '' ) ),
		'image'        => trim( (string) vance_get_theme_mod( $key( 'image' ), '' ) ),
		'cta_label'    => trim( (string) vance_get_theme_mod( $key( 'cta_label' ), 'Explore' ) ),
		'tool'         => trim( (string) vance_get_theme_mod( $key( 'tool' ), '' ) ),
		'link'         => trim( (string) vance_get_theme_mod( $key( 'link' ), '' ) ),
		// Styling. All default empty/false so a category promo, which never had
		// any of these, emits exactly the markup it emitted before the merge.
		'band_bg'      => trim( (string) vance_get_theme_mod( $key( 'bg_color' ), '' ) ),
		'text_color'   => trim( (string) vance_get_theme_mod( $key( 'text_color' ), '' ) ),
		'card_bg'      => trim( (string) vance_get_theme_mod( $key( 'container_bg_color' ), '' ) ),
		'border_on'    => (bool) vance_get_theme_mod( $key( 'border_enable' ), false ),
		'border_width' => absint( vance_get_theme_mod( $key( 'border_width' ), 1 ) ),
		'border_style' => (string) vance_get_theme_mod( $key( 'border_style' ), 'solid' ),
		'border_color' => (string) vance_get_theme_mod( $key( 'border_color' ), '#e2e8f0' ),
		'border_scope' => (string) vance_get_theme_mod( $key( 'border_scope' ), 'container' ),
		'width'        => (string) vance_get_theme_mod( $key( 'width' ), 'container' ),
	);
}

/**
 * Render one Promo Block from a resolved values array.
 *
 * Deliberately reads no theme mods itself — the wrappers below own that, which
 * is what lets one function serve a prefix-addressed instance and a
 * term-addressed one.
 *
 * @param array $vals Output of vance_promo_block_vals().
 */
function vance_render_promo_block( array $vals ) {
	$layout  = $vals['layout'];
	$eyebrow = $vals['eyebrow'];
	$heading = $vals['heading'];
	$text    = $vals['text'];
	$image   = $vals['image'];
	$cta     = $vals['cta_label'];
	$tool    = $vals['tool'];
	$link    = $vals['link'];

	// Nothing meaningful to show — bail rather than render an empty card.
	// Checked BEFORE the text-only layout drops the image, so a block set to
	// "Text only" with an image but no copy still bails instead of rendering an
	// empty strip.
	if ( '' === $heading && '' === $text && '' === $image ) {
		return;
	}

	// "Text only" ignores whatever image is saved rather than deleting it, so
	// switching back to an image layout restores the previous picture.
	if ( 'text' === $layout ) {
		$image = '';
	}

	// Resolve the CTA target. A tool selection opens the unified modal; anything
	// else falls back to the custom link.
	$tool_urls = vance_promo_tool_urls();
	$href      = '';
	$data_attr = '';
	if ( isset( $tool_urls[ $tool ] ) ) {
		$href      = $tool_urls[ $tool ];
		$data_attr = ' data-vance-tool-open="' . esc_attr( $tool ) . '"';
	} elseif ( '' !== $link ) {
		$href = $link;
	}
	$has_cta = ( '' !== $cta && '' !== $href );

	// -- Optional styling -------------------------------------------------
	// Border declaration, scoped to the full-bleed band or the inner card.
	$border_decl = '';
	if ( $vals['border_on'] && $vals['border_width'] > 0 ) {
		$style = in_array( $vals['border_style'], array( 'solid', 'dashed', 'dotted', 'double' ), true )
			? $vals['border_style']
			: 'solid';
		$border_decl = 'border:' . $vals['border_width'] . 'px ' . $style . ' ' . $vals['border_color'] . ';';
	}
	$border_band = ( 'full' === $vals['border_scope'] ) ? $border_decl : '';
	$border_card = ( 'full' !== $vals['border_scope'] ) ? $border_decl : '';

	// background-COLOR, not the background shorthand: the banner layout paints a
	// scrim and photo onto the same element via background-image, and the
	// shorthand would wipe it out.
	$band_style = '';
	if ( '' !== $vals['band_bg'] )    { $band_style .= 'background-color:' . $vals['band_bg'] . ';'; }
	if ( '' !== $vals['text_color'] ) { $band_style .= 'color:' . $vals['text_color'] . ';'; }
	$band_style .= $border_band;

	// The banner layout paints the image onto the card itself under a dark
	// scrim, rather than giving it a column of its own — so the copy sits over
	// the photo. The scrim is deliberately heavy on the text side (0.92) so
	// white type clears 4.5:1 against any photograph, and thins out towards the
	// right where there is no text.
	$inner_classes = 'vance-cat-promo__inner vance-glass vance-glass--interactive vance-cat-promo__inner--' . $layout;
	if ( '' !== $image ) {
		$inner_classes .= ' has-image';
	}
	$inner_style = '';
	if ( 'banner' === $layout && '' !== $image ) {
		$inner_style = "background-image: linear-gradient(90deg, rgba(10,25,41,0.92) 0%, rgba(10,25,41,0.78) 55%, rgba(10,25,41,0.45) 100%), url('" . esc_url( $image ) . "');";
	}
	if ( '' !== $vals['card_bg'] ) { $inner_style .= 'background-color:' . $vals['card_bg'] . ';'; }
	$inner_style .= $border_card;

	$container_class = ( 'full' === $vals['width'] ) ? 'container-fluid' : 'container';
	?>
    <section class="vance-cat-promo" aria-label="<?php echo esc_attr( $heading ? $heading : 'Featured' ); ?>"<?php echo $band_style ? ' style="' . esc_attr( $band_style ) . '"' : ''; ?>>
        <div class="<?php echo esc_attr( $container_class ); ?>">
            <div class="<?php echo esc_attr( $inner_classes ); ?>"<?php echo $inner_style ? ' style="' . esc_attr( $inner_style ) . '"' : ''; ?>>
                <?php if ( $image && 'banner' !== $layout ) : ?>
                    <div class="vance-cat-promo__media" style="background-image:url('<?php echo esc_url( $image ); ?>');" role="img" aria-label="<?php echo esc_attr( $heading ); ?>"></div>
                <?php endif; ?>
                <div class="vance-cat-promo__body">
                    <?php if ( $eyebrow ) : ?><span class="vance-cat-promo__eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
                    <?php if ( $heading ) : ?><h2 class="vance-cat-promo__title"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
                    <?php if ( $text ) : ?><p class="vance-cat-promo__text"><?php echo esc_html( $text ); ?></p><?php endif; ?>
                    <?php if ( $has_cta ) : ?>
                        <a class="vance-btn-inverted" href="<?php echo esc_url( $href ); ?>"<?php echo $data_attr; // phpcs:ignore ?>><?php echo esc_html( $cta ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
	<?php
}

/**
 * Homepage instance. Called from front-page.php's `case 'promo'`; visibility is
 * the vance_promo_show checkbox and position comes from Homepage → Section
 * Order, both exactly as before.
 */
function vance_render_promo_home() {
	if ( ! vance_get_theme_mod( 'vance_promo_show', false ) ) {
		return;
	}
	vance_render_promo_block( vance_promo_block_vals( vance_promo_keys_prefixed( 'vance_promo_' ) ) );
}

/**
 * Knowledgebase instance. The template calls this once per placement slot; it
 * renders in the slot matching its "Position on the page" setting and bails in
 * the others — the same pattern vance_render_prime_block_categories() uses.
 *
 * @param string $slot See vance_kb_page_placement_choices().
 */
function vance_render_promo_knowledgebase( $slot = 'below_intro' ) {
	if ( ! vance_get_theme_mod( 'vance_kbpromo_show', false ) ) {
		return;
	}
	$placement = vance_get_theme_mod( 'vance_kbpromo_placement', 'below_intro' );
	if ( ! array_key_exists( $placement, vance_kb_page_placement_choices() ) ) {
		$placement = 'below_intro';
	}
	if ( $placement !== $slot ) {
		return;
	}
	vance_render_promo_block( vance_promo_block_vals( vance_promo_keys_prefixed( 'vance_kbpromo_' ) ) );
}

/**
 * Category archive instance. One configuration per term, so the block can say
 * something different on each category page.
 *
 * Keeps its original function name because archive.php,
 * category-content-healthcare-news.php and
 * template-parts/subcategory-grouped-archive.php all call it.
 *
 * @param int $term_id
 */
function vance_render_category_promo( $term_id ) {
	$term_id = (int) $term_id;
	if ( ! $term_id ) {
		return;
	}
	if ( ! vance_get_theme_mod( 'vance_cat_promo_show_' . $term_id, false ) ) {
		return;
	}
	vance_render_promo_block( vance_promo_block_vals( vance_promo_keys_term( $term_id ) ) );
}
