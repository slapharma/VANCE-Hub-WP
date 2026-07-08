/**
 * VHH Annotations — wp-admin modal for reviewing an AI edit.
 * Delegated, dependency-free open/close for [data-vhh-open-modal] triggers.
 */
(function () {
	'use strict';

	function close( modal ) {
		if ( modal ) {
			modal.hidden = true;
			document.body.classList.remove( 'vhh-modal-open' );
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-vhh-open-modal]' );
		if ( opener ) {
			e.preventDefault();
			var m = document.getElementById( opener.getAttribute( 'data-vhh-open-modal' ) );
			if ( m ) {
				m.hidden = false;
				document.body.classList.add( 'vhh-modal-open' );
			}
			return;
		}
		if ( e.target.closest( '[data-vhh-close-modal]' ) || ( e.target.classList && e.target.classList.contains( 'vhh-modal-backdrop' ) ) ) {
			e.preventDefault();
			close( e.target.closest( '.vhh-modal' ) );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			var open = document.querySelector( '.vhh-modal:not([hidden])' );
			if ( open ) { close( open ); }
		}
	} );
})();
