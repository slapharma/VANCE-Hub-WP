<?php
/**
 * Link + content check for `vance_discount` — `wp vance discounts check`.
 *
 * Copies inc/citation-check.php's shape: cache asymmetry (a good result caches
 * longer than a failure, a transport error isn't cached at all), a real
 * browser User-Agent (several provider sites 403 anything else — see
 * docs/DISCOUNTS_TOOL_PLAN.md §1: nationaltrust.org.uk sits behind a Radware
 * bot check, londonzoo.org/kew.org/nhsbsa.nhs.uk 403 non-browser UAs outright),
 * and a Posts-list column showing the last verdict.
 *
 * Unlike citation-check, there is no publish-time hook here — a scheme's URLs
 * don't change on their own the way a post's content does, so this only runs
 * on demand via WP-CLI (and could be put on a cron later; not built until
 * something needs it).
 *
 * A pure HTTP 200 proves nothing on its own (lesson: a redirect-to-index host
 * or a soft-404 template both return 200) — see the sentinel check below.
 *
 * ## Usage
 *
 *     wp vance discounts check                 # every published scheme
 *     wp vance discounts check --post=1583      # one scheme
 *     wp vance discounts check --refresh        # ignore the cache
 *
 * Exits non-zero when anything is wrong, so it can gate a deploy.
 *
 * @package vance-health-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VANCE_DISCOUNT_LINK_META = '_vance_discount_link_report';

/**
 * Provider domains known to 403 anything that doesn't look like a real
 * browser, confirmed during the 2026-09-02 audit (plan §1). Sent a browser UA
 * on every request regardless, but these are whitelisted from being reported
 * as a hard failure if they still come back non-200 — a Radware-style check
 * can fail even a browser UA occasionally, and this checker should not cry
 * wolf about a page a human can load fine.
 *
 * `bluelightcard.co.uk` added 2026-09-04: its signup link 301s to an
 * Auth0-hosted flow at blcshine.io which sits behind a Cloudflare JS
 * challenge that 403s a server-side fetch — confirmed manually with curl +
 * a browser UA and still 403, so this is a bot-challenge a real browser
 * passes and a script cannot, the same shape as the other four, not a dead
 * link. Whitelisted by the ORIGINAL host (bluelightcard.co.uk), not the
 * redirect target: vance_discount_fetch() below reads the host from the
 * URL it was called with, before wp_remote_get() follows the redirect.
 *
 * @return string[] Host suffixes.
 */
function vance_discount_lenient_hosts() {
	return array( 'nationaltrust.org.uk', 'londonzoo.org', 'kew.org', 'nhsbsa.nhs.uk', 'bluelightcard.co.uk' );
}

/**
 * Words too generic to prove anything about which page loaded — a provider
 * field like "Your water company (statutory scheme)" or "Your local council"
 * is a description, not a name, and checking for it verbatim on the CCW FAQ
 * page is guaranteed to fail even though the page is exactly right. Found by
 * running the checker against the live seed on 2026-09-04 and getting 17 of
 * 34 false "check" verdicts — a single-string sentinel misidentified real,
 * confirmed-correct pages as wrong far more often than it caught anything.
 *
 * @return array<string,true>
 */
function vance_discount_sentinel_stopwords() {
	return array_fill_keys(
		array( 'your', 'local', 'council', 'water', 'company', 'statutory', 'scheme', 'government', 'department', 'welsh' ),
		true
	);
}

/**
 * Short, distinctive substrings this scheme's page ought to contain — plural,
 * because any ONE of them appearing is enough to prove the fetch landed on
 * the right page rather than a 200 soft-404 or an unrelated redirect target.
 * A single sentinel (the original design) turned out to be too brittle: a
 * generic provider phrase or a curly-vs-straight apostrophe difference
 * ("Crohn's" vs "Crohn’s") sinks the whole check. Multiple independent
 * candidates make one mismatched character harmless.
 *
 * @param int $post_id Scheme post ID.
 * @return string[] Lowercased candidates, ASCII-apostrophe variants included.
 */
