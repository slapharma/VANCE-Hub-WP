/**
 * VHH Annotations — main controller.
 *
 * Owns: commenting-mode toggle, selection → pill → popover → save flow,
 * mark rendering + hover card, and the shared annotation registry that
 * sidebar.js renders from.
 */
(function () {
	'use strict';
	if ( ! window.VHH ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;
	var A = null; // anchoring, resolved at init

	var state = {
		root: null,
		commenting: false,
		items: {},       // id -> annotation (API shape + {orphan: bool})
		order: [],       // ids in document order (selector-less last)
		pill: null,
		popover: null,
		hoverCard: null,
		pendingSelector: null,
		pendingRange: null
	};

	VHH.state = state;

	/* ------------------------------ helpers ------------------------------ */

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text ) { node.textContent = text; }
		return node;
	}

	function relTime( iso ) {
		var then = new Date( iso ).getTime();
		if ( isNaN( then ) ) { return ''; }
		var mins = Math.round( ( Date.now() - then ) / 60000 );
		if ( mins < 1 ) { return 'just now'; }
		if ( mins < 60 ) { return mins + 'm ago'; }
		var hours = Math.round( mins / 60 );
		if ( hours < 24 ) { return hours + 'h ago'; }
		return Math.round( hours / 24 ) + 'd ago';
	}
	VHH.relTime = relTime;

	/* ----------------------------- rendering ----------------------------- */

	function marksFor( id ) {
		return state.root.querySelectorAll( 'mark.vhh-mark[data-annotation-id="' + id + '"]' );
	}

	function applyMarkStatus( annotation ) {
		marksFor( annotation.id ).forEach( function ( m ) {
			m.classList.toggle( 'vhh-mark--resolved', annotation.status === 'resolved' );
		} );
	}

	function anchorAnnotation( annotation ) {
		annotation.orphan = false;
		if ( annotation.overall || annotation.target_type !== 'text' || ! annotation.selector ) {
			return;
		}
		var found = A.findSelector( annotation.selector, state.root );
		if ( ! found ) {
			annotation.orphan = true;
			return;
		}
		var range = A.offsetsToRange( found.start, found.end, found.map );
		if ( ! range ) {
			annotation.orphan = true;
			return;
		}
		annotation._docPos = found.start;
		A.wrapRange( range, annotation.id );
		applyMarkStatus( annotation );
	}

	/** Insert one id into state.order keeping document order (_docPos). */
	function insertInOrder( annotation ) {
		var id = String( annotation.id );
		state.order = state.order.filter( function ( x ) { return x !== id; } );
		state.order.push( id );
		state.order.sort( function ( a, b ) {
			var pa = state.items[ a ] && state.items[ a ]._docPos;
			var pb = state.items[ b ] && state.items[ b ]._docPos;
			if ( pa == null && pb == null ) { return ( +a ) - ( +b ); }
			if ( pa == null ) { return 1; }
			if ( pb == null ) { return -1; }
			return pa - pb;
		} );
	}
	VHH.insertInOrder = insertInOrder;

	function renderAll() {
		A.unwrapMarks( state.root, null );
		var ids = Object.keys( state.items );
		// Anchor in creation order; then compute document order for the sidebar.
		ids.forEach( function ( id ) { anchorAnnotation( state.items[ id ] ); } );
		state.order = ids.sort( function ( a, b ) {
			var pa = state.items[ a ]._docPos, pb = state.items[ b ]._docPos;
			if ( pa == null && pb == null ) { return ( +a ) - ( +b ); }
			if ( pa == null ) { return 1; }
			if ( pb == null ) { return -1; }
			return pa - pb;
		} );
		VHH.bus.emit( 'items:changed' );
	}

	/* --------------------------- pill + popover -------------------------- */

	function hidePill() {
		if ( state.pill ) { state.pill.style.display = 'none'; }
	}

	function showPill( rect ) {
		if ( ! state.pill ) {
			state.pill = el( 'button', 'vhh-ui vhh-pill', '💬 ' + cfg.i18n.addComment );
			state.pill.type = 'button';
			state.pill.addEventListener( 'mousedown', function ( e ) {
				e.preventDefault(); // keep the selection alive
			} );
			state.pill.addEventListener( 'click', onPillClick );
			document.body.appendChild( state.pill );
		}
		state.pill.style.display = 'block';
		var top = window.scrollY + rect.top - 44;
		if ( top < window.scrollY + 8 ) { top = window.scrollY + rect.bottom + 10; }
		state.pill.style.top = top + 'px';
		state.pill.style.left = Math.max( 8, window.scrollX + rect.left + rect.width / 2 - 70 ) + 'px';
	}

	function closePopover() {
		if ( state.popover ) {
			state.popover.remove();
			state.popover = null;
		}
		state.pendingSelector = null;
		state.pendingRange = null;
		state.pendingImage = null;
	}

	/**
	 * Shared comment popover. context: { quote, rect, onSave(text) }.
	 */
	function openPopover( context ) {
		closePopover();
		hidePill();

		var pop = el( 'div', 'vhh-ui vhh-popover' );
		if ( context.quote ) {
			var q = el( 'blockquote', 'vhh-popover-quote' );
			q.textContent = context.quote.length > 140 ? context.quote.slice( 0, 140 ) + '…' : context.quote;
			pop.appendChild( q );
		}
		var ta = el( 'textarea', 'vhh-popover-text' );
		ta.maxLength = 2000;
		ta.rows = 3;
		ta.placeholder = cfg.i18n.addComment + '…';
		pop.appendChild( ta );

		var row = el( 'div', 'vhh-popover-actions' );
		var save = el( 'button', 'vhh-btn vhh-btn--primary', cfg.i18n.save );
		save.type = 'button';
		var cancel = el( 'button', 'vhh-btn', cfg.i18n.cancel );
		cancel.type = 'button';
		row.appendChild( cancel );
		row.appendChild( save );
		pop.appendChild( row );

		cancel.addEventListener( 'click', closePopover );
		save.addEventListener( 'click', function () {
			var text = ta.value.trim();
			if ( ! text ) { ta.focus(); return; }
			save.disabled = true;
			context.onSave( text ).catch( function ( err ) {
				save.disabled = false;
				alert( cfg.i18n.saveFailed + ( err && err.message ? '\n' + err.message : '' ) );
			} );
		} );

		document.body.appendChild( pop );
		state.popover = pop;
		var rect = context.rect;
		var top = window.scrollY + rect.bottom + 8;
		var left = Math.max( 8, Math.min(
			window.scrollX + rect.left,
			window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 16
		) );
		pop.style.top = top + 'px';
		pop.style.left = left + 'px';
		ta.focus();
	}
	VHH.openPopover = openPopover;
	VHH.closePopover = closePopover;

	function onPillClick() {
		var sel = window.getSelection();
		if ( ! sel || sel.isCollapsed ) { hidePill(); return; }
		var range = sel.getRangeAt( 0 );
		var selector = A.describeRange( range, state.root );
		if ( ! selector ) { hidePill(); return; }

		var rect = range.getBoundingClientRect();
		sel.removeAllRanges();

		// Capture `selector` in the closure — openPopover() calls
		// closePopover() first, which would null any state.pending* we set
		// here before Save ever runs.
		openPopover( {
			quote: selector.exact,
			rect: rect,
			onSave: function ( text ) {
				return VHH.api.create( {
					post: cfg.postId,
					comment: text,
					target_type: 'text',
					selector: selector,
					overall: false
				} ).then( function ( created ) {
					state.items[ created.id ] = created;
					closePopover();
					// Anchor just the new note (one walk) instead of a full
					// unwrap-and-re-anchor pass over every annotation.
					anchorAnnotation( created );
					insertInOrder( created );
					VHH.bus.emit( 'items:changed' );
				} );
			}
		} );
	}

	/* ---------------------------- hover card ----------------------------- */

	function showHoverCard( annotation, mark ) {
		hideHoverCard();
		var card = el( 'div', 'vhh-ui vhh-hover-card' );
		var head = el( 'div', 'vhh-hover-head' );
		if ( annotation.author && annotation.author.avatar ) {
			var img = el( 'img', 'vhh-avatar' );
			img.src = annotation.author.avatar;
			img.alt = '';
			head.appendChild( img );
		}
		head.appendChild( el( 'strong', null, ( annotation.author && annotation.author.name ) || '' ) );
		head.appendChild( el( 'span', 'vhh-muted', ' · ' + relTime( annotation.created ) ) );
		card.appendChild( head );
		card.appendChild( el( 'div', 'vhh-hover-body', annotation.comment ) );
		document.body.appendChild( card );

		var rect = mark.getBoundingClientRect();
		card.style.top = ( window.scrollY + rect.bottom + 6 ) + 'px';
		card.style.left = Math.max( 8, Math.min(
			window.scrollX + rect.left,
			window.scrollX + document.documentElement.clientWidth - card.offsetWidth - 16
		) ) + 'px';
		state.hoverCard = card;
	}

	function hideHoverCard() {
		if ( state.hoverCard ) {
			state.hoverCard.remove();
			state.hoverCard = null;
		}
	}

	/* --------------------------- mode + wiring --------------------------- */

	function setCommenting( on ) {
		state.commenting = on;
		state.root.classList.toggle( 'vhh-commenting', on );
		document.body.classList.toggle( 'vhh-mode-on', on );
		if ( ! on ) { hidePill(); closePopover(); }
		VHH.bus.emit( 'mode:changed', on );
	}
	VHH.setCommenting = setCommenting;

	function onMouseUp() {
		// Listing views (homepage, category pages) have no single post of
		// their own to attach a text quote to — cfg.postId is 0 there (see
		// class-vhh-frontend.php::gate()). Only card-level comments work on
		// those pages; offering the text-selection pill would just lead to a
		// guaranteed "This post cannot be annotated" save failure.
		if ( ! cfg.postId || ! state.commenting || state.popover ) { return; }
		// Defer: the selection isn't final until after mouseup completes.
		setTimeout( function () {
			var sel = window.getSelection();
			if ( ! sel || sel.isCollapsed ) { hidePill(); return; }
			var range = sel.getRangeAt( 0 );
			if ( ! state.root.contains( range.commonAncestorContainer ) ) { hidePill(); return; }
			var text = String( sel );
			if ( ! text.trim() || text.length > 1000 ) { hidePill(); return; }
			showPill( range.getBoundingClientRect() );
		}, 10 );
	}

	function init() {
		A = VHH.anchoring;
		state.root = document.querySelector( '[data-vhh-annotatable]' );
		if ( ! state.root || ! A ) { return; }

		if ( cfg.settings.highlightColor ) {
			document.documentElement.style.setProperty( '--vhh-highlight', cfg.settings.highlightColor );
		}

		document.addEventListener( 'mouseup', onMouseUp );
		document.addEventListener( 'selectionchange', function () {
			var sel = window.getSelection();
			if ( sel && sel.isCollapsed && ! state.popover ) { hidePill(); }
		} );

		// Mark interactions (delegated).
		state.root.addEventListener( 'click', function ( e ) {
			var mark = e.target.closest ? e.target.closest( 'mark.vhh-mark' ) : null;
			if ( ! mark ) { return; }
			var id = mark.getAttribute( 'data-annotation-id' );
			VHH.bus.emit( 'focus:card', id );
		} );
		state.root.addEventListener( 'mouseover', function ( e ) {
			var mark = e.target.closest ? e.target.closest( 'mark.vhh-mark' ) : null;
			if ( ! mark ) { return; }
			var item = state.items[ mark.getAttribute( 'data-annotation-id' ) ];
			if ( item ) { showHoverCard( item, mark ); }
		} );
		state.root.addEventListener( 'mouseout', function ( e ) {
			if ( e.target.closest && e.target.closest( 'mark.vhh-mark' ) ) { hideHoverCard(); }
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! state.popover ) { return; }
			// Ignore clicks inside ANY vhh UI (popover, pill, panel, image
			// overlay) — otherwise the same click that opens a popover from
			// the sidebar bubbles here and instantly closes it.
			var t = e.target;
			if ( t.closest && ( t.closest( '.vhh-ui' ) || t.closest( 'mark.vhh-mark' ) ) ) { return; }
			// Click-away only closes if the textarea is empty.
			var ta = state.popover.querySelector( 'textarea' );
			if ( ta && ! ta.value.trim() ) { closePopover(); }
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) { closePopover(); hidePill(); }
		} );

		// Sidebar → scroll to mark.
		VHH.bus.on( 'focus:mark', function ( id ) {
			var mark = state.root.querySelector( 'mark.vhh-mark[data-annotation-id="' + id + '"]' );
			if ( ! mark ) { return; }
			mark.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			marksFor( id ).forEach( function ( m ) {
				m.classList.add( 'vhh-mark--pulse' );
				setTimeout( function () { m.classList.remove( 'vhh-mark--pulse' ); }, 1600 );
			} );
		} );

		// Status changes from the sidebar.
		VHH.bus.on( 'item:updated', function ( annotation ) {
			state.items[ annotation.id ] = Object.assign( state.items[ annotation.id ] || {}, annotation );
			if ( annotation.status === 'deleted' ) {
				delete state.items[ annotation.id ];
				A.unwrapMarks( state.root, annotation.id );
				state.order = state.order.filter( function ( id ) { return +id !== +annotation.id; } );
				VHH.bus.emit( 'items:changed' );
				return;
			}
			applyMarkStatus( state.items[ annotation.id ] );
			VHH.bus.emit( 'items:changed' );
		} );

		// Initial load.
		VHH.api.list( cfg.postId ).then( function ( data ) {
			( data.annotations || [] ).forEach( function ( a ) {
				state.items[ a.id ] = a;
			} );
			renderAll();
			VHH.bus.emit( 'ready' );
		} ).catch( function ( err ) {
			console.error( 'VHH: could not load annotations', err );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
