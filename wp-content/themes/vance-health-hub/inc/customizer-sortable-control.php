<?php
/**
 * Sortable Sections — Customizer control + registry.
 *
 * Custom WP_Customize_Control that renders a drag-and-drop sortable list of
 * homepage sections (each with a 'show' checkbox + a drag handle). The setting
 * stores a comma-separated list of CHECKED section IDs in display order, so
 * the front-page.php switch loop can read it unchanged.
 *
 * Also exposes vance_get_available_sections() — the single source of truth for
 * what can be put on the homepage. Phase 1 returns just the homepage-native
 * sections; Phase 2 will extend it to include cross-page named blocks.
 *
 * @package vance-health-hub
 * @since   2026-05-25 (post-rollback reconciliation)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The registry. Returns array<id => array{ label, group }>.
 *
 *   - 'id'    matches the case-string in front-page.php's switch
 *   - 'label' is what the admin sees in the Customizer
 *   - 'group' lets the UI group sections (Homepage Native vs cross-page);
 *             Phase 2 will populate the 'patients' / 'hcp' / 'evidence' /
 *             'tools' groups.
 */
function vance_get_available_sections() {
	$sections = array(
		// Homepage-native — these have a matching `case 'X':` in front-page.php.
		'hero'              => array( 'label' => 'Hero',                                   'group' => 'Homepage' ),
		// 'pathway' (Who Am I? tiles) and 'pathway_content' were retired
		// 2026-08-21. Pathway Content became the registry-driven
		// 'prime-block-home-1' (registered in inc/prime-block.php); saved
		// orders still naming 'pathway_content' are rewritten to it on read
		// by front-page.php.
		'promo'             => array( 'label' => 'Promo Block',                            'group' => 'Homepage' ),
		'cats'              => array( 'label' => 'Category Cards',                         'group' => 'Homepage' ),
		'discovery'         => array( 'label' => 'Discovery Suite',                        'group' => 'Homepage' ),
		'join'              => array( 'label' => 'Join the Community',                     'group' => 'Homepage' ),
		'kb'                => array( 'label' => 'Knowledge Base Mini-Hero',               'group' => 'Homepage' ),
		'kb-content'        => array( 'label' => 'Knowledge Base Content (category blocks)', 'group' => 'Homepage' ),
		'testimonials'      => array( 'label' => 'Testimonials',                           'group' => 'Homepage' ),
	);

	/**
	 * Filter the homepage section registry. Phase 2 hooks here to add cross-page
	 * named blocks (patients-benefits, hcp-resources, evidence-pillars, etc.).
	 *
	 * @param array $sections array<id => array{label, group}>
	 */
	return apply_filters( 'vance_homepage_sections', $sections );
}

/**
 * Rewrite a saved section list for the sections retired on 2026-08-21.
 *
 * 'pathway' (the Who Am I? tiles) is gone with no replacement; 'pathway_content'
 * became the registry-driven 'prime-block-home-1'. Substituting in place keeps
 * the admin's chosen position for the block that survived.
 *
 * Pure — front-page.php owns the decision to persist the result.
 *
 * @param array $sections Section IDs in display order.
 * @return array Rewritten list (deduped).
 */
function vance_migrate_retired_pathway_sections( array $sections ) {
	$out = array();
	foreach ( $sections as $sid ) {
		if ( $sid === 'pathway' ) {
			continue; // retired with no replacement
		}
		if ( $sid === 'pathway_content' ) {
			$sid = 'prime-block-home-1';
		}
		if ( ! in_array( $sid, $out, true ) ) {
			$out[] = $sid;
		}
	}
	return $out;
}

/**
 * Append any Content Widget that is switched on but missing from the saved
 * section order.
 *
 * Each widget now has its own "Show this widget" checkbox. Before that,
 * visibility was governed ONLY by whether the admin had also ticked the widget
 * in the separate Section Order screen — so a widget someone had filled in but
 * never added there silently rendered nothing.
 *
 * The test is deliberately narrower than "show is true": `show` DEFAULTS to
 * true, so appending on that alone would drop all five widgets onto every site
 * that has never configured any of them, each showing six arbitrary posts. A
 * widget is therefore only auto-added when it also carries authored copy (a
 * heading or a subtitle) — the signal that somebody actually set it up and
 * expected to see it.
 *
 * Section Order stays fully authoritative for anything already listed there;
 * this only fills in instances it never mentioned.
 *
 * @param array $sections Section IDs in display order.
 * @return array
 */
