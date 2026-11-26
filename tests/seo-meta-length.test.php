<?php
/**
 * inc/seo-meta-length.php — the pure length functions:
 * vance_meta_shorten_title(), vance_meta_pad_title(), vance_meta_cut_words()
 * and vance_meta_fit_description().
 *
 * Titles are kept to 20-60 characters and descriptions to 110-160. Every length
 * here is counted in characters, not bytes, so the curly-apostrophe and emoji
 * cases are what catch a byte-counting regression.
 *
 * Same shape as the other suites in this directory: hand-written WP stubs, no
 * WordPress, no database.
 */

define( 'ABSPATH', true );

$THEME = dirname( __DIR__ ) . '/wp-content/themes/vance-health-hub';

/* ---- WordPress stubs --------------------------------------------------- */

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}

/*
 * mbstring shim. The suite runs on PHP installs where mbstring is not loaded.
 * The shim is used only when the real function is missing. It counts UTF-8
 * code points, which is what mb_strlen() and mb_substr() do for valid UTF-8,
 * and every input in this file is valid UTF-8.
 */
/* Invalid UTF-8 (a lone lead byte) makes preg_split return false. Count bytes then, so a bad result fails a check instead of crashing the run. */
function vance_test_chars( $s ) {
	$chars = preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY );
	return false === $chars ? str_split( (string) $s ) : $chars;
}
if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $s, $enc = null ) {
		return count( vance_test_chars( $s ) );
	}
}
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null, $enc = null ) {
		return implode( '', array_slice( vance_test_chars( $s ), $start, $len ) );
	}
}

require_once $THEME . '/inc/seo-meta-length.php';

/* ---- the runner --------------------------------------------------------- */
$PASS = 0; $FAIL = array();

function expect_same( $label, $expected, $actual ) {
	global $PASS, $FAIL;
	if ( $expected === $actual ) { $PASS++; return; }
	$FAIL[] = $label . "\n      expected: " . var_export( $expected, true ) . "\n      actual:   " . var_export( $actual, true );
}

function expect_true( $label, $cond ) {
	global $PASS, $FAIL;
	if ( $cond ) { $PASS++; return; }
	$FAIL[] = $label;
}

function section( $t ) { echo "\n-- $t\n"; }


/* ===================================================================== */
section( '1. vance_meta_shorten_title(): titles at or under 60 characters' );
/* ===================================================================== */

$title = 'Gut Health Basics for Adults Living With Crohn’s Disease';
$out   = vance_meta_shorten_title( $title );
expect_same( '1a  returns a short title unchanged', 'Gut Health Basics for Adults Living With Crohn’s Disease', $out );

$title = str_repeat( 'Word ', 11 ) . 'Words';
$out   = vance_meta_shorten_title( $title );
expect_same( '1b  returns a title of exactly 60 characters unchanged', str_repeat( 'Word ', 11 ) . 'Words', $out );

$out = vance_meta_shorten_title( null );
expect_same( '1c  returns an empty string for a null title', '', $out );

$out = vance_meta_shorten_title( '' );
expect_same( '1d  returns an empty string for an empty title', '', $out );


/* ===================================================================== */
section( '2. vance_meta_shorten_title(): long titles with a subtitle separator' );
/* ===================================================================== */

$title = '7 Days of Gut-Friendly Habits: What a Supportive Week Looks Like with IBD';
$out   = vance_meta_shorten_title( $title );
expect_same( '2a  cuts at the colon when the prefix is a good title', '7 Days of Gut-Friendly Habits', $out );

$title = 'Eating Well With IBD: Food Choices for Flares, Remission, and the Days In Between';
$out   = vance_meta_shorten_title( $title );
expect_same( '2b  accepts a colon prefix of exactly 20 characters', 'Eating Well With IBD', $out );

$title = 'Diet and Gut Health: What a Supportive Week Looks Like for Adults Living With IBD and Crohn’s';
$out   = vance_meta_shorten_title( $title );
expect_same( '2c  does not cut at a colon prefix of 19 characters, falls back to a word cut', 'Diet and Gut Health: What a Supportive Week Looks Like…', $out );

$title = 'Gut Health Basics for Newly Diagnosed Adults - A Practical Guide for Everyone Living With IBD';
$out   = vance_meta_shorten_title( $title );
expect_same( '2d  cuts at a spaced dash', 'Gut Health Basics for Newly Diagnosed Adults', $out );

