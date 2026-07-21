/**
 * VHH Annotations — image region annotation.
 *
 * Regions are stored as fractions of the image box (0–1), rendered with
 * CSS percentages inside an overlay that exactly tracks the image, so
 * responsive resizes and srcset swaps need no recomputation.
 *
 * Interaction (commenting mode only): drag = marquee region; plain click =
 * "comment on whole image". The overlay is pointer-transparent outside
 * commenting mode so linked images keep working.
 *
 * Also covers CSS background-image containers (currently just the article
 * hero) via a hardcoded class allowlist — that template renders a background
 * div, not an <img>, so there's nothing for querySelectorAll('img') to find.
 * Deliberately not a blanket getComputedStyle scan: that would also catch
 * decorative chrome backgrounds that were never meant to be commentable.
 * Any image/background inside a card that links to a DIFFERENT post
 * (Related Articles, Read Next, homepage cards) is deliberately skipped here
 * — see CARD_SELECTOR below — and handled by card-annotator.js's whole-card
 * overlay instead, so a click attributes to the card's own linked post
 * rather than this page's post. Registering them here too would just stack
 * a second, wrongly-attributed, unreachable overlay underneath the
 * card-level one.
 */
(function () {
	'use strict';
	if ( ! window.VHH || ! window.VHH.cfg.settings.imageEnabled ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;
	var DRAG_MIN = 6; // px before a drag counts as a marquee
	var BG_SELECTOR = '.oped-hero-image';
	// Kept in sync with card-annotator.js's CARD_SELECTOR — images/backgrounds
	// inside one of these belong to a different post than this page.
	var CARD_SELECTOR = '.bento-cell-featured, .latest-list-item, .oped-related-item, .oped-readnext-item, .news-card, .va-poster-link';

	var overlays = []; // { img?, bgEl?, overlay, attachmentId, src }

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text ) { node.textContent = text; }
		return node;
	}

	function attachmentIdFor( img ) {
		var m = ( img.className || '' ).match( /wp-image-(\d+)/ );
		if ( m ) { return parseInt( m[ 1 ], 10 ); }
		if ( img.dataset && img.dataset.id ) { return parseInt( img.dataset.id, 10 ) || 0; }
		return 0;
	}

	function baseSrc( img ) {
		return ( img.currentSrc || img.src || '' ).split( '?' )[ 0 ];
	}

	/** Extract the photo URL from a (possibly gradient-layered) CSS background-image. */
	function bgSrc( el ) {
		var value = getComputedStyle( el ).backgroundImage || '';
		var matches = value.match( /url\((['"]?)(.*?)\1\)/g ) || [];
		if ( ! matches.length ) { return ''; }
		// The overlay gradient (if any) is listed first, the photo last.
		var last = matches[ matches.length - 1 ].match( /url\((['"]?)(.*?)\1\)/ );
		return last ? last[ 2 ].split( '?' )[ 0 ] : '';
	}

	/** Current reference src for an overlay entry, whether backed by <img> or a background div. */
	function entrySrc( entry ) {
		return entry.img ? baseSrc( entry.img ) : bgSrc( entry.bgEl );
	}

	function selectorMatches( sel, entry ) {
		if ( ! sel || sel.type !== 'ImageRegionSelector' ) { return false; }
		if ( sel.attachmentId && entry.attachmentId ) {
			return sel.attachmentId === entry.attachmentId;
		}
		if ( sel.src && entry.src ) {
			return sel.src.split( '?' )[ 0 ] === entry.src;
		}
		return false;
	}

	/* ------------------------------ rendering ----------------------------- */

	function renderRegions() {
		overlays.forEach( function ( entry ) {
			entry.overlay.querySelectorAll( '.vhh-img-region' ).forEach( function ( r ) { r.remove(); } );

			var pin = 0;
			VHH.state.order.forEach( function ( id ) {
				var a = VHH.state.items[ id ];
				if ( ! a || a.target_type !== 'image' || ! selectorMatches( a.selector, entry ) ) { return; }
				pin++;
				var r = a.selector.region;
				var region = el( 'div', 'vhh-img-region' + ( a.status === 'resolved' ? ' vhh-img-region--resolved' : '' ) );
				region.style.left = ( r.x * 100 ) + '%';
				region.style.top = ( r.y * 100 ) + '%';
				region.style.width = ( r.w * 100 ) + '%';
				region.style.height = ( r.h * 100 ) + '%';
				region.setAttribute( 'data-annotation-id', String( a.id ) );
				region.appendChild( el( 'span', 'vhh-img-pin', String( pin ) ) );
				region.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					VHH.bus.emit( 'focus:card', String( a.id ) );
				} );
				entry.overlay.appendChild( region );
			} );
		} );
	}

	/* ------------------------------- capture ------------------------------ */

	function saveRegion( entry, region, rect ) {
		VHH.openPopover( {
			quote: null,
			rect: rect,
			onSave: function ( text ) {
				return VHH.api.create( {
					post: cfg.postId,
					comment: text,
					target_type: 'image',
					selector: {
						type: 'ImageRegionSelector',
						attachmentId: entry.attachmentId,
						src: entrySrc( entry ),
						region: region,
						wholeImage: region.x === 0 && region.y === 0 && region.w === 1 && region.h === 1
					},
					overall: false
				} ).then( function ( created ) {
					VHH.state.items[ created.id ] = created;
					VHH.insertInOrder( created );
					VHH.closePopover();
					VHH.bus.emit( 'items:changed' );
					renderRegions();
				} );
			}
		} );
	}

	function wireOverlay( entry ) {
		var overlay = entry.overlay;
		var marquee = null;
		var start = null;

		// Images the editor set to "Link To: Media File/Attachment Page" put
		// our overlay inside a native <a>. preventDefault() on pointerdown
		// does NOT suppress the browser's own click-driven navigation, so a
		// plain click both opens the save popover AND navigates away before
		// the user can use it — looks exactly like "clicking does nothing."
		overlay.addEventListener( 'click', function ( e ) {
			if ( VHH.state.commenting ) { e.preventDefault(); }
		} );

		overlay.addEventListener( 'pointerdown', function ( e ) {
			if ( ! VHH.state.commenting || e.button !== 0 ) { return; }
			if ( e.target.closest( '.vhh-img-region' ) ) { return; }
			e.preventDefault();
			var box = overlay.getBoundingClientRect();
			start = { x: e.clientX - box.left, y: e.clientY - box.top, box: box };
			overlay.setPointerCapture( e.pointerId );
		} );

		overlay.addEventListener( 'pointermove', function ( e ) {
			if ( ! start ) { return; }
			var x = e.clientX - start.box.left;
			var y = e.clientY - start.box.top;
			if ( ! marquee && ( Math.abs( x - start.x ) > DRAG_MIN || Math.abs( y - start.y ) > DRAG_MIN ) ) {
				marquee = el( 'div', 'vhh-img-marquee' );
				overlay.appendChild( marquee );
			}
			if ( marquee ) {
				var left = Math.max( 0, Math.min( start.x, x ) );
				var top = Math.max( 0, Math.min( start.y, y ) );
				marquee.style.left = left + 'px';
				marquee.style.top = top + 'px';
				marquee.style.width = Math.min( start.box.width - left, Math.abs( x - start.x ) ) + 'px';
				marquee.style.height = Math.min( start.box.height - top, Math.abs( y - start.y ) ) + 'px';
			}
		} );

		overlay.addEventListener( 'pointerup', function ( e ) {
			if ( ! start ) { return; }
			var box = start.box;
			var wasDrag = !! marquee;
			var region;
			var anchorRect;

			if ( wasDrag ) {
				var mr = marquee.getBoundingClientRect();
				marquee.remove();
				region = {
					x: Math.max( 0, Math.min( 1, ( mr.left - box.left ) / box.width ) ),
					y: Math.max( 0, Math.min( 1, ( mr.top - box.top ) / box.height ) ),
					w: Math.max( 0.01, Math.min( 1, mr.width / box.width ) ),
					h: Math.max( 0.01, Math.min( 1, mr.height / box.height ) )
				};
				anchorRect = mr;
			} else {
				// Plain click → whole image.
				region = { x: 0, y: 0, w: 1, h: 1 };
				anchorRect = box;
			}
			marquee = null;
			start = null;

			if ( region.w * box.width < 4 || region.h * box.height < 4 ) { return; }
			saveRegion( entry, region, anchorRect );
		} );

		overlay.addEventListener( 'pointercancel', function () {
			if ( marquee ) { marquee.remove(); }
			marquee = null;
			start = null;
		} );
	}

	/* -------------------------------- init -------------------------------- */

	function setup( img ) {
		if ( img.closest( '.vhh-img-wrap' ) ) { return; }
		var wrap = el( 'span', 'vhh-img-wrap' );
		img.parentNode.insertBefore( wrap, img );
		wrap.appendChild( img );

		var overlay = el( 'div', 'vhh-ui vhh-img-overlay' );
		wrap.appendChild( overlay );
		wrap.appendChild( el( 'span', 'vhh-ui vhh-img-hint', '📌 ' + cfg.i18n.wholeImage ) );

		var entry = {
			img: img,
			overlay: overlay,
			attachmentId: attachmentIdFor( img ),
			src: baseSrc( img )
		};
		overlays.push( entry );
		wireOverlay( entry );
	}

	/**
	 * Same overlay/region machinery as setup(), for a CSS background-image
	 * container instead of an <img>. These are already block-level boxes with
	 * their own size, so — unlike setup() — nothing gets re-parented: the
	 * overlay is appended directly as a child, after ensuring a positioning
	 * context exists for it to pin to.
	 */
	function setupBg( bgEl ) {
		if ( bgEl.classList.contains( 'vhh-bg-wrap' ) ) { return; }
		bgEl.classList.add( 'vhh-bg-wrap' );
		if ( getComputedStyle( bgEl ).position === 'static' ) {
			bgEl.style.position = 'relative';
		}

		var overlay = el( 'div', 'vhh-ui vhh-img-overlay' );
		bgEl.appendChild( overlay );
		bgEl.appendChild( el( 'span', 'vhh-ui vhh-img-hint', '📌 ' + cfg.i18n.wholeImage ) );

		var entry = {
			bgEl: bgEl,
			overlay: overlay,
			attachmentId: 0,
			src: bgSrc( bgEl )
		};
		overlays.push( entry );
		wireOverlay( entry );
	}

	function init() {
		var root = document.querySelector( '[data-vhh-annotatable]' );
		if ( ! root ) { return; }

		// Listing views (homepage, category pages) have no single post of
		// their own — cfg.postId is 0 there — so direct <img>/background
		// commenting (always posts to cfg.postId) has nothing to attach to.
		// Card-level comments (card-annotator.js) are unaffected — they never
		// depend on cfg.postId.
		if ( ! cfg.postId ) { return; }

		root.querySelectorAll( 'img' ).forEach( function ( img ) {
			if ( img.closest( CARD_SELECTOR ) ) { return; } // handled by card-annotator.js instead
			if ( img.complete ) {
				setup( img );
			} else {
				img.addEventListener( 'load', function () { setup( img ); }, { once: true } );
			}
		} );

		root.querySelectorAll( BG_SELECTOR ).forEach( function ( bgEl ) {
			if ( bgSrc( bgEl ) ) { setupBg( bgEl ); } // skip empty placeholders (e.g. "More coming soon")
		} );

		VHH.bus.on( 'ready', renderRegions );
		VHH.bus.on( 'items:changed', renderRegions );
		VHH.bus.on( 'focus:image', function ( id ) {
			var region = document.querySelector( '.vhh-img-region[data-annotation-id="' + id + '"]' );
			if ( ! region ) { return; }
			region.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			region.classList.add( 'vhh-img-region--pulse' );
			setTimeout( function () { region.classList.remove( 'vhh-img-region--pulse' ); }, 1600 );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
