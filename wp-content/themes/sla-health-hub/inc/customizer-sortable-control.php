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
 * @package sla-health-hub
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
		'pathway'           => array( 'label' => 'Pathway Tiles (Who Am I?)',              'group' => 'Homepage' ),
		'pathway_content'   => array( 'label' => 'Pathway Content (Featured Tools)',       'group' => 'Homepage' ),
		'promo'             => array( 'label' => 'Promo Block',                            'group' => 'Homepage' ),
		'cats'              => array( 'label' => 'Category Cards',                         'group' => 'Homepage' ),
		'discovery'         => array( 'label' => 'Discovery Suite',                        'group' => 'Homepage' ),
		'join'              => array( 'label' => 'Join the Community',                     'group' => 'Homepage' ),
		'kb'                => array( 'label' => 'Knowledge Base Mini-Hero',               'group' => 'Homepage' ),
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
