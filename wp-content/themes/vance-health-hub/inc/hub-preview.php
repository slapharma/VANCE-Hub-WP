<?php
/**
 * VanceHubClub preview mode: see the whole club layout across the site without
 * turning any switch on for the public.
 *
 * Every vance_hub_*_active() function asks vance_hub_preview_query() what the
 * request wants. The answer is 'hub', 'classic' or '':
 *
 *   1. ?vhh_layout=hub|classic from a user with edit_theme_options wins. This
 *      is the per-page preview the site already had.
 *   2. Else, when preview mode is on for this request, 'hub'.
 *   3. Else '' (the saved switches decide).
 *
 * Preview mode is on in two cases:
 *
 *   - Admin flag: a user with edit_theme_options turned it on from the
 *     VanceHubClub admin page or the admin bar. Stored per user in the
 *     `_vance_hub_preview` user meta. It stays on until turned off.
 *   - Share link: home_url('/?vhh_preview=<token>'). A valid, unexpired token
 *     sets an HttpOnly cookie that holds an HMAC of the token, then redirects
 *     to the same address without the token. Each request checks the cookie
 *     against the CURRENT token and expiry, so "New link" or "Turn sharing off"
 *     ends every old cookie at once. A bad token does nothing visible.
 *
 * Preview mode changes presentation only. It grants no capability, and member
 * features still need a login: the dashboard checks is_user_logged_in() on its
 * own, whatever this file says.
 *
 * Every preview response is sent with nocache headers, LiteSpeed nocache and
 * noindex. The cookie is also added to the LiteSpeed Cache vary list, so a
 * page cache does not serve a cached classic page to a cookie holder (or a
 * preview page to anyone). See the "Cache" block below for what that needs.
 *
 * The state is decided once per request, on `init` (vance_hub_preview_boot).
 * Until then, and in the test harnesses that never fire `init`, preview mode
 * is off and only rule 1 applies.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * Names and pure logic (no WordPress state)
 * ====================================================================== */

/** User meta key of the admin flag. */
function vance_hub_preview_meta_key() {
	return '_vance_hub_preview';
}

/** Option that holds the share link: array( 'token' => string, 'expires' => int ). */
function vance_hub_preview_option() {
	return 'vance_hub_preview_share';
}

/** Name of the share cookie. Also the name given to LiteSpeed Cache as a vary cookie. */
function vance_hub_preview_cookie_name() {
	return 'vance_hub_preview';
}

/** Days a new share link lives. */
function vance_hub_preview_share_days() {
	return 14;
}

/**
 * Pick the layout the request asks for.
 *
 * @param string $explicit 'hub', 'classic' or '' (the admin's ?vhh_layout=).
 * @param bool   $mode_on  True when preview mode is on for this request.
 * @return string 'hub', 'classic' or ''.
 */
function vance_hub_preview_pick( $explicit, $mode_on ) {
	if ( 'hub' === $explicit || 'classic' === $explicit ) {
		return $explicit;
	}
	return $mode_on ? 'hub' : '';
}

/**
 * Normalise a stored share option.
 *
 * @param mixed $raw Value of the option.
 * @return array token (string, '' when none) and expires (int).
 */
function vance_hub_preview_share_clean( $raw ) {
	$out = array( 'token' => '', 'expires' => 0 );
	if ( ! is_array( $raw ) || ! isset( $raw['token'], $raw['expires'] ) || ! is_string( $raw['token'] ) ) {
		return $out;
	}
	// A token is 32 letters and digits. Anything else is not a token.
	if ( ! preg_match( '/^[A-Za-z0-9]{32}$/', $raw['token'] ) ) {
		return $out;
	}
	$out['token']   = $raw['token'];
	$out['expires'] = (int) $raw['expires'];
	return $out;
}

/**
 * True when the share link exists and has not expired.
 *
 * @param array $share From vance_hub_preview_share_clean().
 * @param int   $now   Unix time.
 * @return bool
 */
function vance_hub_preview_share_live( $share, $now ) {
	return is_array( $share ) && ! empty( $share['token'] ) && (int) $share['expires'] > (int) $now;
}

/**
 * The cookie value for a token: an HMAC, so the token itself never sits in
 * the browser after the first visit.
 *
 * @param string $token Share token.
 * @param string $key   Secret key (wp_salt()).
 * @return string
 */
