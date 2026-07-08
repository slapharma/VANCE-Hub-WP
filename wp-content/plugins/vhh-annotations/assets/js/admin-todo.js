/**
 * VHH Annotations — wp-admin modal for reviewing an AI edit.
 *
 * [data-vhh-open-modal]  — open a modal that's already populated (preview exists).
 * [data-vhh-generate]    — AJAX-generate a preview, then open the modal with the
 *                          result injected. One click, no page reload.
 * [data-vhh-close-modal] / backdrop / Esc — close.
 */
(function () {
	'use strict';

	function open( modal ) {
		if ( modal ) {
			modal.hidden = false;
			document.body.classList.add( 'vhh-modal-open' );
		}
	}
	function close( modal ) {
		if ( modal ) {
			modal.hidden = true;
			document.body.classList.remove( 'vhh-modal-open' );
		}
	}

	function generate( btn ) {
		var todo   = btn.getAttribute( 'data-vhh-generate' );
		var modal  = document.getElementById( btn.getAttribute( 'data-vhh-modal' ) );
		var review = modal ? modal.querySelector( '#vhh-ai-review' ) : null;
		if ( ! modal || ! review || typeof window.VHH_ADMIN === 'undefined' ) { return; }

		review.innerHTML = '<div class="vhh-modal-body"><p class="vhh-generating"><span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>' +
			( window.VHH_ADMIN.generating || 'Generating…' ) + '</p></div>';
		open( modal );

		var body = new URLSearchParams();
		body.set( 'action', 'vhh_ai_generate' );
		body.set( 'nonce', window.VHH_ADMIN.nonce );
		body.set( 'todo', todo );

		fetch( window.VHH_ADMIN.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			if ( res && res.success && res.data && res.data.html ) {
				review.innerHTML = res.data.html;
			} else {
				var msg = ( res && res.data && res.data.message ) || ( window.VHH_ADMIN.failed || 'Generation failed' );
				review.innerHTML = '<div class="vhh-modal-body"><div class="notice notice-error" style="padding:8px 12px;"><p>' +
					String( msg ).replace( /</g, '&lt;' ) + '</p></div></div>';
			}
		} ).catch( function ( err ) {
			review.innerHTML = '<div class="vhh-modal-body"><div class="notice notice-error" style="padding:8px 12px;"><p>' +
				( window.VHH_ADMIN.failed || 'Generation failed' ) + ': ' + String( err.message || err ).replace( /</g, '&lt;' ) + '</p></div></div>';
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var gen = e.target.closest( '[data-vhh-generate]' );
		if ( gen ) { e.preventDefault(); generate( gen ); return; }

		var opener = e.target.closest( '[data-vhh-open-modal]' );
		if ( opener ) {
			e.preventDefault();
			open( document.getElementById( opener.getAttribute( 'data-vhh-open-modal' ) ) );
			return;
		}
		if ( e.target.closest( '[data-vhh-close-modal]' ) || ( e.target.classList && e.target.classList.contains( 'vhh-modal-backdrop' ) ) ) {
			e.preventDefault();
			close( e.target.closest( '.vhh-modal' ) );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			var m = document.querySelector( '.vhh-modal:not([hidden])' );
			if ( m ) { close( m ); }
		}
	} );
})();
