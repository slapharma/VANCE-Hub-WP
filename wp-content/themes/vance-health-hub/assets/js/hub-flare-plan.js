/**
 * Flare plan card: print, the step-by-step builder, and "How am I today?".
 *
 * Every part is an enhancement. The print buttons and the quick check are hidden
 * in the markup and shown here, so with no script the member never sees a
 * control that does nothing. With no script the plan form shows all its boxes.
 *
 * Print: the layout is CSS (@media print in hub-dashboard.css). Before printing
 * this script marks the part to print and every ancestor of it, so the CSS can
 * drop the rest of the page and print no blank pages. The part is the plan card,
 * or the appointment sheet when the member prints from it.
 *
 * Nothing here sends data. The quick check reads three sliders and the last
 * check-in from the page, and writes text into the page.
 */
( function () {
	'use strict';

	var printTarget = '';

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	/* ---- print ---------------------------------------------------------- */

	function printablePart( which ) {
		if ( 'sheet' === which ) {
			var box = document.querySelector( '.vhd-flare__sheetbox[open]' );
			return box ? box.querySelector( '.vhd-flare-sheet' ) : null;
		}
		return document.querySelector( '.vhd-flare' );
	}

	function markPrint() {
		// Print from the browser menu: the sheet when it is open, else the plan.
		var which = printTarget || ( document.querySelector( '.vhd-flare__sheetbox[open]' ) ? 'sheet' : 'plan' );
		var part  = printablePart( which );
		// Not when the card is in a hidden panel (another sub-tab is open): the
		// member prints the page they see.
		if ( ! part || part.closest( '[hidden]' ) ) {
			return;
		}
		document.documentElement.classList.add( 'vhd-printing' );
		part.classList.add( 'vhd-print-target' );
		for ( var node = part.parentElement; node; node = node.parentElement ) {
			node.classList.add( 'vhd-print-path' );
		}
	}

	function unmarkPrint() {
		printTarget = '';
		document.documentElement.classList.remove( 'vhd-printing' );
		Array.prototype.forEach.call( document.querySelectorAll( '.vhd-print-path, .vhd-print-target' ), function ( node ) {
			node.classList.remove( 'vhd-print-path' );
			node.classList.remove( 'vhd-print-target' );
		} );
	}

	function initPrint() {
		var buttons = document.querySelectorAll( '[data-vhd-flare-print]' );
		if ( ! buttons.length || typeof window.print !== 'function' ) {
			return;
		}
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.hidden = false;
			button.addEventListener( 'click', function () {
				printTarget = 'sheet' === button.getAttribute( 'data-vhd-flare-print' ) ? 'sheet' : 'plan';
				window.print();
			} );
		} );
	}

	/* ---- step-by-step builder ------------------------------------------- */

	function initWizard( form ) {
		var steps = Array.prototype.slice.call( form.querySelectorAll( '.vhd-flare__field, .vhd-flare__final' ) );
		if ( steps.length < 2 ) {
			return;
		}
		var total    = steps.length;
		var current  = 0;
		var template = form.getAttribute( 'data-step-text' ) || 'Step %1$s of %2$s';
		var label    = el( 'p', 'vhd-flare__step' );
		var nav      = el( 'div', 'vhd-flare__nav' );
		var back     = el( 'button', 'vhd-btn vhd-btn--outline', form.getAttribute( 'data-back-text' ) || 'Back' );
		var next     = el( 'button', 'vhd-btn vhd-btn--purple', form.getAttribute( 'data-next-text' ) || 'Next' );
		back.type = 'button';
		next.type = 'button';
		label.setAttribute( 'aria-live', 'polite' );
		nav.appendChild( back );
		nav.appendChild( next );
		form.insertBefore( label, steps[ 0 ] );
		form.appendChild( nav );

		label.tabIndex = -1;

		function show( moved ) {
			steps.forEach( function ( step, n ) {
				step.hidden = n !== current;
			} );
			label.textContent = template.replace( '%1$s', String( current + 1 ) ).replace( '%2$s', String( total ) );
			back.hidden = 0 === current;
			next.hidden = current === total - 1;
			if ( moved ) {
				// Keep keyboard focus on the page: the button that was clicked may
				// be hidden now. Go to the first field of the step, else to the label.
				var focus = steps[ current ].querySelector( 'textarea, input:not([type="hidden"]), button' ) || label;
				focus.focus();
			}
		}

		back.addEventListener( 'click', function () {
			current = Math.max( 0, current - 1 );
			show( true );
		} );
		next.addEventListener( 'click', function () {
			current = Math.min( total - 1, current + 1 );
			show( true );
		} );
		show( false );
	}

	/* ---- How am I today? ------------------------------------------------ */

	function initQuick( box ) {
		var config;
		try {
			config = JSON.parse( box.getAttribute( 'data-config' ) || '{}' );
		} catch ( e ) {
			return;
		}
		var labels  = config.labels || {};
		var sliders = box.querySelectorAll( '[data-vhd-q]' );
		var button  = box.querySelector( '[data-vhd-quick-show]' );
		var result  = box.querySelector( '[data-vhd-quick-result]' );
		if ( ! sliders.length || ! button || ! result ) {
			return;
		}
		box.hidden = false;

		Array.prototype.forEach.call( sliders, function ( slider ) {
			var out = box.querySelector( 'output[for="' + slider.id + '"]' );
			slider.addEventListener( 'input', function () {
				if ( out ) {
					out.textContent = slider.value;
				}
			} );
		} );

		button.addEventListener( 'click', function () {
			var today  = {};
			var higher = [];
			var line   = [];
			Array.prototype.forEach.call( sliders, function ( slider ) {
				var key = slider.getAttribute( 'data-vhd-q' );
				today[ key ] = parseInt( slider.value, 10 ) || 0;
				line.push( ( labels[ key ] || key ) + ' ' + today[ key ] );
			} );
			while ( result.firstChild ) {
				result.removeChild( result.firstChild );
			}
			result.appendChild( el( 'p', '', ( config.today || 'Today' ) + ': ' + line.join( ', ' ) + '.' ) );

			var last = config.last_v;
			if ( last ) {
				var lastLine = [];
				Object.keys( today ).forEach( function ( key ) {
					lastLine.push( ( labels[ key ] || key ) + ' ' + last[ key ] );
					if ( today[ key ] > last[ key ] ) {
						higher.push( labels[ key ] || key );
					}
				} );
				result.appendChild( el( 'p', '', ( config.last || '%s' ).replace( '%s', config.last_at || '' ) + ': ' + lastLine.join( ', ' ) + '.' ) );
				result.appendChild( el( 'p', '', higher.length ? ( config.higher || '%s' ).replace( '%s', higher.join( ', ' ) ) : ( config.same || '' ) ) );
			} else if ( config.nolast ) {
				result.appendChild( el( 'p', '', config.nolast ) );
			}
			result.appendChild( el( 'p', 'vhd-flare__quick-follow', config.follow || '' ) );

			// Point to the member's own plan. No verdict: the member decides.
			var plan = document.querySelector( '.vhd-flare__plan' );
			if ( plan ) {
				plan.classList.add( 'is-highlight' );
				var calm = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
				if ( plan.scrollIntoView ) {
					plan.scrollIntoView( { behavior: calm ? 'auto' : 'smooth', block: 'nearest' } );
				}
			}
		} );
	}

	function init() {
		initPrint();
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-vhd-wizard]' ), initWizard );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-vhd-flare-quick]' ), initQuick );
	}

	window.addEventListener( 'beforeprint', markPrint );
	window.addEventListener( 'afterprint', unmarkPrint );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