function vance_append_enabled_content_widgets( array $sections ) {
	if ( ! defined( 'VANCE_CONTENT_WIDGET_INSTANCES' ) ) {
		return $sections;
	}
	for ( $i = 1; $i <= VANCE_CONTENT_WIDGET_INSTANCES; $i++ ) {
		$id = 'content-widget-' . $i;
		if ( in_array( $id, $sections, true ) ) {
			continue;
		}
		if ( ! vance_get_theme_mod( 'vance_cw' . $i . '_show', true ) ) {
			continue;
		}
		$configured = trim( (string) vance_get_theme_mod( 'vance_cw' . $i . '_heading', '' ) ) !== ''
		           || trim( (string) vance_get_theme_mod( 'vance_cw' . $i . '_subtitle', '' ) ) !== '';
		if ( $configured ) {
			$sections[] = $id;
		}
	}
	return $sections;
}

/**
 * The Customizer control. Lazy-loaded inside customize_register.
 */
if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'Vance_Customize_Sortable_Sections_Control' ) ) {

	class Vance_Customize_Sortable_Sections_Control extends WP_Customize_Control {

		public $type = 'vance_sortable_sections';

		/**
		 * Available sections registry. Passed by the registrar; defaults to the
		 * filter-driven vance_get_available_sections() if left empty.
		 *
		 * @var array<id => array{label, group}>
		 */
		public $available = array();

		public function enqueue() {
			$ver = wp_get_theme()->get( 'Version' );
			wp_enqueue_script(
				'vance-customizer-sortable',
				get_template_directory_uri() . '/assets/js/customizer-sortable.js',
				array( 'jquery', 'jquery-ui-sortable', 'customize-controls' ),
				$ver,
				true
			);
			wp_enqueue_style(
				'vance-customizer-sortable',
				get_template_directory_uri() . '/assets/css/customizer-sortable.css',
				array(),
				$ver
			);
		}

		public function render_content() {
			$available = ! empty( $this->available ) ? $this->available : vance_get_available_sections();
			$value     = trim( (string) $this->value() );
			$checked   = $value === '' ? array() : array_filter( array_map( 'trim', explode( ',', $value ) ) );

			// 1. Build the ordered list:
			//    a) items already in the saved value, in saved order, marked checked.
			//    b) items NOT in the saved value, appended in group order, marked unchecked.
			$ordered = array();
			foreach ( $checked as $id ) {
				if ( isset( $available[ $id ] ) ) {
					$ordered[ $id ] = array_merge( $available[ $id ], array( 'show' => true ) );
				}
			}
			foreach ( $available as $id => $meta ) {
				if ( ! isset( $ordered[ $id ] ) ) {
					$ordered[ $id ] = array_merge( $meta, array( 'show' => false ) );
				}
			}
			?>
			<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
			<?php endif; ?>

			<ul class="vance-sortable-sections" data-control-id="<?php echo esc_attr( $this->id ); ?>">
				<?php foreach ( $ordered as $id => $meta ) : ?>
					<li class="vance-sortable-item <?php echo $meta['show'] ? 'is-visible' : 'is-hidden'; ?>" data-section-id="<?php echo esc_attr( $id ); ?>">
						<span class="vance-sortable-handle" aria-hidden="true">&#x2630;</span>
						<label class="vance-sortable-label">
							<input type="checkbox" class="vance-sortable-checkbox" value="<?php echo esc_attr( $id ); ?>" <?php checked( $meta['show'] ); ?> />
							<span class="vance-sortable-name"><?php echo esc_html( $meta['label'] ); ?></span>
							<span class="vance-sortable-group"><?php echo esc_html( $meta['group'] ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<input
				type="hidden"
				class="vance-sortable-value"
				<?php $this->link(); ?>
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<?php
		}
	}
}

/**
 * Sanitize the comma-separated section list against the registry.
 * Drops anything not registered (defensive — Customizer JS shouldn't ever
 * submit unknown IDs, but a hand-crafted POST might).
 */
function vance_sanitize_sortable_sections( $input ) {
	if ( ! is_string( $input ) || $input === '' ) {
		return '';
	}
	$available = vance_get_available_sections();
	$ids       = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $input ) ) ) );
	$ids       = array_values( array_intersect( $ids, array_keys( $available ) ) );
	return implode( ',', $ids );
}