function vance_hub_preview_cookie_value( $token, $key ) {
	return hash_hmac( 'sha256', 'vance-hub-preview|' . $token, $key );
}

/**
 * True when a ?vhh_preview= value matches the live token.
 *
 * @param mixed $given Value from the request.
 * @param array $share Clean share.
 * @param int   $now   Unix time.
 * @return bool
 */
function vance_hub_preview_token_ok( $given, $share, $now ) {
	return is_string( $given ) && vance_hub_preview_share_live( $share, $now ) && hash_equals( $share['token'], $given );
}

/**
 * True when a cookie value matches the live token.
 *
 * @param mixed  $cookie Cookie value from the request.
 * @param array  $share  Clean share.
 * @param int    $now    Unix time.
 * @param string $key    Secret key.
 * @return bool
 */
function vance_hub_preview_cookie_ok( $cookie, $share, $now, $key ) {
	return is_string( $cookie ) && vance_hub_preview_share_live( $share, $now )
		&& hash_equals( vance_hub_preview_cookie_value( $share['token'], $key ), $cookie );
}

/**
 * A request path that is safe to redirect to on this site: starts with one
 * '/', no backslash, no control characters. Anything else gives '/'.
 *
 * @param mixed $uri Candidate path (with query).
 * @return string
 */
function vance_hub_preview_safe_path( $uri ) {
	if ( ! is_string( $uri ) || '' === $uri || '/' !== $uri[0] ) {
		return '/';
	}
	if ( isset( $uri[1] ) && '/' === $uri[1] ) {
		return '/';
	}
	if ( false !== strpos( $uri, chr( 92 ) ) || preg_match( '/[\x00-\x1F\x7F]/', $uri ) ) {
		return '/';
	}
	return $uri;
}

/* =========================================================================
 * Request state
 * ====================================================================== */

/**
 * The preview mode of this request: 'admin', 'share' or ''.
 *
 * @param string|null $set Set the state (boot only). Null reads it.
 * @return string
 */
function vance_hub_preview_mode( $set = null ) {
	static $mode = '';
	if ( null !== $set ) {
		$mode = in_array( $set, array( 'admin', 'share' ), true ) ? $set : '';
	}
	return $mode;
}

/** True when preview mode is on for this request. */
function vance_hub_preview_on() {
	return '' !== vance_hub_preview_mode();
}

/**
 * The administrator's explicit ?vhh_layout= value: 'hub', 'classic' or ''.
 *
 * Only users who may preview have the value read at all, and it must be a
 * string: ?vhh_layout[]= arrives as an array, and sanitize_key() throws a
 * TypeError on one under PHP 8.
 *
 * @return string
 */
