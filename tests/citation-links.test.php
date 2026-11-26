<?php
/**
 * inc/citation-links.php — vance_linkify_dois_in_text() and vance_linkify_dois():
 * the display-time DOI linker.
 *
 * Covers the wptexturize regression: a DOI whose letter x sits between two digits
 * reaches the filter as an &#215; entity, and must still link the full DOI with
 * the letter x restored.
 *
 * Same shape as the other suites in this directory: hand-written WP stubs, no
 * WordPress, no database.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- WordPress stubs --------------------------------------------------- */

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
function esc_url( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }

require_once $THEME . '/inc/citation-links.php';

/* ---- the runner --------------------------------------------------------- */
$PASS = 0; $FAIL = array();
function check( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}
function section( $t ) { echo "\n-- $t\n"; }


/* ===================================================================== */
section( '1. vance_linkify_dois_in_text(): wptexturize multiplication sign' );
/* ===================================================================== */

// wptexturize turned the x of 1570159x14666160404124127 into &#215; before the filter ran.
$texturized_in = 'Available at 10.2174/1570159&#215;14666160404124127 online.';
$texturized_out = vance_linkify_dois_in_text( $texturized_in );

check( '1a  links the FULL DOI, with the letter x restored, in one anchor and then the trailing text',
	$texturized_out === 'Available at <a class="vhh-doi" href="https://doi.org/10.2174/1570159x14666160404124127" target="_blank" rel="noopener">10.2174/1570159x14666160404124127</a> online.' );

check( '1b  the link text shows the letter x, not a multiplication sign',
	strpos( $texturized_out, '>10.2174/1570159x14666160404124127</a>' ) !== false );

check( '1c  no &#215; entity survives anywhere in the output',
	strpos( $texturized_out, '&#215;' ) === false );


/* ===================================================================== */
section( '2. vance_linkify_dois_in_text(): ordinary DOIs' );
/* ===================================================================== */

check( '2a  a normal DOI in a sentence is linked, its text unchanged',
	vance_linkify_dois_in_text( 'See 10.1016/j.cell.2020.01.001 for detail' )
	=== 'See <a class="vhh-doi" href="https://doi.org/10.1016/j.cell.2020.01.001" target="_blank" rel="noopener">10.1016/j.cell.2020.01.001</a> for detail' );

check( '2b  a trailing full stop stays outside the link',
	vance_linkify_dois_in_text( 'Published as 10.1002/mds.1234.' )
	=== 'Published as <a class="vhh-doi" href="https://doi.org/10.1002/mds.1234" target="_blank" rel="noopener">10.1002/mds.1234</a>.' );

check( '2c  a DOI followed by an &amp; entity is not absorbed into the link',
	vance_linkify_dois_in_text( '10.1016/j.cell.2020.01.001&amp;more' )
	=== '<a class="vhh-doi" href="https://doi.org/10.1016/j.cell.2020.01.001" target="_blank" rel="noopener">10.1016/j.cell.2020.01.001</a>&amp;more' );

check( '2d  a DOI with an ordinary literal x (not an entity) keeps its x in both href and text',
	vance_linkify_dois_in_text( 'Ref 10.2174/1570159x14666160404124127 end' )
	=== 'Ref <a class="vhh-doi" href="https://doi.org/10.2174/1570159x14666160404124127" target="_blank" rel="noopener">10.2174/1570159x14666160404124127</a> end' );


/* ===================================================================== */
section( '3. vance_linkify_dois(): content that is already linked' );
/* ===================================================================== */

check( '3a  a DOI already inside an anchor is left exactly as written',
	vance_linkify_dois( '<a href="https://doi.org/10.1016/j.cell.2020.01.001">10.1016/j.cell.2020.01.001</a>' )
	=== '<a href="https://doi.org/10.1016/j.cell.2020.01.001">10.1016/j.cell.2020.01.001</a>' );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
