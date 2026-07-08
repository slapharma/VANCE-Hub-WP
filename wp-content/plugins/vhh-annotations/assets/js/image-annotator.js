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
 */
(function () {
	'use strict';
	if ( ! window.VHH || ! window.VHH.cfg.settings.imageEnabled ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;
	var DRAG_MIN = 6; // px before a drag counts as a marquee

	var overlays = []; // { img, overlay, attachmentId, src }

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
						src: baseSrc( entry.img ),
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

	function init() {
		var root = document.querySelector( '[data-vhh-annotatable]' );
		if ( ! root ) { return; }

		root.querySelectorAll( 'img' ).forEach( function ( img ) {
			if ( img.complete ) {
				setup( img );
			} else {
				img.addEventListener( 'load', function () { setup( img ); }, { once: true } );
			}
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
