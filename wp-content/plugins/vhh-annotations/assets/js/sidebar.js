/**
 * VHH Annotations — notes panel + comments toggle button.
 *
 * Desktop (≥1024px): right rail. Mobile: bottom sheet. Position variant comes
 * from settings.sidebarPosition and is expressed as a class on the panel.
 */
(function () {
	'use strict';
	if ( ! window.VHH ) { return; }

	var VHH = window.VHH;
	var cfg = VHH.cfg;

	var ui = {
		fab: null,
		panel: null,
		list: null,
		countBadge: null,
		filter: 'open',
		open: false
	};

	// Annotations left on OTHER pages/posts — fetched once, independent of
	// the current-page filter control. Not anchored into this page's DOM.
	var otherItems = [];

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) { node.className = className; }
		if ( text ) { node.textContent = text; }
		return node;
	}

	function items() {
		return VHH.state.order
			.map( function ( id ) { return VHH.state.items[ id ]; } )
			.filter( Boolean );
	}

	function matchesFilter( a ) {
		if ( ui.filter === 'all' ) { return true; }
		return a.status === ui.filter;
	}

	function visibleItems() {
		return items().filter( matchesFilter );
	}

	/* ------------------------------ actions ------------------------------ */

	function canAct( a ) {
		return cfg.user.canModerate || ( a.author && a.author.id === cfg.user.id );
	}

	function patch( a, action, card ) {
		card.classList.add( 'vhh-card--busy' );
		VHH.api.patch( a.id, action ).then( function ( updated ) {
			if ( updated.post === cfg.postId ) {
				// Owned by annotator.js's per-page state (marks/anchoring).
				VHH.bus.emit( 'item:updated', updated );
				return;
			}
			// Cross-page item: there's no mark to update, just our own list.
			var idx = -1;
			for ( var i = 0; i < otherItems.length; i++ ) {
				if ( otherItems[ i ].id === updated.id ) { idx = i; break; }
			}
			if ( 'deleted' === updated.status ) {
				if ( idx !== -1 ) { otherItems.splice( idx, 1 ); }
			} else if ( idx !== -1 ) {
				otherItems[ idx ] = updated;
			}
			renderList();
		} ).catch( function ( err ) {
			card.classList.remove( 'vhh-card--busy' );
			alert( cfg.i18n.saveFailed + ( err && err.message ? '\n' + err.message : '' ) );
		} );
	}

	/* ------------------------------- render ------------------------------ */

	function renderCard( a ) {
		var card = el( 'div', 'vhh-card vhh-card--' + a.status );
		card.setAttribute( 'data-annotation-id', String( a.id ) );

		var crossPage = a.post !== cfg.postId;
		if ( crossPage && a.post_permalink ) {
			var pageLink = el( 'a', 'vhh-card-page-link' );
			pageLink.href = a.post_permalink;
			pageLink.target = '_blank';
			pageLink.rel = 'noopener';
			pageLink.textContent = ( a.post_title || cfg.i18n.comments ) + ' ↗';
			pageLink.addEventListener( 'click', function ( e ) { e.stopPropagation(); } );
			card.appendChild( pageLink );
		}

		// Category tag + Done badge row.
		if ( a.category || a.status === 'resolved' ) {
			var tags = el( 'div', 'vhh-card-tags' );
			if ( a.category ) {
				tags.appendChild( el( 'span', 'vhh-tag', a.category ) );
			}
			if ( a.status === 'resolved' ) {
				tags.appendChild( el( 'span', 'vhh-tag vhh-tag--done', '✓ ' + cfg.i18n.done ) );
			}
			card.appendChild( tags );
		}

		var head = el( 'div', 'vhh-card-head' );
		if ( a.author && a.author.avatar ) {
			var img = el( 'img', 'vhh-avatar' );
			img.src = a.author.avatar;
			img.alt = '';
			head.appendChild( img );
		}
		head.appendChild( el( 'strong', null, ( a.author && a.author.name ) || '' ) );
		head.appendChild( el( 'span', 'vhh-muted', VHH.relTime( a.created ) ) );
		card.appendChild( head );

		if ( a.overall ) {
			card.appendChild( el( 'div', 'vhh-card-kind', cfg.i18n.overall ) );
		} else if ( a.target_type === 'image' ) {
			card.appendChild( el( 'div', 'vhh-card-kind', '📌 ' + cfg.i18n.imageNote ) );
		} else if ( a.selector && a.selector.exact ) {
			var quote = el( 'blockquote', 'vhh-card-quote' );
			quote.textContent = a.selector.exact.length > 120 ? a.selector.exact.slice( 0, 120 ) + '…' : a.selector.exact;
			card.appendChild( quote );
		}

		if ( a.orphan ) {
			card.appendChild( el( 'div', 'vhh-card-orphan', '⚠ ' + cfg.i18n.orphan ) );
		}
		if ( a.claude_task ) {
			card.appendChild( el( 'div', 'vhh-card-task', '⏳ in a proposed task' ) );
		}

		card.appendChild( el( 'div', 'vhh-card-body', a.comment ) );

		// Replies (flat thread) — visible to everyone.
		if ( a.replies && a.replies.length ) {
			var thread = el( 'div', 'vhh-replies' );
			a.replies.forEach( function ( r ) {
				var rc = el( 'div', 'vhh-reply' );
				var rh = el( 'div', 'vhh-reply-head' );
				rh.appendChild( el( 'strong', null, ( r.author && r.author.name ) || '' ) );
				rh.appendChild( el( 'span', 'vhh-muted', VHH.relTime( r.created ) ) );
				rc.appendChild( rh );
				rc.appendChild( el( 'div', 'vhh-reply-body', r.comment ) );
				thread.appendChild( rc );
			} );
			card.appendChild( thread );
		}

		var actions = el( 'div', 'vhh-card-actions' );

		// Reply — any logged-in user.
		var replyBtn = el( 'button', 'vhh-btn vhh-btn--small', cfg.i18n.reply );
		replyBtn.type = 'button';
		replyBtn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			toggleReplyBox( card, a );
		} );
		actions.appendChild( replyBtn );

		// Resolve/Done + delete — moderators or the note's owner.
		if ( canAct( a ) ) {
			var res = el( 'button', 'vhh-btn vhh-btn--small',
				a.status === 'resolved' ? cfg.i18n.reopen : cfg.i18n.markDone );
			res.type = 'button';
			res.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				patch( a, a.status === 'resolved' ? 'unresolve' : 'resolve', card );
			} );
			actions.appendChild( res );

			var del = el( 'button', 'vhh-btn vhh-btn--small vhh-btn--danger', cfg.i18n.delete );
			del.type = 'button';
			del.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				if ( window.confirm( cfg.i18n.confirmDelete ) ) {
					patch( a, 'delete', card );
				}
			} );
			actions.appendChild( del );
		}
		card.appendChild( actions );

		card.addEventListener( 'click', function () {
			// No mark exists on THIS page's DOM for a cross-page annotation.
			if ( crossPage || a.overall || a.orphan ) { return; }
			VHH.bus.emit( a.target_type === 'image' ? 'focus:image' : 'focus:mark', String( a.id ) );
		} );

		return card;
	}

	/** Toggle an inline reply composer under a card. */
	function toggleReplyBox( card, a ) {
		var existing = card.querySelector( '.vhh-reply-box' );
		if ( existing ) { existing.remove(); return; }

		var box = el( 'div', 'vhh-reply-box' );
		var ta = el( 'textarea', 'vhh-reply-input' );
		ta.rows = 2;
		ta.placeholder = cfg.i18n.replyPlaceholder;
		ta.addEventListener( 'click', function ( e ) { e.stopPropagation(); } );
		box.appendChild( ta );

		var send = el( 'button', 'vhh-btn vhh-btn--small vhh-btn--primary', cfg.i18n.send );
		send.type = 'button';
		send.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var text = ta.value.trim();
			if ( ! text ) { ta.focus(); return; }
			send.disabled = true;
			VHH.api.reply( a.id, a.post, text ).then( function ( reply ) {
				a.replies = ( a.replies || [] ).concat( {
					id: reply.id,
					author: reply.author,
					comment: reply.comment,
					created: reply.created
				} );
				// Keep both the per-page registry and the cross-page list in sync.
				if ( VHH.state.items[ a.id ] ) { VHH.state.items[ a.id ].replies = a.replies; }
				renderList();
			} ).catch( function ( err ) {
				send.disabled = false;
				alert( cfg.i18n.saveFailed + ( err && err.message ? '\n' + err.message : '' ) );
			} );
		} );
		box.appendChild( send );
		card.appendChild( box );
		ta.focus();
	}

	function renderList() {
		if ( ! ui.list ) { return; }
		ui.list.textContent = '';

		var visible = visibleItems();
		var overall = visible.filter( function ( a ) { return a.overall; } );
		var inline = visible.filter( function ( a ) { return ! a.overall; } );

		if ( overall.length ) {
			ui.list.appendChild( el( 'h4', 'vhh-section-title', cfg.i18n.overall ) );
			overall.forEach( function ( a ) { ui.list.appendChild( renderCard( a ) ); } );
		}
		if ( inline.length ) {
			ui.list.appendChild( el( 'h4', 'vhh-section-title', cfg.i18n.inlineNotes ) );
			inline.forEach( function ( a ) { ui.list.appendChild( renderCard( a ) ); } );
		}
		if ( ! visible.length ) {
			ui.list.appendChild( el( 'p', 'vhh-empty', '—' ) );
		}

		var otherVisible = otherItems.filter( matchesFilter );
		if ( otherVisible.length ) {
			ui.list.appendChild( el( 'h4', 'vhh-section-title', cfg.i18n.otherPages ) );
			otherVisible.forEach( function ( a ) { ui.list.appendChild( renderCard( a ) ); } );
		}

		var openCount = items().filter( function ( a ) { return a.status === 'open'; } ).length;
		if ( ui.countBadge ) {
			ui.countBadge.textContent = String( openCount );
			ui.countBadge.style.display = openCount ? 'inline-flex' : 'none';
		}
	}

	function setOpen( open ) {
		ui.open = open;
		ui.panel.classList.toggle( 'vhh-panel--open', open );
		VHH.setCommenting( open );
	}

	function buildPanel() {
		var pos = ( cfg.settings.sidebarPosition || 'right' );
		ui.panel = el( 'aside', 'vhh-ui vhh-panel vhh-panel--pos-' + pos );
		ui.panel.setAttribute( 'aria-label', cfg.i18n.comments );

		var head = el( 'div', 'vhh-panel-head' );
		head.appendChild( el( 'h3', 'vhh-panel-title', cfg.i18n.comments ) );

		var filter = document.createElement( 'select' );
		filter.className = 'vhh-filter';
		[ [ 'open', 'Open' ], [ 'resolved', cfg.i18n.done ], [ 'all', 'All' ] ].forEach( function ( opt ) {
			var o = document.createElement( 'option' );
			o.value = opt[ 0 ];
			o.textContent = opt[ 1 ];
			filter.appendChild( o );
		} );
		filter.value = ui.filter;
		filter.addEventListener( 'change', function () {
			ui.filter = filter.value;
			renderList();
		} );
		head.appendChild( filter );

		var close = el( 'button', 'vhh-panel-close', '×' );
		close.type = 'button';
		close.setAttribute( 'aria-label', 'Close' );
		close.addEventListener( 'click', function () { setOpen( false ); } );
		head.appendChild( close );
		ui.panel.appendChild( head );

		var overallBtn = el( 'button', 'vhh-btn vhh-btn--block', '+ ' + cfg.i18n.overall );
		overallBtn.type = 'button';
		overallBtn.addEventListener( 'click', function () {
			VHH.openPopover( {
				quote: null,
				rect: overallBtn.getBoundingClientRect(),
				onSave: function ( text ) {
					return VHH.api.create( {
						post: cfg.postId,
						comment: text,
						overall: true
					} ).then( function ( created ) {
						VHH.state.items[ created.id ] = created;
						VHH.insertInOrder( created );
						VHH.closePopover();
						renderList();
					} );
				}
			} );
		} );
		ui.panel.appendChild( overallBtn );

		ui.list = el( 'div', 'vhh-panel-list' );
		ui.panel.appendChild( ui.list );
		document.body.appendChild( ui.panel );
	}

	function buildFab() {
		ui.fab = el( 'button', 'vhh-ui vhh-fab' );
		ui.fab.type = 'button';
		ui.fab.setAttribute( 'aria-label', cfg.i18n.comments );
		ui.fab.appendChild( el( 'span', null, '💬 ' + cfg.i18n.comments ) );
		ui.countBadge = el( 'span', 'vhh-fab-badge', '0' );
		ui.countBadge.style.display = 'none';
		ui.fab.appendChild( ui.countBadge );
		ui.fab.addEventListener( 'click', function () { setOpen( ! ui.open ); } );
		document.body.appendChild( ui.fab );
	}

	function init() {
		if ( ! document.querySelector( '[data-vhh-annotatable]' ) ) { return; }
		buildFab();
		buildPanel();

		// Site-wide glance: annotations left on other pages, so switching
		// pages never hides feedback — each card links back to its article.
		VHH.api.listOthers().then( function ( data ) {
			otherItems = ( data.annotations || [] ).filter( function ( a ) { return a.post !== cfg.postId; } );
			renderList();
		} ).catch( function () { /* non-critical — leave the section empty */ } );

		VHH.bus.on( 'items:changed', renderList );
		VHH.bus.on( 'ready', renderList );
		VHH.bus.on( 'focus:card', function ( id ) {
			setOpen( true );
			renderList();
			var card = ui.list.querySelector( '.vhh-card[data-annotation-id="' + id + '"]' );
			if ( card ) {
				card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
				card.classList.add( 'vhh-card--pulse' );
				setTimeout( function () { card.classList.remove( 'vhh-card--pulse' ); }, 1600 );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