function vance_hub_preview_explicit_query() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_GET['vhh_layout'] ) || ! is_string( $_GET['vhh_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview switch, capability-gated.
		return '';
	}
	$query = sanitize_key( wp_unslash( $_GET['vhh_layout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return ( 'hub' === $query || 'classic' === $query ) ? $query : '';
}

/**
 * The one resolver every vance_hub_*_active() function reads.
 *
 * @return string 'hub', 'classic' or ''.
 */
function vance_hub_preview_query() {
	return vance_hub_preview_pick( vance_hub_preview_explicit_query(), vance_hub_preview_on() );
}

/** The share link as stored, cleaned. */
function vance_hub_preview_share() {
	return vance_hub_preview_share_clean( get_option( vance_hub_preview_option(), array() ) );
}

/** The secret the cookie HMAC uses. */
function vance_hub_preview_key() {
	return wp_salt( 'auth' );
}

/** True when the current user may use the admin preview controls. */
function vance_hub_preview_can_admin() {
	return current_user_can( 'edit_theme_options' ) && is_user_logged_in();
}

/** True when the current user has turned the admin flag on (and may still use it). */
function vance_hub_preview_admin_flag() {
	return vance_hub_preview_can_admin() && '1' === (string) get_user_meta( get_current_user_id(), vance_hub_preview_meta_key(), true );
}

/**
 * Set or clear the share cookie.
 *
 * @param string $value   Cookie value; '' clears it.
 * @param int    $expires Unix time.
 */
function vance_hub_preview_set_cookie( $value, $expires ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie(
		vance_hub_preview_cookie_name(),
		$value,
		array(
			'expires'  => '' === $value ? time() - YEAR_IN_SECONDS : (int) $expires,
			'path'     => ( defined( 'COOKIEPATH' ) && COOKIEPATH ) ? COOKIEPATH : '/',
			'domain'   => ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/** Mark this response as not cacheable anywhere. */
function vance_hub_preview_nocache() {
	if ( ! headers_sent() ) {
		nocache_headers();
	}
	do_action( 'litespeed_control_set_nocache', 'vance hub preview' );
}

/** The current request path, made safe for a redirect, without the given args. */
function vance_hub_preview_current_path( $strip = array() ) {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by vance_hub_preview_safe_path().
	if ( $strip ) {
		$uri = remove_query_arg( $strip, $uri );
	}
	return vance_hub_preview_safe_path( $uri );
}

/** Redirect to a same-site path and stop. */
function vance_hub_preview_redirect( $path ) {
	vance_hub_preview_nocache();
	wp_safe_redirect( wp_validate_redirect( vance_hub_preview_safe_path( $path ), home_url( '/' ) ), 302, 'Vance Hub preview' );
	exit;
}

/**
 * Decide the preview mode once, on `init` (the current user is known by then).
 * Also handles the share-link landing and the share visitor's exit link.
 */
function vance_hub_preview_boot() {
	// Start every decision from "off", so a state left by an earlier call in the
	// same process cannot carry over.
	vance_hub_preview_mode( '' );

	$front_get = ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron()
		&& isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'];
	$cookie_name = vance_hub_preview_cookie_name();
	$has_cookie  = isset( $_COOKIE[ $cookie_name ] );

	// Share visitor's "Exit preview": clear the cookie, then drop the argument.
	if ( $front_get && $has_cookie && isset( $_GET['vhh_preview_exit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only clears the visitor's own preview cookie.
		vance_hub_preview_set_cookie( '', 0 );
		vance_hub_preview_redirect( vance_hub_preview_current_path( array( 'vhh_preview_exit' ) ) );
	}

	// Share-link landing. A bad or expired token falls through: no cookie, no
	// error page, the visitor sees the normal site.
	if ( $front_get && isset( $_GET['vhh_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the token is the credential.
		$share = vance_hub_preview_share();
		$given = is_string( $_GET['vhh_preview'] ) ? wp_unslash( $_GET['vhh_preview'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared with hash_equals only.
		if ( vance_hub_preview_token_ok( $given, $share, time() ) ) {
			vance_hub_preview_set_cookie( vance_hub_preview_cookie_value( $share['token'], vance_hub_preview_key() ), $share['expires'] );
			vance_hub_preview_redirect( vance_hub_preview_current_path( array( 'vhh_preview' ) ) );
		}
	}

	// The Customizer preview shows what the saved and unsaved settings give,
	// so preview mode stays out of it.
	if ( is_customize_preview() ) {
		return;
	}

	if ( vance_hub_preview_admin_flag() ) {
		vance_hub_preview_mode( 'admin' );
		return;
	}

	if ( $has_cookie ) {
		$cookie = $_COOKIE[ $cookie_name ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared with hash_equals only.
		if ( vance_hub_preview_cookie_ok( $cookie, vance_hub_preview_share(), time(), vance_hub_preview_key() ) ) {
			vance_hub_preview_mode( 'share' );
			return;
		}
		// A dead cookie (rotated, turned off, expired): clear it, so the visitor
		// goes back to the public cache bucket. Not cached itself.
		if ( $front_get ) {
			vance_hub_preview_set_cookie( '', 0 );
			vance_hub_preview_nocache();
		}
	}
}
add_action( 'init', 'vance_hub_preview_boot', 1 );

/* =========================================================================
 * Cache and SEO
 * ====================================================================== */

/**
 * A preview page is never cached and never indexed. X-Robots-Tag as well as
 * the wp_robots filter, because an SEO plugin may print its own robots tag.
 */
function vance_hub_preview_template_redirect() {
	if ( ! vance_hub_preview_on() ) {
		return;
	}
	vance_hub_preview_nocache();
	if ( ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}
add_action( 'template_redirect', 'vance_hub_preview_template_redirect', 0 );

/**
 * noindex in the robots meta tag on a preview page.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function vance_hub_preview_robots( $robots ) {
	if ( vance_hub_preview_on() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'vance_hub_preview_robots', 99 );

/**
 * Add the share cookie to LiteSpeed Cache's vary list.
 *
 * Two filters, both documented in the LSCWP API reference
 * (docs.litespeedtech.com/lscache/lscwp/api/, "Vary"):
 *   - litespeed_vary_curr_cookies: added to the X-LiteSpeed-Vary response
 *     header of the current response (Vary::finalize()). Registered on every
 *     request, so every cacheable page is stored per value of this cookie.
 *   - litespeed_vary_cookies: the site-wide list, written into the LSCACHE
 *     block of .htaccess as a Cache-Vary rule (Htaccess::_generate_rules()).
 *     It reaches .htaccess only when LSCWP rewrites that file, for example
 *     when its settings are saved.
 * Pages cached before this code shipped have no vary on the cookie: purge
 * the cache after deploy.
 *
 * @param array $cookies Cookie names.
 * @return array
 */
function vance_hub_preview_vary_cookies( $cookies ) {
	$cookies   = is_array( $cookies ) ? $cookies : array();
	$cookies[] = vance_hub_preview_cookie_name();
	return $cookies;
}
add_filter( 'litespeed_vary_curr_cookies', 'vance_hub_preview_vary_cookies', 9999 );
add_filter( 'litespeed_vary_cookies', 'vance_hub_preview_vary_cookies', 9999 );

/* =========================================================================
 * Admin actions (admin-post.php, nonce-protected, edit_theme_options)
 * ====================================================================== */

/** Address of the VanceHubClub admin page. */
function vance_hub_preview_admin_page_url() {
	return admin_url( 'admin.php?page=vance-hub-club' );
}

/**
 * Link that turns the admin flag on or off, then returns to $back.
 *
 * @param bool   $on   Turn on (true) or off (false).
 * @param string $back Same-site path to return to.
 * @return string
 */
function vance_hub_preview_toggle_url( $on, $back = '' ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'vance_hub_preview_toggle',
				'state'  => $on ? 'on' : 'off',
				// WordPress add_query_arg() does not encode the values it is given, so
				// encode once here; PHP decodes once when it fills $_REQUEST. Without
				// this, a back path with '&' splits into separate parameters.
				'back'   => rawurlencode( $back ),
			),
			admin_url( 'admin-post.php' )
		),
		'vance_hub_preview_toggle'
	);
}

/** Return to the 'back' path of a request, or to the admin page. */
function vance_hub_preview_go_back() {
	$back = isset( $_REQUEST['back'] ) && is_string( $_REQUEST['back'] ) ? wp_unslash( $_REQUEST['back'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller; validated below.
	$to   = '' === $back ? vance_hub_preview_admin_page_url() : vance_hub_preview_safe_path( $back );
	wp_safe_redirect( wp_validate_redirect( $to, vance_hub_preview_admin_page_url() ) );
	exit;
}

/** Turn the current admin's preview flag on or off. */
function vance_hub_preview_handle_toggle() {
	if ( ! vance_hub_preview_can_admin() ) {
		wp_die( esc_html__( 'You may not change the VanceHubClub preview.', 'vance-health-hub' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'vance_hub_preview_toggle' );
	$state = isset( $_REQUEST['state'] ) && is_string( $_REQUEST['state'] ) ? sanitize_key( wp_unslash( $_REQUEST['state'] ) ) : '';
	if ( 'on' === $state ) {
		update_user_meta( get_current_user_id(), vance_hub_preview_meta_key(), '1' );
	} elseif ( 'off' === $state ) {
		delete_user_meta( get_current_user_id(), vance_hub_preview_meta_key() );
	}
	vance_hub_preview_go_back();
}
add_action( 'admin_post_vance_hub_preview_toggle', 'vance_hub_preview_handle_toggle' );

/** Create (or rotate) the share link, or turn sharing off. */
function vance_hub_preview_handle_share() {
	if ( ! vance_hub_preview_can_admin() ) {
		wp_die( esc_html__( 'You may not change the VanceHubClub preview.', 'vance-health-hub' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'vance_hub_preview_share' );
	$op = isset( $_POST['op'] ) && is_string( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
	if ( 'create' === $op ) {
		// A new token replaces the old one, so every old link and cookie stops working.
		update_option(
			vance_hub_preview_option(),
			array(
				'token'   => wp_generate_password( 32, false, false ),
				'expires' => time() + vance_hub_preview_share_days() * DAY_IN_SECONDS,
			),
			false
		);
	} elseif ( 'off' === $op ) {
		delete_option( vance_hub_preview_option() );
	}
	wp_safe_redirect( vance_hub_preview_admin_page_url() );
	exit;
}
add_action( 'admin_post_vance_hub_preview_share', 'vance_hub_preview_handle_share' );

/**
 * Admin bar item: "Club preview: on" / "Club preview: off". Clicking it flips
 * the flag and returns to the same page.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function vance_hub_preview_admin_bar( $bar ) {
	if ( ! vance_hub_preview_can_admin() ) {
		return;
	}
	$on   = vance_hub_preview_admin_flag();
	$back = vance_hub_preview_current_path( array( 'vhh_preview' ) );
	$bar->add_node(
		array(
			'id'    => 'vance-hub-preview',
			'title' => esc_html( $on ? __( 'Club preview: on', 'vance-health-hub' ) : __( 'Club preview: off', 'vance-health-hub' ) ),
			'href'  => vance_hub_preview_toggle_url( ! $on, $back ),
			'meta'  => array(
				'title' => $on ? __( 'Turn the VanceHubClub preview off', 'vance-health-hub' ) : __( 'Show the VanceHubClub layout on every page, to you only', 'vance-health-hub' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'vance_hub_preview_admin_bar', 90 );

/**
 * The preview boxes on the VanceHubClub admin page: the admin's own preview
 * mode, and the share link. Shown only to a user who may use them. The token
 * is printed here and nowhere else.
 */
function vance_hub_preview_admin_box() {
	if ( ! vance_hub_preview_can_admin() ) {
		return;
	}
	$on    = vance_hub_preview_admin_flag();
	$share = vance_hub_preview_share();
	$live  = vance_hub_preview_share_live( $share, time() );
	$when  = $share['token'] ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $share['expires'] ) : '';
	$post  = admin_url( 'admin-post.php' );
	?>
	<h2><?php esc_html_e( 'Preview mode (you only)', 'vance-health-hub' ); ?></h2>
	<p><?php esc_html_e( 'Preview mode shows the VanceHubClub layout on every page, to you only. It stays on when you go from page to page, until you turn it off. A bar at the bottom of each page shows that it is on. You can also turn it on and off from the admin bar ("Club preview").', 'vance-health-hub' ); ?></p>
	<p>
		<?php esc_html_e( 'Preview mode now:', 'vance-health-hub' ); ?>
		<strong><?php echo esc_html( $on ? __( 'On', 'vance-health-hub' ) : __( 'Off', 'vance-health-hub' ) ); ?></strong>
		<a class="button<?php echo $on ? '' : ' button-primary'; ?>" href="<?php echo esc_url( vance_hub_preview_toggle_url( ! $on ) ); ?>"><?php echo esc_html( $on ? __( 'Turn preview off', 'vance-health-hub' ) : __( 'Turn preview on', 'vance-health-hub' ) ); ?></a>
	</p>

	<h2><?php esc_html_e( 'Share a preview link', 'vance-health-hub' ); ?></h2>
	<p><?php esc_html_e( 'Send this link to a colleague who has no login. It shows the VanceHubClub layout on every page, in their browser only, until the link expires. It does not give access to member pages or to any data.', 'vance-health-hub' ); ?></p>
	<?php if ( $live ) : ?>
		<p>
			<label for="vhh-preview-link" class="screen-reader-text"><?php esc_html_e( 'Preview link', 'vance-health-hub' ); ?></label>
			<input type="text" id="vhh-preview-link" class="large-text code" readonly value="<?php echo esc_attr( add_query_arg( 'vhh_preview', $share['token'], home_url( '/' ) ) ); ?>" onfocus="this.select()">
		</p>
		<p>
			<button type="button" class="button button-primary" id="vhh-preview-copy"><?php esc_html_e( 'Copy link', 'vance-health-hub' ); ?></button>
			<span id="vhh-preview-copied" aria-live="polite"></span>
		</p>
		<p><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'The link expires on %s.', 'vance-health-hub' ), $when ) ); ?></p>
	<?php elseif ( $share['token'] ) : ?>
		<p><strong><?php echo esc_html( sprintf( /* translators: %s: date and time */ __( 'The link expired on %s. Make a new link to share again.', 'vance-health-hub' ), $when ) ); ?></strong></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Sharing is off. There is no link.', 'vance-health-hub' ); ?></p>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( $post ); ?>" style="display:inline-block;margin-right:8px">
		<input type="hidden" name="action" value="vance_hub_preview_share">
		<input type="hidden" name="op" value="create">
		<?php wp_nonce_field( 'vance_hub_preview_share' ); ?>
		<button type="submit" class="button"><?php echo esc_html( $share['token'] ? __( 'New link', 'vance-health-hub' ) : __( 'Create link', 'vance-health-hub' ) ); ?></button>
	</form>
	<?php if ( $share['token'] ) : ?>
		<form method="post" action="<?php echo esc_url( $post ); ?>" style="display:inline-block">
			<input type="hidden" name="action" value="vance_hub_preview_share">
			<input type="hidden" name="op" value="off">
			<?php wp_nonce_field( 'vance_hub_preview_share' ); ?>
			<button type="submit" class="button"><?php esc_html_e( 'Turn sharing off', 'vance-health-hub' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( '"New link" and "Turn sharing off" stop the old link at once, also for people who opened it before.', 'vance-health-hub' ); ?></p>
	<?php endif; ?>
	<?php if ( $live ) : ?>
		<script>
		( function () {
			var btn = document.getElementById( 'vhh-preview-copy' );
			var input = document.getElementById( 'vhh-preview-link' );
			var note = document.getElementById( 'vhh-preview-copied' );
			if ( ! btn || ! input ) { return; }
			btn.addEventListener( 'click', function () {
				var done = function () { note.textContent = <?php echo wp_json_encode( __( 'Copied.', 'vance-health-hub' ) ); ?>; };
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( input.value ).then( done );
				} else {
					input.select();
					document.execCommand( 'copy' );
					done();
				}
			} );
		}() );
		</script>
	<?php endif; ?>
	<?php
}

/* =========================================================================
 * Banner
 * ====================================================================== */

/** The banner's styles, inline: one small block, only on preview pages. */
function vance_hub_preview_enqueue() {
	if ( is_admin() || ! vance_hub_preview_on() ) {
		return;
	}
	wp_register_style( 'vance-hub-preview', false, array(), '1.0.0' );
	wp_enqueue_style( 'vance-hub-preview' );
	wp_add_inline_style(
		'vance-hub-preview',
		'.vhh-preview-bar{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:99999;'
		. 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px 16px;'
		. 'max-width:calc(100% - 32px);padding:10px 16px;background:#0A1929;color:#FFFFFF;'
		. 'border-radius:var(--radius-surface,14px);box-shadow:0 6px 24px rgba(10,25,41,.25);'
		. 'font:500 14px/1.4 var(--font-main,"Inter",system-ui,sans-serif);text-align:center}'
		. '.vhh-preview-bar__exit{display:inline-block;padding:6px 12px;background:#008080;color:#FFFFFF;'
		. 'border-radius:var(--radius-control,6px);text-decoration:none;font-weight:600}'
		. '.vhh-preview-bar__exit:hover,.vhh-preview-bar__exit:focus{background:#006666;color:#FFFFFF}'
		. '.vhh-preview-bar__exit:focus-visible{outline:2px solid #DEF4F4;outline-offset:2px}'
		. '@media print{.vhh-preview-bar{display:none}}'
	);
}
add_action( 'wp_enqueue_scripts', 'vance_hub_preview_enqueue' );

/** The banner itself, at the end of the page. */
function vance_hub_preview_banner() {
	if ( is_admin() || ! vance_hub_preview_on() ) {
		return;
	}
	// The token must never be printed outside the admin box, so strip it here.
	$here = vance_hub_preview_current_path( array( 'vhh_preview' ) );
	if ( 'admin' === vance_hub_preview_mode() ) {
		$exit = vance_hub_preview_toggle_url( false, $here );
	} else {
		$exit = add_query_arg( 'vhh_preview_exit', '1', $here );
	}
	echo '<div class="vhh-preview-bar" role="region" aria-label="' . esc_attr__( 'Preview', 'vance-health-hub' ) . '">'
		. '<span>' . esc_html__( 'You are viewing the VanceHubClub preview.', 'vance-health-hub' ) . '</span>'
		. '<a class="vhh-preview-bar__exit" href="' . esc_url( $exit ) . '">' . esc_html__( 'Exit preview', 'vance-health-hub' ) . '</a>'
		. '</div>';
}
add_action( 'wp_footer', 'vance_hub_preview_banner', 100 );
