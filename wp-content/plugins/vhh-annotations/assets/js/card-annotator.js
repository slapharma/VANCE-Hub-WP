/**
 * VHH Annotations — whole-card comments on components that link to a
 * DIFFERENT post than the page being viewed (homepage bento/list cards,
 * a single post's own Related Articles / Read Next cards).
 *
 * Unlike image/insertion commenting, the target post isn't cfg.postId — each
 * card carries its own `data-vhh-post-id` (added in the theme templates).
 * A card without that attribute is skipped rather than guessed at from href.
 *
 * Interaction (commenting mode only): click anywhere on the card — no drag.
 * This is feedback on the whole component's design/structure, not a region,
 * so it reuses the same overlay/popover visuals as whole-image commenting
 * but none of image-annotator.js's marquee logic.
 */
(function () {
	'use strict';
	if ( ! window.VHH || ! window.VHH.cfg.settings.imageEnabled ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;
	var CARD_SELECTOR = '.bento-cell-featured, .latest-list-item, .oped-related-item, .oped-readnext-item, .news-card, .va-poster-link';

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text ) { node.textContent = text; }
		return node;
	}

	/** Best-effort reference image for the selector — an <img> child, else a background-image (on the card itself or a child), else the card's own href. */
	function cardSrc( card ) {
		var img = card.querySelector( 'img' );
		if ( img ) { return ( img.currentSrc || img.src || '' ).split( '?' )[ 0 ]; }
		// .va-poster-link carries its background-image inline on itself, not a
		// descendant — querySelector() never matches the element it's called on.
		var bg = ( card.style && card.style.backgroundImage ) ? card : card.querySelector( '[style*="background-image"]' );
		if ( bg ) {
			var m = ( getComputedStyle( bg ).backgroundImage || '' ).match( /url\((['"]?)(.*?)\1\)/ );
			if ( m ) { return m[ 2 ].split( '?' )[ 0 ]; }
		}
		return card.href || '';
	}

	function openCardPopover( card, postId, rect ) {
		VHH.openPopover( {
			quote: null,
			rect: rect,
			onSave: function ( text ) {
				return VHH.api.create( {
					post: postId,
					comment: text,
					target_type: 'image',
					selector: {
						type: 'ImageRegionSelector',
						src: cardSrc( card ),
						region: { x: 0, y: 0, w: 1, h: 1 },
						wholeImage: true
					},
					overall: false
				} ).then( function ( created ) {
					VHH.closePopover();
					// This page's own registry only holds items for cfg.postId — a
					// card comment almost always targets a different post, so route
					// it to the sidebar's cross-page list instead of VHH.state.
					if ( created.post === cfg.postId ) {
						VHH.state.items[ created.id ] = created;
						VHH.insertInOrder( created );
						VHH.bus.emit( 'items:changed' );
					} else {
						VHH.bus.emit( 'cross:created', created );
					}
				} );
			}
		} );
	}

	function setupCard( card ) {
		var postId = parseInt( card.getAttribute( 'data-vhh-post-id' ), 10 ) || 0;
		if ( ! postId ) { return; } // no target post declared — skip rather than guess from href

		card.classList.add( 'vhh-bg-wrap' ); // reuses image-annotator.js's hover-hint CSS hook
		if ( getComputedStyle( card ).position === 'static' ) {
			card.style.position = 'relative';
		}

		var overlay = el( 'div', 'vhh-ui vhh-img-overlay' );
		card.appendChild( overlay );
		card.appendChild( el( 'span', 'vhh-ui vhh-img-hint', '📌 ' + cfg.i18n.wholeImage ) );

		// Same click-during-commenting-mode navigation guard as image-annotator.js.
		overlay.addEventListener( 'click', function ( e ) {
			if ( ! VHH.state.commenting ) { return; }
			e.preventDefault();
			e.stopPropagation();
			openCardPopover( card, postId, overlay.getBoundingClientRect() );
		} );
	}

	function init() {
		var root = document.querySelector( '[data-vhh-annotatable]' );
		if ( ! root ) { return; }
		root.querySelectorAll( CARD_SELECTOR ).forEach( setupCard );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
