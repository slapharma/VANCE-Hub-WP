<?php
/**
 * Stop shipping assets to visitors who cannot use them.
 *
 * Measured on 2026-09-01: the homepage came to 592&nbsp;KB and a condition page
 * to 929&nbsp;KB. Third-party JavaScript was 268&nbsp;KB of the 338&nbsp;KB
 * total — Google Tag Manager at 170 and the Google sign-in client at 98 — while
 * the theme's own bundles came to a well-behaved 33&nbsp;KB. Two of those loads
 * are avoidable without changing what the site does.
 *
 * 1. Dashicons
 *
 * WordPress's admin icon font, 34.7&nbsp;KB, was being served on every public
 * URL: homepage, articles, condition pages, category archives, recipes. The
 * theme does render dashicon markup, but only inside editor and admin regions —
 * a crawl of all 218 public URLs found zero dashicon elements in what a
 * logged-out visitor receives. It is dequeued for them and left alone for
 * logged-in users, whose admin bar needs it.
 *
 * 2. The Google sign-in client
 *
 * 98&nbsp;KB from accounts.google.com on every page view by a logged-out
 * visitor, to render a button inside a modal that visitor has to open first.
 * Both g_id_onload elements set data-auto_prompt="false", so nothing appears
 * unprompted and nothing needs the library before an interaction.
 *
 * So it is fetched on the first interaction instead, or when the browser goes
 * idle, whichever comes first. The declarative markup stays exactly where it
 * was: Google's client scans the DOM for g_id_onload when it loads, whenever
 * that is, so the button renders the same way — it simply is not in the way of
 * first paint any more. The idle fallback means a visitor who opens the modal
 * without touching anything first still finds it ready.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drop the admin icon font for logged-out visitors.
 *
 * Priority 999 so it runs after everything that might enqueue it.
 *
 * @return void
 */
function vance_dequeue_dashicons_for_visitors() {
	if ( is_user_logged_in() || is_admin() ) {
		return;
	}

	wp_dequeue_style( 'dashicons' );
	wp_deregister_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'vance_dequeue_dashicons_for_visitors', 999 );

/**
 * Take the Google sign-in client off the critical path.
 *
 * Dequeues the handle registered in functions.php and hands the URL to the
 * loader below instead.
 *
 * @return void
 */
function vance_defer_google_signin() {
	if ( is_admin() ) {
		return;
	}

	wp_dequeue_script( 'google-gsi' );
}
add_action( 'wp_enqueue_scripts', 'vance_defer_google_signin', 999 );

/**
 * Fetch the sign-in client on first interaction, or on idle.
 *
 * Deliberately tiny and inline: a separate request to save a request is not a
 * saving, and this has to run before any interaction it wants to catch.
 *
 * @return void
 */
function vance_google_signin_loader() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	// Nothing to initialise if the sign-in markup was never printed.
	if ( ! defined( 'GOOGLE_CLIENT_ID' ) || ! GOOGLE_CLIENT_ID ) {
		return;
	}
	?>
	<script id="vance-gsi-loader">
	(function () {
		var loaded = false;
		var events = ['pointerdown', 'keydown', 'touchstart', 'focusin'];

		function load() {
			if (loaded) { return; }
			loaded = true;

			events.forEach(function (e) {
				window.removeEventListener(e, load, true);
			});

			var s = document.createElement('script');
			s.src = 'https://accounts.google.com/gsi/client';
			s.async = true;
			s.defer = true;
			document.head.appendChild(s);
		}

		events.forEach(function (e) {
			window.addEventListener(e, load, { once: true, capture: true, passive: true });
		});

		// Backstop, so somebody who opens the sign-in modal without having
		// touched anything first still finds a button waiting.
		if ('requestIdleCallback' in window) {
			requestIdleCallback(load, { timeout: 6000 });
		} else {
			window.setTimeout(load, 4000);
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'vance_google_signin_loader', 5 );

/**
 * 3. Lazy-loaded card thumbnails on category archives
 *
 * archive.php and template-parts/subcategory-grouped-archive.php mark card
 * images past the first few with data-bg instead of an inline
 * background-image (audit P5: a grouped archive with pagination off — see
 * inc/functions.php's vance_grouped_archive_no_pagination() — measured at
 * 41,000px and 4.96 MB, every thumbnail loading eagerly). CSS
 * background-image has no native loading="lazy", so this small script swaps
 * data-bg into the real background-image via IntersectionObserver as each
 * card nears the viewport. Only enqueued where those templates render.
 */
function vance_enqueue_lazy_card_images() {
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return;
	}
	wp_enqueue_script(
		'vance-lazy-card-images',
		get_template_directory_uri() . '/assets/js/lazy-card-images.js',
		array(),
		@filemtime( get_template_directory() . '/assets/js/lazy-card-images.js' ) ?: '1',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'vance_enqueue_lazy_card_images' );
