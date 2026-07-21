/**
 * VHH Annotations — insertion-point (empty-space) comments.
 *
 * Lets a reviewer flag "a new paragraph should go here" between two existing
 * blocks, not just comment on existing text/images. Anchored the same way a
 * TextQuoteSelector's context is (prefix = tail of the block before the gap,
 * suffix = head of the block after) but with no `exact` span — it's a
 * zero-width caret position, not a highlighted range.
 *
 * Interaction (commenting mode only): hover the gap between two top-level
 * content blocks to reveal a "+" bar; click it to comment. Saved insertion
 * points render as a small marker inserted directly into the document flow
 * at that gap, not just floated on top of it — editors should see it in
 * place.
 */
(function () {
	'use strict';
	if ( ! window.VHH || ! window.VHH.cfg.settings.insertionEnabled ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;
	var GAP_ZONE = 18;        // px above/below a block boundary that counts as "hovering this gap"
	var MAX_GAP_HEIGHT = 200; // px — beyond this the space is probably a whole other section, not a paragraph gap
	var MATCH_THRESHOLD = 4;  // min combined prefix+suffix similarity score to re-anchor a saved point

	var contentRoot = null;
	var blocks = [];     // direct element children of contentRoot, in order
	var bar = null;       // the floating "+" gap indicator
	var activeGap = null; // index into blocks (0..blocks.length) the bar is currently offered for
	var markers = {};     // annotation id -> marker element currently in the DOM
	var rafScheduled = false;
	var lastMoveEvent = null;

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text ) { node.textContent = text; }
		return node;
	}

	function refreshBlocks() {
		blocks = Array.prototype.filter.call( contentRoot.children, function ( node ) {
			return node.nodeType === 1 && ! node.classList.contains( 'vhh-ui' );
		} );
	}

	/* ------------------------------ anchoring ------------------------------ */

	function tailOf( node ) {
		var t = node ? node.textContent.replace( /\s+/g, ' ' ).trim() : '';
		return t.slice( -64 );
	}
	function headOf( node ) {
		var t = node ? node.textContent.replace( /\s+/g, ' ' ).trim() : '';
		return t.slice( 0, 64 );
	}

	/** Cheap suffix/prefix agreement score: shared chars from the joining edge. */
	function similarity( a, b ) {
		a = ( a || '' ).toLowerCase();
		b = ( b || '' ).toLowerCase();
		var n = Math.min( a.length, b.length );
		var score = 0;
		for ( var i = 0; i < n; i++ ) {
			if ( a[ i ] === b[ i ] ) { score++; } else { break; }
		}
		return score;
	}

	/** Best-matching gap index (0..blocks.length) for a stored selector, or -1 if nothing scores. */
	function findGap( selector ) {
		var best = -1;
		var bestScore = -1;
		for ( var i = 0; i <= blocks.length; i++ ) {
			var before = tailOf( blocks[ i - 1 ] );
			var after = headOf( blocks[ i ] );
			var score =
				similarity( ( selector.prefix || '' ).split( '' ).reverse().join( '' ), before.split( '' ).reverse().join( '' ) ) +
				similarity( selector.suffix || '', after );
			if ( score > bestScore ) {
				bestScore = score;
				best = i;
			}
		}
		return bestScore >= MATCH_THRESHOLD ? best : -1;
	}

	/* ------------------------------- markers -------------------------------- */

	function clearMarkers() {
		Object.keys( markers ).forEach( function ( id ) {
			var m = markers[ id ];
			if ( m.parentNode ) { m.parentNode.removeChild( m ); }
		} );
		markers = {};
	}

	function makeMarker( annotation, pin ) {
		var m = el( 'div', 'vhh-ui vhh-insert-marker' + ( annotation.status === 'resolved' ? ' vhh-insert-marker--resolved' : '' ) );
		m.setAttribute( 'data-annotation-id', String( annotation.id ) );
		m.appendChild( el( 'span', 'vhh-insert-marker-icon', '➕' ) );
		m.appendChild( el( 'span', 'vhh-insert-marker-label', cfg.i18n.insertionNote + ' #' + pin ) );
		m.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			VHH.bus.emit( 'focus:card', String( annotation.id ) );
		} );
		return m;
	}

	function renderMarkers() {
		clearMarkers();
		refreshBlocks();
		var pin = 0;
		VHH.state.order.forEach( function ( id ) {
			var a = VHH.state.items[ id ];
			if ( ! a || a.target_type !== 'insertion' ) { return; }
			var gap = findGap( a.selector || {} );
			if ( gap < 0 ) { a.orphan = true; return; }
			a.orphan = false;
			pin++;
			var marker = makeMarker( a, pin );
			markers[ a.id ] = marker;
			if ( blocks[ gap ] ) {
				contentRoot.insertBefore( marker, blocks[ gap ] );
			} else {
				contentRoot.appendChild( marker );
			}
		} );
	}

	/* ------------------------------ hover bar ------------------------------- */

	function hideBar() {
		if ( bar ) { bar.style.display = 'none'; }
		activeGap = null;
	}

	function showBarAt( gap, rect ) {
		if ( ! bar ) {
			bar = el( 'div', 'vhh-ui vhh-insert-gap' );
			var btn = el( 'button', 'vhh-insert-gap-btn', '+' );
			btn.type = 'button';
			btn.setAttribute( 'aria-label', cfg.i18n.insertHere );
			btn.addEventListener( 'mousedown', function ( e ) { e.preventDefault(); } );
			btn.addEventListener( 'click', onBarClick );
			bar.appendChild( btn );
			document.body.appendChild( bar );
		}
		activeGap = gap;
		bar.style.display = 'block';
		bar.style.top = ( window.scrollY + rect.top ) + 'px';
		bar.style.left = ( window.scrollX + rect.left ) + 'px';
		bar.style.width = rect.width + 'px';
	}

	function onBarClick() {
		if ( activeGap == null ) { return; }
		var gap = activeGap;
		var prefix = tailOf( blocks[ gap - 1 ] );
		var suffix = headOf( blocks[ gap ] );
		if ( ! prefix && ! suffix ) { return; }
		var rect = bar.getBoundingClientRect();
		var label = ( prefix ? '…' + prefix.slice( -60 ) : '' ) + ' ⟨ ' + cfg.i18n.insertionNote + ' ⟩ ' + ( suffix ? suffix.slice( 0, 60 ) + '…' : '' );

		VHH.openPopover( {
			quote: label.trim(),
			rect: rect,
			onSave: function ( text ) {
				return VHH.api.create( {
					post: cfg.postId,
					comment: text,
					target_type: 'insertion',
					selector: {
						type: 'InsertionPointSelector',
						prefix: prefix,
						suffix: suffix
					},
					overall: false
				} ).then( function ( created ) {
					VHH.state.items[ created.id ] = created;
					VHH.insertInOrder( created );
					VHH.closePopover();
					hideBar();
					VHH.bus.emit( 'items:changed' );
					renderMarkers();
				} );
			}
		} );
	}

	/* -------------------------------- wiring -------------------------------- */

	function handleMouseMove( e ) {
		if ( ! e || ! VHH.state.commenting || VHH.state.popover ) { hideBar(); return; }
		var sel = window.getSelection();
		if ( sel && ! sel.isCollapsed ) { hideBar(); return; } // let text-selection commenting win
		if ( ! blocks.length ) { hideBar(); return; }

		var x = e.clientX;
		var y = e.clientY;
		var contentBox = contentRoot.getBoundingClientRect();
		if ( x < contentBox.left - 40 || x > contentBox.right + 40 ) { hideBar(); return; }

		for ( var i = 0; i <= blocks.length; i++ ) {
			var top = ( i === 0 ) ? contentBox.top : blocks[ i - 1 ].getBoundingClientRect().bottom;
			var bottom = ( i === blocks.length ) ? contentBox.bottom : blocks[ i ].getBoundingClientRect().top;
			if ( bottom - top < MAX_GAP_HEIGHT && y >= top - GAP_ZONE && y <= bottom + GAP_ZONE ) {
				showBarAt( i, { top: top - 4, left: contentBox.left, width: contentBox.width } );
				return;
			}
		}
		hideBar();
	}

	function onMouseMove( e ) {
		lastMoveEvent = e;
		if ( rafScheduled ) { return; }
		rafScheduled = true;
		window.requestAnimationFrame( function () {
			rafScheduled = false;
			handleMouseMove( lastMoveEvent );
		} );
	}

	function init() {
		// Listing views (homepage, category pages) have no single post of
		// their own — cfg.postId is 0 there — and a query like
		// '.entry-content' would otherwise match the FIRST card's excerpt
		// teaser on those pages (each news-card has its own), not a real
		// article body. Only card-level comments work on those pages.
		if ( ! cfg.postId ) { return; }
		contentRoot = document.querySelector(
			'[data-vhh-annotatable] .oped-article-body, [data-vhh-annotatable] .entry-content, [data-vhh-annotatable].vhh-review-content'
		);
		if ( ! contentRoot ) { return; }
		refreshBlocks();

		document.addEventListener( 'mousemove', onMouseMove );
		document.addEventListener( 'scroll', hideBar, true );
		VHH.bus.on( 'mode:changed', function ( on ) { if ( ! on ) { hideBar(); } } );

		VHH.bus.on( 'ready', renderMarkers );
		VHH.bus.on( 'items:changed', renderMarkers );
		VHH.bus.on( 'focus:insertion', function ( id ) {
			var m = markers[ id ];
			if ( ! m ) { return; }
			m.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			m.classList.add( 'vhh-insert-marker--pulse' );
			setTimeout( function () { m.classList.remove( 'vhh-insert-marker--pulse' ); }, 1600 );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
