<?php
/**
 * Front-end integration: asset enqueue gate, content wrapper, config payload.
 *
 * Nothing here ever outputs for visitors who fail the gate — logged-out HTML
 * stays byte-identical (page caches for anonymous users are clean by
 * construction).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VHH_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		// Whole-page wrap (nav, sidebar, main content, footer) instead of just
		// the_content — covers custom page templates (e.g. the GI Health hub)
		// that build their markup by hand and never call the_content() at all.
		// wp_body_open()/wp_footer() are core WP hooks the theme already calls
		// (header.php:22, footer.php:335) — zero theme edits needed.
		add_action( 'wp_body_open', array( __CLASS__, 'open_wrap' ) );
		add_action( 'wp_footer', array( __CLASS__, 'close_wrap' ), 9999 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/** The single gate every front-end surface checks. */
	public static function gate() {
		if ( ! VHH_Plugin::enabled() ) {
			return false;
		}
		// Any logged-in user can see and use the comment panel (collaborative
		// review). Logged-out visitors still get zero annotation output.
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! $post instanceof WP_Post || ! VHH_Plugin::post_type_allowed( $post->post_type ) || 'publish' !== $post->post_status ) {
				return false;
			}
			return is_user_logged_in();
		}
		// Listing views have no WP_Post of their own — get_queried_object()
		// returns null (a "latest posts" home) or a WP_Term (a category) — so
		// only whole-card component comments make sense here; each card
		// attributes to ITS OWN linked post via a client-supplied post ID
		// (card-annotator.js), never to this page. is_home() is included
		// alongside is_front_page() because which one is true for "the
		// homepage" flips depending on the site's Reading settings (a
		// "latest posts" home vs. a static front page) — covering both keeps
		// this correct either way. Other listing views (search, author,
		// date archives) stay excluded — not requested.
		if ( is_front_page() || is_home() || is_category() ) {
			return is_user_logged_in();
		}
		return false;
	}

	public static function body_class( $classes ) {
		if ( self::gate() ) {
			$classes[] = 'vhh-annotations-on';
			if ( 'hide' === VHH_Plugin::get( 'resolved_style' ) ) {
				$classes[] = 'vhh-resolved-hide';
			}
		}
		return $classes;
	}

	public static function open_wrap() {
		if ( self::gate() ) {
			echo '<div data-vhh-annotatable="1">';
		}
	}

	public static function close_wrap() {
		if ( self::gate() ) {
			echo '</div>';
		}
	}

	public static function enqueue() {
		if ( ! self::gate() ) {
			return;
		}

		$post = get_queried_object();
		// The homepage has no WP_Post of its own (show_on_front=posts on
		// this site) — give it its dedicated feedback bucket instead of the
		// 0/null postId that only allows card-level comments.
		if ( ! $post instanceof WP_Post && ( is_front_page() || is_home() ) ) {
			$id   = VHH_Site_Feedback::homepage_post_id();
			$post = $id ? get_post( $id ) : null;
		}
		$css = VHH_ANN_DIR . 'assets/css/annotations.css';

		wp_enqueue_style(
			'vhh-annotations',
			VHH_ANN_URL . 'assets/css/annotations.css',
			array(),
			VHH_ANN_VERSION . '-' . ( @filemtime( $css ) ?: '1' )
		);

		$scripts = array(
			'vhh-api'       => array( 'assets/js/api.js', array() ),
			'vhh-anchoring' => array( 'assets/js/anchoring.js', array( 'vhh-api' ) ),
			'vhh-annotator' => array( 'assets/js/annotator.js', array( 'vhh-anchoring' ) ),
			'vhh-sidebar'   => array( 'assets/js/sidebar.js', array( 'vhh-annotator' ) ),
		);
		if ( VHH_Plugin::get( 'image_annotation' ) ) {
			$scripts['vhh-image'] = array( 'assets/js/image-annotator.js', array( 'vhh-annotator' ) );
			$scripts['vhh-card']  = array( 'assets/js/card-annotator.js', array( 'vhh-annotator' ) );
		}
		if ( VHH_Plugin::get( 'insertion_annotation' ) ) {
			$scripts['vhh-insertion'] = array( 'assets/js/insertion-annotator.js', array( 'vhh-annotator' ) );
		}
		foreach ( $scripts as $handle => $def ) {
			wp_enqueue_script(
				$handle,
				VHH_ANN_URL . $def[0],
				$def[1],
				VHH_ANN_VERSION . '-' . ( @filemtime( VHH_ANN_DIR . $def[0] ) ?: '1' ),
				true
			);
		}

		wp_localize_script( 'vhh-api', 'VHH_CFG', self::client_config( $post ) );
	}

	/**
	 * The VHH_CFG payload consumed by all vhh JS. Single source of truth —
	 * the email-review template reuses it with overrides (token, anonymous
	 * user) so the two surfaces can never drift.
	 *
	 * @param WP_Post|WP_Term|null $post      Annotated post, or whatever
	 *                                        get_queried_object() returns for
	 *                                        a listing view (null for a
	 *                                        "latest posts" home, WP_Term for
	 *                                        a category) — those only ever
	 *                                        carry card-level comments, which
	 *                                        don't depend on postId.
	 * @param array                 $overrides Deep-merged over the defaults.
	 * @return array
	 */
	public static function client_config( $post, array $overrides = array() ) {
		$config = array(
			'restUrl'  => esc_url_raw( rest_url( 'vhh/v1' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'postId'   => ( $post instanceof WP_Post ) ? $post->ID : 0,
			'settings' => array(
				'highlightColor'   => (string) VHH_Plugin::get( 'highlight_color' ),
				'resolvedStyle'    => (string) VHH_Plugin::get( 'resolved_style' ),
				'sidebarPosition'  => (string) VHH_Plugin::get( 'sidebar_position' ),
				'imageEnabled'     => (bool) VHH_Plugin::get( 'image_annotation' ),
				'insertionEnabled' => (bool) VHH_Plugin::get( 'insertion_annotation' ),
			),
			'user'     => array(
				'id'          => get_current_user_id(),
				'name'        => wp_get_current_user()->display_name,
				'canModerate' => VHH_Plugin::user_can_moderate(),
			),
			'i18n'     => array(
				'addComment'    => __( 'Add comment', 'vhh-annotations' ),
				'overall'       => __( 'Overall feedback', 'vhh-annotations' ),
				'inlineNotes'   => __( 'Inline notes', 'vhh-annotations' ),
				'otherPages'    => __( 'Other pages', 'vhh-annotations' ),
				'resolve'       => __( 'Resolve', 'vhh-annotations' ),
				'unresolve'     => __( 'Unresolve', 'vhh-annotations' ),
				'delete'        => __( 'Delete', 'vhh-annotations' ),
				'save'          => __( 'Save', 'vhh-annotations' ),
				'cancel'        => __( 'Cancel', 'vhh-annotations' ),
				'orphan'        => __( 'Text changed since this note', 'vhh-annotations' ),
				'saveFailed'    => __( 'Could not save — try again.', 'vhh-annotations' ),
				'wholeImage'    => __( 'Comment on whole image', 'vhh-annotations' ),
				'imageNote'     => __( 'Image note', 'vhh-annotations' ),
				'insertHere'    => __( 'Insert comment here', 'vhh-annotations' ),
				'insertionNote' => __( 'Insertion point', 'vhh-annotations' ),
				'comments'      => __( 'Comments', 'vhh-annotations' ),
				'confirmDelete' => __( 'Delete this note?', 'vhh-annotations' ),
				'done'          => __( 'Done', 'vhh-annotations' ),
				'markDone'      => __( 'Mark done', 'vhh-annotations' ),
				'reopen'        => __( 'Reopen', 'vhh-annotations' ),
				'reply'         => __( 'Reply', 'vhh-annotations' ),
				'replyPlaceholder' => __( 'Write a reply…', 'vhh-annotations' ),
				'send'          => __( 'Send', 'vhh-annotations' ),
			),
		);
		foreach ( $overrides as $key => $value ) {
			$config[ $key ] = is_array( $value ) && isset( $config[ $key ] ) && is_array( $config[ $key ] )
				? array_merge( $config[ $key ], $value )
				: $value;
		}
		return $config;
	}
}