$title = 'Can Gut Bacteria Affect Your Mood? A Look at the Research on Brain and Gut Links';
$out   = vance_meta_shorten_title( $title );
expect_same( '2e  keeps the question mark when it cuts', 'Can Gut Bacteria Affect Your Mood?', $out );

$title = 'Gut Health Basics for Adults: Living Well with IBD: A Long Practical Guide for Everyone';
$out   = vance_meta_shorten_title( $title );
expect_same( '2f  uses the last separator that leaves a valid title', 'Gut Health Basics for Adults: Living Well with IBD', $out );

$title = 'Why Does My Gut Hurt So Much After Eating Every Single Meal of the Day? Find Out Here Now';
$out   = vance_meta_shorten_title( $title );
expect_same( '2g  ignores a separator whose prefix is over 60 characters, falls back to a word cut', 'Why Does My Gut Hurt So Much After Eating Every Single Meal…', $out );


/* ===================================================================== */
section( '3. vance_meta_shorten_title(): long titles with no separator' );
/* ===================================================================== */

$title = str_repeat( 'Word ', 11 ) . 'Wordss';
$out   = vance_meta_shorten_title( $title );
expect_same( '3a  cuts a 61-character title at a word boundary, with ellipsis', str_repeat( 'Word ', 10 ) . 'Word…', $out );

$title = str_repeat( 'Word ', 12 ) . 'Wordy';
$out   = vance_meta_shorten_title( $title );
expect_same( '3b  keeps the last whole word when the limit falls on a space', str_repeat( 'Word ', 11 ) . 'Word…', $out );

$title = 'Living Well With Inflammatory Bowel Disease Every Day of the Year';
$out   = vance_meta_shorten_title( $title );
expect_same( '3c  does not end a cut title on a joining word', 'Living Well With Inflammatory Bowel Disease Every Day…', $out );

$title = str_repeat( 'k', 80 );
$out   = vance_meta_shorten_title( $title );
expect_true( '3d  keeps a single unbroken word of 80 characters within 60', mb_strlen( $out ) <= 60 && mb_substr( $out, -1 ) === '…' );


/* ===================================================================== */
section( '4. vance_meta_shorten_title(): multibyte characters are counted as characters' );
/* ===================================================================== */

$title = 'Aberystwyth Hospital Leads UK in New Crohn’s and Colitis Trials';
$out   = vance_meta_shorten_title( $title );
expect_same( '4a  counts a curly apostrophe as one character, not three bytes', 'Aberystwyth Hospital Leads UK in New Crohn’s and Colitis…', $out );

$title = str_repeat( 'k', 59 ) . '’';
$out   = vance_meta_shorten_title( $title );
expect_same( '4b  leaves a 60-character title with a curly apostrophe unchanged', str_repeat( 'k', 59 ) . '’', $out );

$title = str_repeat( '🦠', 60 );
$out   = vance_meta_shorten_title( $title );
expect_same( '4c  leaves a 60-character title of 4-byte emoji unchanged', str_repeat( '🦠', 60 ), $out );

$title = 'Living With Crohn’s Disease: Diet, Sleep and Parents’ Tips';
$out   = vance_meta_shorten_title( $title );
expect_same( '4d  leaves a 58-character title with a colon unchanged, though it is 62 bytes', 'Living With Crohn’s Disease: Diet, Sleep and Parents’ Tips', $out );


/* ===================================================================== */
section( '5. vance_meta_pad_title(): short titles get the site name' );
/* ===================================================================== */

$out = vance_meta_pad_title( 'Gut Guide', 'Vance Health Hub' );
expect_same( '5a  pads a short title with the site name', 'Gut Guide | Vance Health Hub', $out );

$out = vance_meta_pad_title( str_repeat( 'a', 19 ), 'Vance Health Hub' );
expect_same( '5b  pads a 19-character title, the boundary just under 20', str_repeat( 'a', 19 ) . ' | Vance Health Hub', $out );

$out = vance_meta_pad_title( 'Short Title Here Ok!', 'Vance Health Hub' );
expect_same( '5c  leaves a 20-character title unpadded, the boundary', 'Short Title Here Ok!', $out );

