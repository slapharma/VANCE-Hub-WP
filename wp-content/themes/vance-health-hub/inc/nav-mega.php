<?php
/**
 * Mega-menu panel support for the primary navigation.
 *
 * The primary menu is rendered by Max Mega Menu 3.10.5 (free edition). The link
 * structure of each panel is built in Appearance → Menus with the plugin's own
 * grid builder — no code involved, and the client can rearrange it. This file
 * supplies the three things the plugin cannot:
 *
 *   1. assets/css/nav-mega.css — the panel interior styling.
 *   2. Three widgets the plugin can drop into a grid cell:
 *        VHH_Nav_Tiles_Widget     icon tiles (the Conditions panel)
 *        VHH_Nav_CTA_Widget       a call-to-action rail
 *        VHH_Nav_Featured_Widget  the latest posts from a category, live
 *   3. An inline icon set for the tiles.
 *
 * Max Mega Menu registers a hidden sidebar per menu item and lists CLASSIC
 * widgets in its picker, which is why these are WP_Widget subclasses rather
 * than blocks. They are registered unconditionally: the plugin is what makes
 * them reachable, but a widget that exists and is never placed costs nothing,
 * whereas a plugin-detection check that guesses wrong would leave the panels
 * unstyled with no visible error.
 *
 * See docs/MEGA-MENU-SETUP.md in the repo root (not deployed) for the admin-side
 * build: menu tree, per-item settings, and the values to paste into each widget.
 *
 * @package vance-health-hub
 * @since   2026-08-28
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/* =============================================================================
   ASSETS
   ========================================================================== */

/**
 * Enqueue the panel stylesheet.
 *
 * Priority 20 so it prints after vance_health_hub_scripts() (priority 10) has
 * queued main.css and the two mobile sheets. The declared dependency is
 * vance-main-style only: that handle is always registered, whereas depending on
 * a handle that might not be enqueued in some context would make WordPress drop
 * this stylesheet silently. Load order against the mobile sheets does not
 * matter here — they pin h1/h2/p with !important, and nothing in nav-mega.css
 * styles those elements (panel headings are deliberately <a> and <span>).
 *
 * Cache-busted on filemtime, matching main.css, so every edit busts itself.
 */
