/**
 * Post Hero Overlay — Customizer live preview.
 *
 * Mirrors vance_post_hero_overlay_gradient() + vance_resolve_post_overlay_settings()
 * (functions.php) in JS so the overlay settings, which use the 'postMessage'
 * transport, repaint the hero instantly as they change — no full preview reload
 * per keystroke/drag. The actual front-end rendering is still done in PHP; this
 * only drives the live preview.
 *
 * Category-aware: PHP localises the previewed post's main (top-level) category id
 * as vancePostOverlayPreview.catId. If that category has "Use custom overlay"
 * ticked, the preview reads its per-category settings; otherwise the global ones.
 *
 * @package sla-health-hub
 */
( function ( api ) {
	'use strict';

	var HERO_SELECTOR = '.oped-hero-image';
	var CAT_ID = ( window.vancePostOverlayPreview && window.vancePostOverlayPreview.catId ) || 0;

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

	function truthy( v ) {
		return v === true || v === 1 || v === '1' || v === 'on';
	}

	// Read a setting's current value, or undefined if it isn't registered.
	function val( id ) {
		try {
			var s = api( id );
			return s ? s() : undefined;
		} catch ( e ) {
			return undefined;
		}
	}

	// Which setting keys drive the preview: the previewed post's category
	// override (when its "custom" toggle is on) or the global settings.
	function activeKeys() {
		if ( CAT_ID && truthy( val( 'vance_post_overlay_' + CAT_ID + '_custom' ) ) ) {
			var p = 'vance_post_overlay_' + CAT_ID + '_';
			return { enable: p + 'enable', color: p + 'color', opacity: p + 'opacity', spread: p + 'spread' };
		}
		return {
			enable: 'vance_post_overlay_enable',
			color: 'vance_post_overlay_color',
			opacity: 'vance_post_overlay_opacity',
			spread: 'vance_post_overlay_spread'
		};
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

	// Repaint the hero using the currently-active settings.
	function repaint() {
		var el = document.querySelector( HERO_SELECTOR );
		if ( ! el ) { return; }

		// Recover the image URL from the current inline background-image so we
		// can rebuild the layered value without knowing it up front.
		var current = el.style.backgroundImage || '';
		var match = current.match( /url\((['"]?)(.*?)\1\)/ );
		var url = match ? match[ 2 ] : '';

		var k = activeKeys();
		var layers = [];
		if ( truthy( val( k.enable ) ) ) {
			layers.push( buildGradient( val( k.color ), val( k.opacity ), val( k.spread ) ) );
		}
		if ( url ) {
			layers.push( "url('" + url + "')" );
		}
		el.style.backgroundImage = layers.join( ', ' );
	}

	// Bind repaint to the global settings, plus this post's category settings
	// (including its "custom" toggle) so switching custom on/off updates live.
	var ids = [
		'vance_post_overlay_enable',
		'vance_post_overlay_color',
		'vance_post_overlay_opacity',
		'vance_post_overlay_spread'
	];
	if ( CAT_ID ) {
		var p = 'vance_post_overlay_' + CAT_ID + '_';
		ids = ids.concat( [ p + 'custom', p + 'enable', p + 'color', p + 'opacity', p + 'spread' ] );
	}
	ids.forEach( function ( id ) {
		api( id, function ( setting ) {
			setting.bind( repaint );
		} );
	} );
} )( wp.customize );
