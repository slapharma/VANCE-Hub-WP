<?php
/**
 * Resolve every DOI in an article against CrossRef, and say so in the editor.
 *
 * An audit on 2026-09-01 checked all 635 DOIs on the site. 66 of the 613 in
 * patient guides were wrong: 23 pointed at nothing, and 43 resolved cleanly to
 * a different paper than the one the citation named. Right journal, right year,
 * wrong article — in 40 of those 43 the stated year matched the paper the DOI
 * really points to.
 *
 * That last detail is why this file exists rather than a spellcheck. A mistyped
 * DOI 404s, because the identifier space is sparse; landing on a real paper in
 * the right journal and year means the identifier was constructed to look
 * plausible instead of copied from a source. No amount of proofreading catches
 * that, because the citation reads perfectly. Only resolving it does.
 *
 * So: on publish, every DOI in the post is resolved against CrossRef — the
 * registration agency for these prefixes, and therefore the authority on what a
 * DOI actually points at — and the title it returns is compared with the
 * citation the DOI is attached to. The result goes in the editor sidebar and in
 * a column on the Posts list.
 *
 * It flags, it does not block. Articles arrive here through an automated
 * pipeline, and a network call standing between that pipeline and a successful
 * publish would turn a CrossRef outage into a content outage. The check runs a
 * few seconds after the post lands and writes its verdict where an editor will
 * see it.
 *
 * Backfill and CI:
 *
 *     wp vance citations                 # every published post
 *     wp vance citations --post=1583     # one post
 *     wp vance citations --refresh       # ignore the cache
 *
 * Exits non-zero when anything is wrong, so it can gate a deploy.
 *
 * @package Vance_Health_Hub
 * @since   2026-09-01
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VANCE_CITATION_META  = '_vance_citation_report';
const VANCE_CITATION_EVENT = 'vance_check_citations';

/**
 * Words too common to say anything about whether two titles are the same work.
 *
 * @return array<string,true>
 */
function vance_citation_stopwords() {
	static $stop = null;

	if ( null === $stop ) {
		$stop = array_fill_keys(
			explode( ' ', 'the and for with from that this into their there where when been have were was are its of in on to a an by as at is be' ),
			true
		);
	}

	return $stop;
}

/**
 * Significant words in a string, for comparing a citation with a paper title.
 *
 * Short words carry no signal at this length of text, so anything under five
 * characters is dropped along with the stopwords.
 *
 * @param string $text Text to reduce.
 * @return array<string,true> Word set.
 */
function vance_citation_tokens( $text ) {
	$stop  = vance_citation_stopwords();
	$words = array();

	if ( preg_match_all( '~[a-z]+~', strtolower( (string) $text ), $m ) ) {
		foreach ( $m[0] as $w ) {
			if ( strlen( $w ) > 4 && ! isset( $stop[ $w ] ) ) {
				$words[ $w ] = true;
			}
		}
	}

	return $words;
}

/**
 * Every DOI in a post's content, with the citation text it sits in.
 *
 * The 320 characters before a DOI are where its reference lives in this site's
 * house style — "Authors. Title. Journal. Year;Vol(Issue):pages. doi:" — which
 * is what the returned title has to be compared against.
 *
 * @param string $content Post content.
 * @return array<int,array{doi:string,context:string}>
 */