function vance_discount_sentinel_candidates( $post_id ) {
	$candidates = array();

	$cost = (string) get_post_meta( $post_id, '_vance_discount_cost', true );
	if ( preg_match( '/£\s?[0-9][0-9,.]*/', $cost, $m ) ) {
		$candidates[] = strtolower( preg_replace( '/\s+/', '', $m[0] ) );
	}

	$stop = vance_discount_sentinel_stopwords();
	$provider = (string) get_post_meta( $post_id, '_vance_discount_provider', true );
	// A provider name with a comma or bracket clause ("Royal Botanic Gardens,
	// Kew", "Chessington World of Adventures (Merlin)") — the parenthetical
	// is almost always the least likely part to appear on the PROVIDER's own
	// page (it names who they belong to, not who they are), so it's dropped
	// rather than tried as its own candidate.
	$provider = trim( preg_replace( '/\(.*$/', '', strtok( $provider, ',' ) ) );

	foreach ( preg_split( '/\s+/', $provider ) as $word ) {
		$clean = strtolower( trim( $word, ".,&'’" ) );
		if ( strlen( $clean ) >= 5 && ! isset( $stop[ $clean ] ) ) {
			$candidates[] = $clean;
		}
	}

	// The scheme's own title is a last resort: distinctive enough on a page
	// about that exact scheme, even when the provider field is a description
	// rather than a name (WaterSure's is "Your water company (statutory
	// scheme)" — no usable word survives the filter above).
	$title = (string) get_the_title( $post_id );
	foreach ( preg_split( '/\s+/', $title ) as $word ) {
		$clean = strtolower( trim( $word, ".,&'’()" ) );
		if ( strlen( $clean ) >= 5 && ! isset( $stop[ $clean ] ) ) {
			$candidates[] = $clean;
		}
	}

	return array_values( array_unique( $candidates ) );
}

/**
 * True if the given HTML body contains ANY sentinel candidate. Apostrophe
 * style (straight ' vs curly ’) is normalised on both sides so "Crohn's"
 * matches "Crohn’s" — confirmed to be the actual cause of two false fails in
 * the 2026-09-04 run (ccuk-radar-key, ccuk-cant-wait-card).
 *
 * @param string   $body       Fetched page HTML.
 * @param string[] $candidates From vance_discount_sentinel_candidates().
 * @return bool
 */