$out = vance_meta_pad_title( 'Gut Guide', '' );
expect_same( '5d  leaves the title unpadded when there is no site name', 'Gut Guide', $out );

$out = vance_meta_pad_title( str_repeat( 'a', 19 ), str_repeat( 'S', 45 ) );
expect_same( '5e  leaves the title unpadded when the padded result would exceed 60', str_repeat( 'a', 19 ), $out );

$out = vance_meta_pad_title( '', 'Vance Health Hub' );
expect_same( '5f  leaves an empty title empty', '', $out );


/* ===================================================================== */
section( '6. vance_meta_cut_words(): cut at a word boundary' );
/* ===================================================================== */

$out = vance_meta_cut_words( 'Gut health guide', 60 );
expect_same( '6a  returns text unchanged when under the limit', 'Gut health guide', $out );

$out = vance_meta_cut_words( str_repeat( 'k', 60 ), 60 );
expect_same( '6b  returns text unchanged when exactly at the limit', str_repeat( 'k', 60 ), $out );

$out = vance_meta_cut_words( 'Gut health guide for everyone', 15 );
expect_same( '6c  drops the partial word at the cut', 'Gut health…', $out );

$out = vance_meta_cut_words( 'Crohn’s disease and colitis care', 20 );
expect_same( '6d  drops a joining word left at the end of the cut', 'Crohn’s disease…', $out );

$out = vance_meta_cut_words( str_repeat( 'k', 30 ), 10 );
expect_same( '6e  cuts a single unbroken word inside the limit', str_repeat( 'k', 9 ) . '…', $out );

$out = vance_meta_cut_words( '', 60 );
expect_same( '6f  returns an empty string for empty text', '', $out );


/* ===================================================================== */
section( '7. vance_meta_fit_description(): long descriptions' );
/* ===================================================================== */

$desc = str_repeat( 'x', 119 ) . '. ' . str_repeat( 'y', 100 );
$out  = vance_meta_fit_description( $desc );
expect_same( '7a  cuts at a sentence end that is 70 or more characters in', str_repeat( 'x', 119 ) . '.', $out );

$desc = str_repeat( 'x', 40 ) . '. ' . str_repeat( 'word ', 40 );
$out  = vance_meta_fit_description( $desc );
expect_same( '7b  ignores a sentence end under 70 characters, cuts at a word boundary', str_repeat( 'x', 40 ) . '. ' . str_repeat( 'word ', 22 ) . 'word…', $out );

$desc = str_repeat( 'word ', 40 );
$out  = vance_meta_fit_description( $desc );
expect_same( '7c  cuts a description with no sentence end at a word boundary, 160 characters with ellipsis', str_repeat( 'word ', 31 ) . 'word…', $out );

$desc = str_repeat( 'x', 119 ) . '. ' . str_repeat( 'y', 100 );
$out  = vance_meta_fit_description( '', $desc );
expect_same( '7d  builds the description from the source when the description is empty', str_repeat( 'x', 119 ) . '.', $out );


/* ===================================================================== */
section( '8. vance_meta_fit_description(): descriptions already 110-160 characters' );
/* ===================================================================== */

$out = vance_meta_fit_description( str_repeat( 'z', 110 ) );
expect_same( '8a  leaves a 110-character description unchanged, the lower boundary', str_repeat( 'z', 110 ), $out );

$out = vance_meta_fit_description( str_repeat( 'z', 160 ) );
expect_same( '8b  leaves a 160-character description unchanged, the upper boundary', str_repeat( 'z', 160 ), $out );

$out = vance_meta_fit_description( str_repeat( '’', 120 ) );
expect_same( '8c  counts 120 curly apostrophes as 120 characters, not bytes', str_repeat( '’', 120 ), $out );

$out = vance_meta_fit_description( str_repeat( 'a', 110 ), 'SOURCE TEXT THAT MUST NOT BE USED' );
expect_same( '8d  uses the description and ignores the source when both are given', str_repeat( 'a', 110 ), $out );


/* ===================================================================== */
section( '9. vance_meta_fit_description(): short descriptions get the suffix' );
/* ===================================================================== */

$out = vance_meta_fit_description( str_repeat( 'a', 108 ) . '.' );
expect_same( '9a  a 109-character description gets the suffix, landing at 132', str_repeat( 'a', 108 ) . '. From Vance Health Hub.', $out );