function vance_extract_citations( $content ) {
	$found = array();

	if ( ! preg_match_all( '~10\.\d{4,9}/[^\s<>"\'&]+~', (string) $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return $found;
	}

	foreach ( $m[0] as $hit ) {
		$doi = $hit[0];

		if ( function_exists( 'vance_split_doi_punctuation' ) ) {
			list( $doi ) = vance_split_doi_punctuation( $doi );
		} else {
			$doi = rtrim( $doi, '.,;:' );
		}

		$start   = max( 0, $hit[1] - 320 );
		$context = substr( $content, $start, $hit[1] - $start );
		$context = preg_replace( '~\s+~', ' ', wp_strip_all_tags( $context ) );

		$found[] = array(
			'doi'     => $doi,
			'context' => trim( $context ),
		);
	}

	return $found;
}

/**
 * Ask CrossRef what a DOI is registered to.
 *
 * Registered metadata does not change, so a hit is cached for a month. A miss
 * is cached for three days only — a DOI can be registered after an article
 * quotes it, particularly for papers published online ahead of an issue.
 * Transport failures are not cached at all, or a CrossRef wobble would look
 * like a bad citation for a month.
 *
 * @param string $doi   Bare DOI.
 * @param bool   $fresh Skip the cache.
 * @return array{status:string,title?:string,journal?:string,year?:int,author?:string}
 */
function vance_crossref_lookup( $doi, $fresh = false ) {
	$key = 'vance_cr_' . md5( $doi );

	if ( ! $fresh ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		'https://api.crossref.org/works/' . rawurlencode( $doi ),
		array(
			'timeout'    => 15,
			'user-agent' => sprintf(
				'VanceHealthHub/1.0 (%s; mailto:%s)',
				home_url( '/' ),
				get_option( 'admin_email' )
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'status' => 'unchecked' );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 404 === $code ) {
		$result = array( 'status' => 'missing' );
		set_transient( $key, $result, 3 * DAY_IN_SECONDS );
		return $result;
	}

	if ( 200 !== $code ) {
		// Rate limiting or an outage. Try again next time rather than record a verdict.
		return array( 'status' => 'unchecked' );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$msg  = isset( $body['message'] ) && is_array( $body['message'] ) ? $body['message'] : array();

	$result = array(
		'status'  => 'found',
		'title'   => isset( $msg['title'][0] ) ? (string) $msg['title'][0] : '',
		'journal' => isset( $msg['container-title'][0] ) ? (string) $msg['container-title'][0] : '',
		'year'    => isset( $msg['issued']['date-parts'][0][0] ) ? (int) $msg['issued']['date-parts'][0][0] : 0,
		'author'  => isset( $msg['author'][0]['family'] ) ? (string) $msg['author'][0]['family'] : '',
	);

	set_transient( $key, $result, 30 * DAY_IN_SECONDS );

	return $result;
}

/**
 * Check every citation in a post and store the verdict.
 *
 * The comparison allows the paper's title to appear either in the reference
 * line or in the post title, because the clinical abstract posts summarise a
 * single paper and put its title in the heading rather than in a reference. In
 * the 2026-09-01 audit that distinction was the difference between 48 reported
 * mismatches and 43 real ones.
 *
 * @param int  $post_id Post to check.
 * @param bool $fresh   Bypass the CrossRef cache.
 * @return array<int,array<string,mixed>> The stored report.
 */
function vance_check_post_citations( $post_id, $fresh = false ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return array();
	}

	$citations = vance_extract_citations( $post->post_content );

	if ( ! $citations ) {
		delete_post_meta( $post_id, VANCE_CITATION_META );
		return array();
	}

	$title_tokens = vance_citation_tokens( $post->post_title );
	$report       = array();

	foreach ( $citations as $cite ) {
		$meta = vance_crossref_lookup( $cite['doi'], $fresh );

		$row = array(
			'doi'    => $cite['doi'],
			'status' => $meta['status'],
			'cited'  => trim( substr( $cite['context'], -190 ) ),
		);

		if ( 'found' === $meta['status'] ) {
			$paper = vance_citation_tokens( $meta['title'] );

			if ( ! $paper ) {
				// Nothing to compare against — an editorial, a correction notice.
				$row['status'] = 'ok';
			} else {
				$in_ref  = count( array_intersect_key( $paper, vance_citation_tokens( $cite['context'] ) ) ) / count( $paper );
				$in_head = count( array_intersect_key( $paper, $title_tokens ) ) / count( $paper );

				$row['status']  = ( max( $in_ref, $in_head ) < 0.2 ) ? 'mismatch' : 'ok';
				$row['overlap'] = round( max( $in_ref, $in_head ), 2 );
			}

			$row['actual'] = trim(
				sprintf(
					'%s — %s (%s, %s)',
					$meta['author'],
					$meta['title'],
					$meta['journal'],
					$meta['year'] ? $meta['year'] : '?'
				)
			);
		}

		$report[] = $row;
	}

	update_post_meta( $post_id, VANCE_CITATION_META, $report );

	return $report;
}

/**
 * Count the bad rows in a stored report.
 *
 * @param array $report Stored report.
 * @return array{ok:int,missing:int,mismatch:int,unchecked:int,bad:int,total:int}
 */
function vance_citation_tally( $report ) {
	$tally = array( 'ok' => 0, 'missing' => 0, 'mismatch' => 0, 'unchecked' => 0, 'bad' => 0, 'total' => 0 );

	foreach ( (array) $report as $row ) {
		$status = isset( $row['status'] ) ? $row['status'] : 'unchecked';

		if ( ! isset( $tally[ $status ] ) ) {
			$status = 'unchecked';
		}

		$tally[ $status ]++;
		$tally['total']++;

		if ( 'missing' === $status || 'mismatch' === $status ) {
			$tally['bad']++;
		}
	}

	return $tally;
}

/* -------------------------------------------------------------------------
 * Running the check
 * ---------------------------------------------------------------------- */

/**
 * Queue a check a few seconds after a post is saved.
 *
 * Deferred rather than inline: a post can carry ten DOIs, and ten uncached HTTP
 * requests inside the save response would make the publishing pipeline wait on
 * CrossRef. Nothing here blocks a publish.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @return void
 */
function vance_queue_citation_check( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( 'publish' !== $post->post_status ) {
		return;
	}
	if ( wp_next_scheduled( VANCE_CITATION_EVENT, array( (int) $post_id ) ) ) {
		return;
	}

	wp_schedule_single_event( time() + 30, VANCE_CITATION_EVENT, array( (int) $post_id ) );
}
add_action( 'save_post_post', 'vance_queue_citation_check', 20, 2 );

/**
 * Cron target.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function vance_run_citation_check( $post_id ) {
	vance_check_post_citations( (int) $post_id );
}
add_action( VANCE_CITATION_EVENT, 'vance_run_citation_check' );

/* -------------------------------------------------------------------------
 * Telling somebody
 * ---------------------------------------------------------------------- */

/**
 * Citations column on the Posts list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function vance_citation_column( $columns ) {
	$out = array();

	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;

		if ( 'title' === $key ) {
			$out['vance_citations'] = __( 'Citations', 'vance-health-hub' );
		}
	}

	return $out;
}
add_filter( 'manage_post_posts_columns', 'vance_citation_column' );

/**
 * Render the Citations column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 * @return void
 */
function vance_citation_column_html( $column, $post_id ) {
	if ( 'vance_citations' !== $column ) {
		return;
	}

	$report = get_post_meta( $post_id, VANCE_CITATION_META, true );

	if ( ! is_array( $report ) || ! $report ) {
		echo '<span style="color:#8c8f94;">&mdash;</span>';
		return;
	}

	$tally = vance_citation_tally( $report );

	if ( $tally['bad'] ) {
		printf(
			'<strong style="color:#b32d2e;">%s</strong>',
			esc_html( sprintf( _n( '%d bad', '%d bad', $tally['bad'], 'vance-health-hub' ), $tally['bad'] ) )
		);
		printf( '<br><span style="color:#8c8f94;">of %d</span>', (int) $tally['total'] );
		return;
	}

	if ( $tally['unchecked'] ) {
		printf( '<span style="color:#996800;">%d unchecked</span>', (int) $tally['unchecked'] );
		return;
	}

	printf( '<span style="color:#007017;">%d ok</span>', (int) $tally['ok'] );
}
add_action( 'manage_post_posts_custom_column', 'vance_citation_column_html', 10, 2 );

/**
 * Register the editor box.
 *
 * @return void
 */
function vance_citation_meta_box() {
	add_meta_box(
		'vance-citation-check',
		__( 'Citation check', 'vance-health-hub' ),
		'vance_citation_meta_box_html',
		'post',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'vance_citation_meta_box' );

/**
 * Render the editor box.
 *
 * @param WP_Post $post Post being edited.
 * @return void
 */
function vance_citation_meta_box_html( $post ) {
	$report = get_post_meta( $post->ID, VANCE_CITATION_META, true );

	if ( ! is_array( $report ) || ! $report ) {
		echo '<p style="color:#646970;margin:0;">No DOIs found in this article, or it has not been checked yet. The check runs about half a minute after publishing.</p>';
		return;
	}

	$tally = vance_citation_tally( $report );

	printf(
		'<p style="margin:0 0 12px;"><strong>%d checked</strong> &mdash; %d ok, %d not found, %d pointing at a different paper%s.</p>',
		(int) $tally['total'],
		(int) $tally['ok'],
		(int) $tally['missing'],
		(int) $tally['mismatch'],
		$tally['unchecked'] ? esc_html( sprintf( ', %d could not be reached', $tally['unchecked'] ) ) : ''
	);

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th style="width:22%">DOI</th><th>Cited as</th><th>Verdict</th>';
	echo '</tr></thead><tbody>';

	foreach ( $report as $row ) {
		$status = isset( $row['status'] ) ? $row['status'] : 'unchecked';

		$verdicts = array(
			'ok'        => array( '#007017', 'Resolves, and matches the citation' ),
			'missing'   => array( '#b32d2e', 'Does not exist — 404 at doi.org' ),
			'mismatch'  => array( '#b32d2e', 'Resolves to a different paper' ),
			'unchecked' => array( '#996800', 'CrossRef could not be reached' ),
		);

		list( $colour, $label ) = isset( $verdicts[ $status ] ) ? $verdicts[ $status ] : $verdicts['unchecked'];

		echo '<tr>';
		printf(
			'<td><a href="%s" target="_blank" rel="noopener"><code>%s</code></a></td>',
			esc_url( 'https://doi.org/' . $row['doi'] ),
			esc_html( $row['doi'] )
		);
		printf( '<td style="font-size:12px;color:#50575e;">&hellip;%s</td>', esc_html( isset( $row['cited'] ) ? $row['cited'] : '' ) );
		printf( '<td style="color:%s;"><strong>%s</strong>', esc_attr( $colour ), esc_html( $label ) );

		if ( 'mismatch' === $status && ! empty( $row['actual'] ) ) {
			printf( '<br><span style="font-size:12px;color:#50575e;">It is: %s</span>', esc_html( $row['actual'] ) );
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
	echo '<p style="margin:10px 0 0;color:#646970;font-size:12px;">Checked against CrossRef, the registration agency that decides what a DOI points to. A citation counts as wrong when the DOI resolves to a paper whose title has almost nothing in common with the reference it is attached to.</p>';
}

/* -------------------------------------------------------------------------
 * wp vance citations
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Resolve every DOI on the site against CrossRef.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Check a single post instead of everything published.
	 *
	 * [--refresh]
	 * : Ignore the cached CrossRef responses and ask again.
	 *
	 * [--quiet-ok]
	 * : Only print posts that have a problem.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	function vance_citations_cli( $args, $assoc_args ) {
		$fresh    = isset( $assoc_args['refresh'] );
		$quiet_ok = isset( $assoc_args['quiet-ok'] );

		if ( isset( $assoc_args['post'] ) ) {
			$ids = array( (int) $assoc_args['post'] );
		} else {
			$ids = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);
		}

		$totals = array( 'ok' => 0, 'missing' => 0, 'mismatch' => 0, 'unchecked' => 0 );
		$dirty  = 0;

		foreach ( $ids as $id ) {
			$report = vance_check_post_citations( $id, $fresh );

			if ( ! $report ) {
				continue;
			}

			$tally = vance_citation_tally( $report );

			foreach ( array( 'ok', 'missing', 'mismatch', 'unchecked' ) as $k ) {
				$totals[ $k ] += $tally[ $k ];
			}

			$slug = get_post_field( 'post_name', $id );

			if ( $tally['bad'] ) {
				$dirty++;
				WP_CLI::log( WP_CLI::colorize( sprintf( '%%R%d bad of %d%%n  %s', $tally['bad'], $tally['total'], $slug ) ) );

				foreach ( $report as $row ) {
					if ( in_array( $row['status'], array( 'missing', 'mismatch' ), true ) ) {
						WP_CLI::log( sprintf( '      %-34s %s', $row['doi'], 'missing' === $row['status'] ? 'does not exist' : 'points at: ' . $row['actual'] ) );
					}
				}
			} elseif ( ! $quiet_ok ) {
				WP_CLI::log( WP_CLI::colorize( sprintf( '%%G%d ok%%n        %s', $tally['total'], $slug ) ) );
			}
		}

		$bad = $totals['missing'] + $totals['mismatch'];

		WP_CLI::log( '' );
		WP_CLI::log(
			sprintf(
				'%d citations checked across %d posts: %d ok, %d missing, %d mismatched, %d unreachable.',
				array_sum( $totals ),
				count( $ids ),
				$totals['ok'],
				$totals['missing'],
				$totals['mismatch'],
				$totals['unchecked']
			)
		);

		if ( $bad ) {
			WP_CLI::error( sprintf( '%d bad citation(s) across %d post(s).', $bad, $dirty ) );
		}

		WP_CLI::success( 'Every citation resolves to the paper it names.' );
	}

	WP_CLI::add_command( 'vance citations', 'vance_citations_cli' );
}