function vance_nav_mega_assets() {
	$path = get_template_directory() . '/assets/css/nav-mega.css';

	wp_enqueue_style(
		'vance-nav-mega',
		get_template_directory_uri() . '/assets/css/nav-mega.css',
		array( 'vance-main-style' ),
		@filemtime( $path ) ?: '1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'vance_nav_mega_assets', 20 );


/* =============================================================================
   ICONS
   ========================================================================== */

/**
 * Inline icon set for the nav tiles and CTA buttons.
 *
 * Lucide geometry, 24×24, stroked with currentColor — the same house style as
 * header.php, inc/hero-spotlight.php and vance_about_icon() in page-about.php.
 *
 * Deliberately NOT reusing assets/img/icons/*.svg: those are pre-rebrand files
 * with fill="#FF5A00" baked in, so they render orange wherever they are used as
 * an <img>. And deliberately not calling vance_about_icon(): that lives in a
 * page template, so it only exists while /about-us/ is rendering.
 *
 * @param string $name  Icon slug. Unknown slugs return an empty string.
 * @param int    $size  Pixel size for width/height.
 * @return string SVG markup, or '' when the slug is unknown.
 */
function vance_nav_icon( $name, $size = 20 ) {
	$paths = array(
		'sparkles'    => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/>',
		'organ'       => '<path d="M8 3v4a4 4 0 0 0 4 4 4 4 0 0 1 4 4v6"/><path d="M8 21c-2.5 0-4-1.6-4-3.6S5.5 14 8 14"/>',
		'pulse'       => '<path d="M2.5 12h4l2-5 3.5 11 2.5-6h7"/>',
		'drop'        => '<path d="M12 3s6 6.4 6 10.6A6 6 0 0 1 6 13.6C6 9.4 12 3 12 3z"/>',
		'flask'       => '<path d="M10 3v6l-5.4 9a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L14 9V3"/><path d="M9 3h6M7.5 14h9"/>',
		'clipboard'   => '<path d="M9 4h6v3H9z"/><path d="M15 5h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3"/><path d="M9 12h6M9 16h4"/>',
		'ribbon'      => '<circle cx="12" cy="9" r="5.5"/><path d="M9 13.6L6.5 21l5.5-3 5.5 3-2.5-7.4"/>',
		'book'        => '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H19v16H5.5A1.5 1.5 0 0 0 4 20.5z"/><path d="M4 17.5A1.5 1.5 0 0 1 5.5 16H19"/>',
		'quiz'        => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 4 2c-.8.6-1.5 1.1-1.5 2"/><path d="M12 17h.01"/>',
		'calculator'  => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
		'leaf'        => '<path d="M4 20c0-8 6-14 16-15 0 10-5 16-13 16H4z"/><path d="M4 20c4-4 7-6 11-8"/>',
		'shield'      => '<path d="M12 3l7.5 3v5.6c0 4.5-3.1 7.9-7.5 9.4-4.4-1.5-7.5-4.9-7.5-9.4V6z"/><path d="M9 12l2 2 4-4"/>',
		'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16.5 5.4a3.2 3.2 0 0 1 0 5.2M17.5 14.4A6 6 0 0 1 21 20"/>',
		'stethoscope' => '<path d="M11 2v2M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>',
		'play'        => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M10.5 9.2l4.5 2.8-4.5 2.8z"/>',
		'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'note'        => '<path d="M5 3.5h14v17H5z"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/>',
		'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6 8.5-6"/>',
		'map'         => '<path d="M3 6.5l6-2.5 6 2.5 6-2.5v13l-6 2.5-6-2.5-6 2.5z"/><path d="M9 4v13M15 6.5v13"/>',
		'arrow'       => '<path d="M5 12h13M13 6.5l5.5 5.5L13 17.5"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) { return ''; }

	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * Icon slugs as a select-ready list, for the widget admin forms.
 *
 * @return array<string,string> slug => Human Label
 */
function vance_nav_icon_choices() {
	$slugs = array(
		'sparkles', 'organ', 'pulse', 'drop', 'flask', 'clipboard', 'ribbon',
		'book', 'quiz', 'calculator', 'leaf', 'shield', 'users', 'stethoscope',
		'play', 'grid', 'note', 'mail', 'map', 'arrow',
	);

	$out = array( '' => '— none —' );
	foreach ( $slugs as $slug ) {
		$out[ $slug ] = ucfirst( $slug );
	}
	return $out;
}


/* =============================================================================
   WIDGET 1 — ICON TILES
   ========================================================================== */

/**
 * A grid of icon tiles for a mega-menu panel.
 *
 * Tiles are entered one per line as pipe-delimited fields rather than through a
 * repeater UI. A classic widget form has no repeater primitive, and hand-rolling
 * one means shipping admin JS to maintain — for a list that changes maybe twice
 * a year, a documented textarea is the smaller thing to own.
 *
 *   icon | Title | Description | /path/
 *
 * Description may be left empty. Prefixing a line with * marks it as the
 * feature tile (wider, teal icon chip) — used for the "not sure where to
 * start?" tile at the end of the Conditions panel.
 */
class VHH_Nav_Tiles_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'vhh_nav_tiles',
			esc_html__( 'Hub Nav: Icon Tiles', 'vance-health-hub' ),
			array(
				'description' => esc_html__( 'A grid of icon tiles for a mega-menu panel. Collapses to plain links on mobile.', 'vance-health-hub' ),
				'classname'   => 'vance-nav-widget vhh_nav_tiles',
			)
		);
	}

	/**
	 * Parse the textarea into tile rows.
	 *
	 * @param string $raw Newline-separated, pipe-delimited tile definitions.
	 * @return array<int,array{icon:string,title:string,desc:string,url:string,feature:bool}>
	 */
	protected function parse_tiles( $raw ) {
		$tiles = array();

		foreach ( preg_split( '/\R/', (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( $line === '' ) { continue; }

			$feature = false;
			if ( strpos( $line, '*' ) === 0 ) {
				$feature = true;
				$line    = trim( substr( $line, 1 ) );
			}

			$parts = array_map( 'trim', explode( '|', $line ) );

			// A tile with no title or no destination is not a tile.
			if ( empty( $parts[1] ) || empty( $parts[3] ) ) { continue; }

			$tiles[] = array(
				'icon'    => isset( $parts[0] ) ? sanitize_key( $parts[0] ) : '',
				'title'   => $parts[1],
				'desc'    => isset( $parts[2] ) ? $parts[2] : '',
				'url'     => $parts[3],
				'feature' => $feature,
			);
		}

		return $tiles;
	}

	public function widget( $args, $instance ) {
		$tiles = $this->parse_tiles( isset( $instance['tiles'] ) ? $instance['tiles'] : '' );
		if ( empty( $tiles ) ) { return; }

		$cols  = isset( $instance['cols'] ) ? max( 1, min( 4, (int) $instance['cols'] ) ) : 3;
		$title = isset( $instance['title'] ) ? $instance['title'] : '';

		echo $args['before_widget'];

		if ( $title !== '' ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		printf(
			'<div class="vance-nav-tiles" style="--vance-nav-tile-cols:%d">',
			$cols
		);

		foreach ( $tiles as $tile ) {
			$icon = vance_nav_icon( $tile['icon'], 19 );

			printf(
				'<a class="vance-nav-tile%1$s" href="%2$s">',
				$tile['feature'] ? ' vance-nav-tile--feature' : '',
				esc_url( $tile['url'] )
			);

			if ( $icon !== '' ) {
				// $icon is built from an internal path map, never from input.
				echo '<span class="vance-nav-tile__icon">' . $icon . '</span>';
			}

			echo '<span class="vance-nav-tile__text">';
			echo '<span class="vance-nav-tile__title">' . esc_html( $tile['title'] ) . '</span>';
			if ( $tile['desc'] !== '' ) {
				echo '<span class="vance-nav-tile__desc">' . esc_html( $tile['desc'] ) . '</span>';
			}
			echo '</span>';

			echo '</a>';
		}

		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$cols  = isset( $instance['cols'] ) ? (int) $instance['cols'] : 3;
		$tiles = isset( $instance['tiles'] ) ? $instance['tiles'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Heading (optional)', 'vance-health-hub' ); ?>
			</label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cols' ) ); ?>">
				<?php esc_html_e( 'Columns', 'vance-health-hub' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'cols' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'cols' ) ); ?>">
				<?php foreach ( array( 1, 2, 3, 4 ) as $n ) : ?>
					<option value="<?php echo (int) $n; ?>" <?php selected( $cols, $n ); ?>><?php echo (int) $n; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'tiles' ) ); ?>">
				<?php esc_html_e( 'Tiles — one per line', 'vance-health-hub' ); ?>
			</label>
			<textarea class="widefat" rows="9"
				id="<?php echo esc_attr( $this->get_field_id( 'tiles' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'tiles' ) ); ?>"><?php echo esc_textarea( $tiles ); ?></textarea>
			<small>
				<?php esc_html_e( 'Format: icon | Title | Description | /path/ — description may be left empty. Start a line with * to make it the wide feature tile.', 'vance-health-hub' ); ?>
				<br>
				<?php
				printf(
					/* translators: %s: comma-separated list of available icon slugs. */
					esc_html__( 'Icons: %s', 'vance-health-hub' ),
					esc_html( implode( ', ', array_slice( array_keys( vance_nav_icon_choices() ), 1 ) ) )
				);
				?>
			</small>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['cols']  = isset( $new_instance['cols'] ) ? max( 1, min( 4, (int) $new_instance['cols'] ) ) : 3;

		// Kept as raw-ish text: the pipe delimiter and newlines must survive, and
		// every field is escaped again at render. sanitize_textarea_field strips
		// tags and control characters without touching either.
		$instance['tiles'] = isset( $new_instance['tiles'] ) ? sanitize_textarea_field( $new_instance['tiles'] ) : '';

		return $instance;
	}
}


/* =============================================================================
   WIDGET 2 — CTA RAIL
   ========================================================================== */

/**
 * A single call-to-action card for the last column of a mega-menu panel.
 * Hidden below 768px — the drawer already carries every link it points at.
 */
class VHH_Nav_CTA_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'vhh_nav_cta',
			esc_html__( 'Hub Nav: CTA Rail', 'vance-health-hub' ),
			array(
				'description' => esc_html__( 'A teal call-to-action card for a mega-menu panel. Hidden on mobile.', 'vance-health-hub' ),
				'classname'   => 'vance-nav-widget vhh_nav_cta',
			)
		);
	}

	public function widget( $args, $instance ) {
		$heading  = isset( $instance['heading'] ) ? $instance['heading'] : '';
		$btn_text = isset( $instance['btn_text'] ) ? $instance['btn_text'] : '';
		$btn_url  = isset( $instance['btn_url'] ) ? $instance['btn_url'] : '';

		// Nothing to show without a headline and a destination.
		if ( $heading === '' || $btn_text === '' || $btn_url === '' ) { return; }

		$eyebrow = isset( $instance['eyebrow'] ) ? $instance['eyebrow'] : '';
		$text    = isset( $instance['text'] ) ? $instance['text'] : '';
		$icon    = vance_nav_icon( isset( $instance['icon'] ) ? $instance['icon'] : '', 14 );

		echo $args['before_widget'];
		echo '<div class="vance-nav-cta">';

		if ( $eyebrow !== '' ) {
			echo '<span class="vance-nav-cta__eyebrow">' . esc_html( $eyebrow ) . '</span>';
		}

		echo '<span class="vance-nav-cta__title">' . esc_html( $heading ) . '</span>';

		if ( $text !== '' ) {
			echo '<p class="vance-nav-cta__text">' . esc_html( $text ) . '</p>';
		}

		printf( '<a class="vance-nav-cta__btn" href="%s">', esc_url( $btn_url ) );
		if ( $icon !== '' ) { echo $icon; } // internal path map, not input
		echo esc_html( $btn_text );
		echo '</a>';

		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$fields = array(
			'eyebrow'  => array( esc_html__( 'Eyebrow', 'vance-health-hub' ), 'text' ),
			'heading'  => array( esc_html__( 'Headline', 'vance-health-hub' ), 'text' ),
			'text'     => array( esc_html__( 'Supporting line', 'vance-health-hub' ), 'textarea' ),
			'btn_text' => array( esc_html__( 'Button label', 'vance-health-hub' ), 'text' ),
			'btn_url'  => array( esc_html__( 'Button URL', 'vance-health-hub' ), 'text' ),
		);

		foreach ( $fields as $key => $meta ) {
			list( $label, $type ) = $meta;
			$value = isset( $instance[ $key ] ) ? $instance[ $key ] : '';
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php if ( $type === 'textarea' ) : ?>
					<textarea class="widefat" rows="3"
						id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php else : ?>
					<input class="widefat" type="text"
						id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>"
						value="<?php echo esc_attr( $value ); ?>">
				<?php endif; ?>
			</p>
			<?php
		}

		$icon = isset( $instance['icon'] ) ? $instance['icon'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'icon' ) ); ?>">
				<?php esc_html_e( 'Button icon', 'vance-health-hub' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'icon' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'icon' ) ); ?>">
				<?php foreach ( vance_nav_icon_choices() as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $icon, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'eyebrow'  => isset( $new_instance['eyebrow'] ) ? sanitize_text_field( $new_instance['eyebrow'] ) : '',
			'heading'  => isset( $new_instance['heading'] ) ? sanitize_text_field( $new_instance['heading'] ) : '',
			'text'     => isset( $new_instance['text'] ) ? sanitize_textarea_field( $new_instance['text'] ) : '',
			'btn_text' => isset( $new_instance['btn_text'] ) ? sanitize_text_field( $new_instance['btn_text'] ) : '',
			'btn_url'  => isset( $new_instance['btn_url'] ) ? esc_url_raw( $new_instance['btn_url'] ) : '',
			'icon'     => isset( $new_instance['icon'] ) ? sanitize_key( $new_instance['icon'] ) : '',
		);
	}
}


