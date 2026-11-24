/**
 * Flare plan card: the print button.
 *
 * The button is hidden in the markup. This script shows it, so with no script
 * the member never sees a button that does nothing. The print layout is CSS
 * only (@media print in hub-dashboard.css): it shows the card and hides the rest.
 */
( function () {
	'use strict';

	function init() {
		var buttons = document.querySelectorAll( '[data-vhd-flare-print]' );
		if ( ! buttons.length || typeof window.print !== 'function' ) {
			return;
		}
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.hidden = false;
			button.addEventListener( 'click', function () {
				window.print();
			} );
		} );
	}

	// Print from the browser menu or the button: mark every ancestor of the card,
	// so the CSS can drop the rest of the page and print no blank pages.
	function markPrint() {
		var card = document.querySelector( '.vhd-flare' );
		if ( ! card ) {
			return;
		}
		document.documentElement.classList.add( 'vhd-printing' );
		for ( var node = card.parentElement; node; node = node.parentElement ) {
			node.classList.add( 'vhd-print-path' );
		}
	}

	function unmarkPrint() {
		document.documentElement.classList.remove( 'vhd-printing' );
		Array.prototype.forEach.call( document.querySelectorAll( '.vhd-print-path' ), function ( node ) {
			node.classList.remove( 'vhd-print-path' );
		} );
	}

	window.addEventListener( 'beforeprint', markPrint );
	window.addEventListener( 'afterprint', unmarkPrint );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