function vance_discount_sentinel_matches( $body, $candidates ) {
	$body_norm = str_replace( array( '’', '‘' ), "'", strtolower( $body ) );
	foreach ( $candidates as $candidate ) {
		if ( false !== stripos( $body_norm, str_replace( array( '’', '‘' ), "'", $candidate ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Fetch one URL with a real browser UA, cached with the citation-check
 * asymmetry: a 200 caches for a week (these pages change less often than a
 * DOI registration, but do get redesigned), a definite failure (404/other
 * non-200 on a non-lenient host) caches for a day, and a transport error or a
 * lenient-host non-200 isn't cached at all.
 *
 * @param string $url   URL to fetch.
 * @param bool   $fresh Bypass the cache.
 * @return array{ok:bool,status:string,code:int,frameable:?bool,body:string}
 */
function vance_discount_fetch( $url, $fresh = false ) {
	$key = 'vance_dc_' . md5( $url );

	if ( ! $fresh ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 20,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'status' => 'unreachable', 'code' => 0, 'frameable' => null, 'body' => '' );
	}

	$code      = (int) wp_remote_retrieve_response_code( $response );
	$host      = wp_parse_url( $url, PHP_URL_HOST );
	$lenient   = false;
	foreach ( vance_discount_lenient_hosts() as $suffix ) {
		if ( $host && ( $host === $suffix || str_ends_with( $host, '.' . $suffix ) ) ) {
			$lenient = true;
			break;
		}
	}

	if ( 200 !== $code ) {
		$result = array( 'ok' => $lenient, 'status' => $lenient ? 'lenient-non-200' : 'bad-status', 'code' => $code, 'frameable' => null, 'body' => '' );
		if ( ! $lenient ) {
			set_transient( $key, $result, DAY_IN_SECONDS );
		}
		return $result;
	}

	$xfo  = wp_remote_retrieve_header( $response, 'x-frame-options' );
	$csp  = wp_remote_retrieve_header( $response, 'content-security-policy' );
	$framebusted = ( $xfo ) || ( $csp && false !== stripos( (string) $csp, 'frame-ancestors' ) );

	$result = array(
		'ok'        => true,
		'status'    => 'ok',
		'code'      => 200,
		'frameable' => ! $framebusted,
		'body'      => wp_remote_retrieve_body( $response ),
	);
	set_transient( $key, $result, WEEK_IN_SECONDS );

	return $result;
}

/**
 * Check both URLs on one scheme, store the verdict, and (for apply_url) sync
 * the frameable meta to what was actually observed — the plan explicitly
 * wants this re-probed rather than trusted forever (§1: probed 2026-09-02).
 *
 * @param int  $post_id Scheme post ID.
 * @param bool $fresh   Bypass the cache.
 * @return array{official:array,apply:array,sentinel_found:?bool}
 */
function vance_check_discount_links( $post_id, $fresh = false ) {
	$official_url = get_post_meta( $post_id, '_vance_discount_official_url', true );
	$apply_url    = get_post_meta( $post_id, '_vance_discount_apply_url', true );
	$candidates   = vance_discount_sentinel_candidates( $post_id );

	$report = array( 'official' => null, 'apply' => null, 'sentinel_found' => null );

	if ( $official_url ) {
		$result                = vance_discount_fetch( $official_url, $fresh );
		$report['official']    = array( 'url' => $official_url, 'status' => $result['status'], 'code' => $result['code'] );
		if ( $result['ok'] && $candidates && $result['body'] ) {
			$report['sentinel_found'] = vance_discount_sentinel_matches( $result['body'], $candidates );
		}
	}

	if ( $apply_url ) {
		$result             = vance_discount_fetch( $apply_url, $fresh );
		$report['apply']    = array( 'url' => $apply_url, 'status' => $result['status'], 'code' => $result['code'] );
		if ( $result['ok'] && null !== $result['frameable'] ) {
			update_post_meta( $post_id, '_vance_discount_frameable', $result['frameable'] ? 1 : 0 );
		}
	}

	update_post_meta( $post_id, VANCE_DISCOUNT_LINK_META, $report );

	return $report;
}

/**
 * True if a stored (or freshly-computed) report has nothing wrong with it.
 *
 * @param array $report From vance_check_discount_links().
 * @return bool
 */
function vance_discount_report_ok( $report ) {
	foreach ( array( 'official', 'apply' ) as $key ) {
		if ( $report[ $key ] && 'ok' !== $report[ $key ]['status'] && 'lenient-non-200' !== $report[ $key ]['status'] ) {
			return false;
		}
	}
	return false !== $report['sentinel_found'];
}

/* -------------------------------------------------------------------------
 * Posts-list "Links" column
 * ---------------------------------------------------------------------- */

function vance_discount_link_column( $columns ) {
	$columns['vance_discount_links'] = __( 'Links', 'vance-health-hub' );
	return $columns;
}
add_filter( 'manage_vance_discount_posts_columns', 'vance_discount_link_column' );

function vance_discount_link_column_html( $column, $post_id ) {
	if ( 'vance_discount_links' !== $column ) {
		return;
	}

	$report = get_post_meta( $post_id, VANCE_DISCOUNT_LINK_META, true );
	if ( ! is_array( $report ) ) {
		echo '<span style="color:#996800;">not checked yet</span>';
		return;
	}

	echo vance_discount_report_ok( $report )
		? '<span style="color:#007017;">&#10003; ok</span>'
		: '<span style="color:#b32d2e;">&#10007; check</span>';
}
add_action( 'manage_vance_discount_posts_custom_column', 'vance_discount_link_column_html', 10, 2 );

/* -------------------------------------------------------------------------
 * wp vance discounts check
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Check a single scheme instead of every published one.
	 *
	 * [--refresh]
	 * : Ignore cached results and fetch again.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	function vance_discounts_check_cli( $args, $assoc_args ) {
		$fresh = isset( $assoc_args['refresh'] );

		if ( isset( $assoc_args['post'] ) ) {
			$ids = array( (int) $assoc_args['post'] );
		} else {
			$ids = get_posts(
				array(
					'post_type'      => 'vance_discount',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
		}

		if ( ! $ids ) {
			WP_CLI::error( 'No vance_discount posts found — run `wp vance discounts import` first.' );
		}

		$ok_count  = 0;
		$bad_slugs = array();

		foreach ( $ids as $id ) {
			$slug   = get_post_field( 'post_name', $id );
			$report = vance_check_discount_links( $id, $fresh );
			$ok     = vance_discount_report_ok( $report );

			if ( $ok ) {
				$ok_count++;
				WP_CLI::log( WP_CLI::colorize( "%Gok%n        {$slug}" ) );
				continue;
			}

			$bad_slugs[] = $slug;
			WP_CLI::log( WP_CLI::colorize( "%Rcheck%n     {$slug}" ) );
			foreach ( array( 'official', 'apply' ) as $which ) {
				if ( $report[ $which ] && 'ok' !== $report[ $which ]['status'] && 'lenient-non-200' !== $report[ $which ]['status'] ) {
					WP_CLI::log( sprintf( '      %-9s %s (%s)', $which, $report[ $which ]['status'], $report[ $which ]['url'] ) );
				}
			}
			if ( false === $report['sentinel_found'] ) {
				WP_CLI::log( '      sentinel  page loaded but expected price/provider text was not found' );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '%d of %d schemes ok.', $ok_count, count( $ids ) ) );

		if ( $bad_slugs ) {
			WP_CLI::error( sprintf( '%d scheme(s) need a look: %s', count( $bad_slugs ), implode( ', ', $bad_slugs ) ) );
		}

		WP_CLI::success( 'Every scheme link resolves and matches its sentinel.' );
	}

	WP_CLI::add_command( 'vance discounts check', 'vance_discounts_check_cli' );
}