$out = vance_meta_fit_description( str_repeat( 'a', 99 ) . '.' );
expect_same( '9b  a 100-character description gets the suffix, landing at 123', str_repeat( 'a', 99 ) . '. From Vance Health Hub.', $out );

$out = vance_meta_fit_description( str_repeat( 'a', 100 ) );
expect_same( '9c  adds a full stop before the suffix when the description has none', str_repeat( 'a', 100 ) . '. From Vance Health Hub.', $out );

$out = vance_meta_fit_description( str_repeat( 'c', 59 ) . '.' );
expect_same( '9d  uses the longer suffix when it fits in 110-160, landing at 121', str_repeat( 'c', 59 ) . '. Evidence-based gut health information from Vance Health Hub.', $out );


/* ===================================================================== */
section( '10. vance_meta_fit_description(): empty input' );
/* ===================================================================== */

$out = vance_meta_fit_description( '', '' );
expect_same( '10a  returns an empty string when the description and the source are both empty', '', $out );

$out = vance_meta_fit_description( null, '' );
expect_same( '10b  returns an empty string for a null description with an empty source', '', $out );


/* ===================================================================== */
section( '11. regressions: valid UTF-8 after a cut, short descriptions, trailing comma' );
/* ===================================================================== */

$title = str_repeat( 'a', 57 ) . 'ē' . ' ' . str_repeat( 'b', 30 );
$out   = vance_meta_shorten_title( $title );
expect_same( '11a  keeps a cut title ending in ē as the whole letter, not a lone lead byte', str_repeat( 'a', 57 ) . 'ē…', $out );

$title = str_repeat( 'a', 57 ) . 'ē' . ' ' . str_repeat( 'b', 30 );
$out   = vance_meta_shorten_title( $title );
expect_true( '11b  output of a cut title ending in ē is valid UTF-8', preg_match( '//u', $out ) === 1 );

$desc = str_repeat( 'x', 150 ) . 'Ó ' . str_repeat( 'y', 20 );
$out  = vance_meta_fit_description( $desc );
expect_same( '11c  keeps a cut description ending in Ó as the whole letter', str_repeat( 'x', 150 ) . 'Ó…', $out );

$out = vance_meta_fit_description( 'Gut health guide for everyone.' );
expect_true( '11d  a 30-character description lands in 110-160 characters', mb_strlen( $out ) >= 110 && mb_strlen( $out ) <= 160 );

$out = vance_meta_fit_description( 'Gut health guide for everyone.' );
expect_same( '11e  a 30-character description gets the longer filler, exact text', "Gut health guide for everyone. Practical, evidence-based information on Crohn's, colitis, IBS and gut health from Vance Health Hub.", $out );

$out = vance_meta_fit_description( str_repeat( 'q', 46 ) . '.' );
expect_true( '11f  a 47-character description lands in 110-160 characters', mb_strlen( $out ) >= 110 && mb_strlen( $out ) <= 160 );

$out = vance_meta_fit_description( 'a' );
expect_same( '11g  a 1-character description gets a full stop and the second filler, exact text', "a. Practical, evidence-based information on Crohn's, colitis, IBS and gut health from Vance Health Hub, for UK patients.", $out );

$out = vance_meta_fit_description( "   \t  ", '' );
expect_same( '11h  a description that is only whitespace, with no source, returns an empty string', '', $out );

$title = 'Gut Health Tips for Crohn’s Patients, Carers, and the Families Who Support Them';
$out   = vance_meta_shorten_title( $title );
expect_same( '11i  drops the joining words after a comma and keeps the word before them', 'Gut Health Tips for Crohn’s Patients, Carers…', $out );

$title = 'Gut Health Tips for Crohn’s Patients, Carers, and the Families Who Support Them';
$out   = vance_meta_shorten_title( $title );
expect_true( '11j  a cut title never ends with a comma before the ellipsis', strpos( $out, ',…' ) === false );


/* ===================================================================== */
echo "\n";
if ( $FAIL ) {
	echo count( $FAIL ) . " FAILED of " . ( $PASS + count( $FAIL ) ) . ":\n";
	foreach ( $FAIL as $f ) { echo "  x  $f\n"; }
	exit( 1 );
}
echo "OK — $PASS checks passed.\n";
