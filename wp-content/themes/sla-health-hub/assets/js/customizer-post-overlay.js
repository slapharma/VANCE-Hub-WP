/**
 * Post Hero Overlay — Customizer live preview.
 *
 * Mirrors vance_post_hero_overlay_gradient() (functions.php) in JS so the four
 * overlay settings, which use the 'postMessage' transport, repaint the hero
 * instantly as they change — no full preview reload per keystroke/drag. The
 * actual front-end rendering is still done in PHP; this only drives the live
 * preview.
 *
 * @package sla-health-hub
 */
( function ( api ) {
	'use strict';

	var HERO_SELECTOR = '.oped-hero-image';

	// Trim trailing zeros: 1.00 -> "1", 62.50 -> "62.5" (matches the PHP $fmt).
	function fmt( n ) {
		return parseFloat( parseFloat( n ).toFixed( 2 ) ).toString();
	}

	// Hex (#rgb or #rrggbb) -> [r, g, b], falling back to #434343.
	function hexToRgb( hex ) {
		hex = String( hex ).replace( /^#/, '' );
		if ( hex.length === 3 ) {
			hex = hex.replace( /(.)/g, '$1$1' );
		}
		if ( ! /^[0-9a-fA-F]{6}$/.test( hex ) ) {
			hex = '434343';
		}
		return [
			parseInt( hex.substr( 0, 2 ), 16 ),
			parseInt( hex.substr( 2, 2 ), 16 ),
			parseInt( hex.substr( 4, 2 ), 16 )
		];
	}

	function clamp( n, lo, hi ) {
		n = parseFloat( n );
		if ( isNaN( n ) ) { n = lo; }
		return Math.max( lo, Math.min( hi, n ) );
	}

	function buildGradient( color, opacity, spread ) {
		var op = clamp( opacity, 0, 1 );
		var sp = clamp( spread, 10, 100 );
		var solid = sp * 0.5;
		var c = hexToRgb( color );
		return 'linear-gradient(to right, ' +
			'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + fmt( op ) + ') 0%, ' +
			'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',' + fmt( op ) + ') ' + fmt( solid ) + '%, ' +
			'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',0) ' + fmt( sp ) + '%)';
	}

	function isEnabled( v ) {
		return v === true || v === 1 || v === '1' || v === 'on';
	}

	// Repaint the hero using the current value of every overlay setting.
	function repaint() {
		var el = document.querySelector( HERO_SELECTOR );
		if ( ! el ) { return; }

		// Recover the image URL from the current inline background-image so we
		// can rebuild the layered value without knowing it up front.
		var current = el.style.backgroundImage || '';
		var match = current.match( /url\((['"]?)(.*?)\1\)/ );
		var url = match ? match[ 2 ] : '';

		var layers = [];
		if ( isEnabled( api( 'vance_post_overlay_enable' )() ) ) {
			layers.push( buildGradient(
				api( 'vance_post_overlay_color' )(),
				api( 'vance_post_overlay_opacity' )(),
				api( 'vance_post_overlay_spread' )()
			) );
		}
		if ( url ) {
			layers.push( "url('" + url + "')" );
		}
		el.style.backgroundImage = layers.join( ', ' );
	}

	[
		'vance_post_overlay_enable',
		'vance_post_overlay_color',
		'vance_post_overlay_opacity',
		'vance_post_overlay_spread'
	].forEach( function ( id ) {
		api( id, function ( setting ) {
			setting.bind( repaint );
		} );
	} );
} )( wp.customize );