/* =============================================================================
   WIDGET 3 — FEATURED ARTICLES
   ========================================================================== */

/**
 * The most recent posts from a chosen category, as cards.
 *
 * This is the one cell that has to be code: a Custom HTML widget would freeze
 * whichever two articles were current on the day it was pasted, and a menu that
 * advertises last spring's news is worse than a menu with no cards at all.
 *
 * Thumbnails render square (--radius-article), matching every other post tile
 * on the site — see the "Article cards stay square" rule at the end of main.css.
 */
class VHH_Nav_Featured_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'vhh_nav_featured',
			esc_html__( 'Hub Nav: Featured Articles', 'vance-health-hub' ),
			array(
				'description' => esc_html__( 'The latest posts from a category, as cards in a mega-menu panel. Hidden on mobile.', 'vance-health-hub' ),
				'classname'   => 'vance-nav-widget vhh_nav_featured',
			)
		);
	}

	public function widget( $args, $instance ) {
		$count = isset( $instance['count'] ) ? max( 1, min( 4, (int) $instance['count'] ) ) : 2;
		$cat   = isset( $instance['cat'] ) ? (int) $instance['cat'] : 0;

		$query_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			// The menu renders on every page of the site, so this query runs on
			// every page load. Skipping the term and meta caches keeps it to the
			// single posts query it needs to be.
			'update_post_meta_cache' => false,
		);

		if ( $cat > 0 ) {
			$query_args['cat'] = $cat;
		}

		$posts = get_posts( $query_args );
		if ( empty( $posts ) ) { return; }

		$title     = isset( $instance['title'] ) ? $instance['title'] : '';
		$more_text = isset( $instance['more_text'] ) ? $instance['more_text'] : '';
		$more_url  = isset( $instance['more_url'] ) ? $instance['more_url'] : '';

		echo $args['before_widget'];

		if ( $title !== '' ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		printf( '<div class="vance-nav-feature" style="--vance-nav-feature-cols:%d">', min( $count, 2 ) );

		foreach ( $posts as $post ) {
			$cats  = get_the_category( $post->ID );
			$chip  = ! empty( $cats ) ? $cats[0]->name : '';
			$thumb = get_the_post_thumbnail(
				$post->ID,
				'medium',
				array(
					'alt'     => '',
					'loading' => 'lazy',
				)
			);

			printf( '<a class="vance-nav-feature__card" href="%s">', esc_url( get_permalink( $post->ID ) ) );

			echo '<span class="vance-nav-feature__thumb">';
			if ( $chip !== '' ) {
				echo '<span class="vance-nav-feature__chip">' . esc_html( $chip ) . '</span>';
			}
			// get_the_post_thumbnail() returns escaped markup.
			echo $thumb;
			echo '</span>';

			echo '<span class="vance-nav-feature__title">' . esc_html( get_the_title( $post->ID ) ) . '</span>';
			echo '<span class="vance-nav-feature__date">' . esc_html( get_the_date( 'j M Y', $post->ID ) ) . '</span>';

			echo '</a>';
		}

		echo '</div>';

		if ( $more_text !== '' && $more_url !== '' ) {
			printf(
				'<a class="vance-nav-feature__more" href="%s">%s%s</a>',
				esc_url( $more_url ),
				esc_html( $more_text ),
				vance_nav_icon( 'arrow', 15 )
			);
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? $instance['title'] : '';
		$cat       = isset( $instance['cat'] ) ? (int) $instance['cat'] : 0;
		$count     = isset( $instance['count'] ) ? (int) $instance['count'] : 2;
		$more_text = isset( $instance['more_text'] ) ? $instance['more_text'] : '';
		$more_url  = isset( $instance['more_url'] ) ? $instance['more_url'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Heading (optional)', 'vance-health-hub' ); ?>
			</label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cat' ) ); ?>">
				<?php esc_html_e( 'Category', 'vance-health-hub' ); ?>
			</label>
			<?php
			wp_dropdown_categories(
				array(
					'show_option_all' => esc_html__( 'All categories', 'vance-health-hub' ),
					'hide_empty'      => false,
					'selected'        => $cat,
					'name'            => $this->get_field_name( 'cat' ),
					'id'              => $this->get_field_id( 'cat' ),
					'class'           => 'widefat',
				)
			);
			?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
				<?php esc_html_e( 'How many', 'vance-health-hub' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>">
				<?php foreach ( array( 1, 2, 3, 4 ) as $n ) : ?>
					<option value="<?php echo (int) $n; ?>" <?php selected( $count, $n ); ?>><?php echo (int) $n; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'more_text' ) ); ?>">
				<?php esc_html_e( '"See more" label', 'vance-health-hub' ); ?>
			</label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'more_text' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'more_text' ) ); ?>"
				value="<?php echo esc_attr( $more_text ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'more_url' ) ); ?>">
				<?php esc_html_e( '"See more" URL', 'vance-health-hub' ); ?>
			</label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'more_url' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'more_url' ) ); ?>"
				value="<?php echo esc_attr( $more_url ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title'     => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'cat'       => isset( $new_instance['cat'] ) ? (int) $new_instance['cat'] : 0,
			'count'     => isset( $new_instance['count'] ) ? max( 1, min( 4, (int) $new_instance['count'] ) ) : 2,
			'more_text' => isset( $new_instance['more_text'] ) ? sanitize_text_field( $new_instance['more_text'] ) : '',
			'more_url'  => isset( $new_instance['more_url'] ) ? esc_url_raw( $new_instance['more_url'] ) : '',
		);
	}
}


/* =============================================================================
   REGISTRATION
   ========================================================================== */

/**
 * Register the three panel widgets.
 *
 * Max Mega Menu reads the global widget registry when it builds its picker, so
 * nothing further is needed for them to appear in the mega-menu builder.
 */
function vance_nav_mega_register_widgets() {
	register_widget( 'VHH_Nav_Tiles_Widget' );
	register_widget( 'VHH_Nav_CTA_Widget' );
	register_widget( 'VHH_Nav_Featured_Widget' );
}
add_action( 'widgets_init', 'vance_nav_mega_register_widgets' );
