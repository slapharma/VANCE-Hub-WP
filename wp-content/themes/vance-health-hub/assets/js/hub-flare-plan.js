/**
 * Flare plan card: print, the step-by-step builder, "How am I today?", the
 * character counters, and focus on load.
 *
 * Every part is an enhancement. The print buttons, the quick check and the
 * counters are hidden in the markup and shown here, so with no script the member
 * never sees a control that does nothing. With no script the plan form shows all
 * its boxes.
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
	// The groups this script opened so the plan prints in full. They are closed again after the print.
	var openedForPrint = [];

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

	// Open every group of the card, so the now and plan groups print in full. The CSS
	// hides the appointment group in the plan print.
	function openGroupsForPrint( part ) {
		Array.prototype.forEach.call( part.querySelectorAll( 'details.vhd-flare__group' ), function ( group ) {
			if ( ! group.open ) {
				group.open = true;
				openedForPrint.push( group );
			}
		} );
	}

	function closeGroupsAfterPrint() {
		openedForPrint.forEach( function ( group ) {
			group.open = false;
		} );
		openedForPrint = [];
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
		if ( 'plan' === which ) {
			openGroupsForPrint( part );
		}
		document.documentElement.classList.add( 'vhd-printing' );
		part.classList.add( 'vhd-print-target' );
		for ( var node = part.parentElement; node; node = node.parentElement ) {
			node.classList.add( 'vhd-print-path' );
		}
	}

	function unmarkPrint() {
		printTarget = '';
		closeGroupsAfterPrint();
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
		nav.appendChild( back );
		nav.appendChild( next );
		form.insertBefore( label, steps[ 0 ] );
		// Back and Next sit before the last step, so the Save button comes after them in the DOM.
		form.insertBefore( nav, steps[ total - 1 ] );

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
				// be hidden now. Focus goes to the step label.
				label.focus();
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

	/* ---- Optional sections in the edit form ---------------------------------- */

	// True when a field has text, a date or a number in it. Checkboxes do not count.
	function fieldHasValue( field ) {
		var inputs = field.querySelectorAll( 'input, textarea, select' );
		return Array.prototype.some.call( inputs, function ( input ) {
			if ( 'checkbox' === input.type || 'radio' === input.type || 'hidden' === input.type ) {
				return false;
			}
			return '' !== String( input.value ).trim();
		} );
	}

	// An optional section is hidden while its "More sections" box is not ticked and it is empty.
	// A field with a value is never hidden. With no script every field shows.
	function initOptional( root ) {
		Array.prototype.forEach.call( root.querySelectorAll( '[data-vhd-sec-toggle]' ), function ( toggle ) {
			var id    = toggle.getAttribute( 'data-vhd-sec-toggle' );
			var field = root.querySelector( '.vhd-flare__field--opt[data-vhd-sec="' + id + '"]' );
			if ( ! field ) {
				return;
			}
			function sync() {
				field.hidden = ! toggle.checked && ! fieldHasValue( field );
			}
			toggle.addEventListener( 'change', sync );
			sync();
		} );
	}

	/* ---- How am I today?------------------------------------------------ */

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

		// A slider counts as moved once the member changes it. Compare needs all three.
		var moved      = {};
		var movedCount = 0;

		function updateButton() {
			button.disabled = movedCount < sliders.length;
		}
		updateButton();

		Array.prototype.forEach.call( sliders, function ( slider ) {
			var key = slider.getAttribute( 'data-vhd-q' );
			var out = box.querySelector( 'output[for="' + slider.id + '"]' );
			function touched() {
				if ( out ) {
					out.textContent = slider.value;
				}
				slider.setAttribute( 'aria-valuetext', ( config.valuetext || '%s of 4' ).replace( '%s', slider.value ) );
				if ( ! moved[ key ] ) {
					moved[ key ] = true;
					movedCount++;
					updateButton();
				}
			}
			// 'input' does not fire when the member taps or presses a key and the
			// value stays at 0. A tap or a key press on the slider is also an answer.
			[ 'input', 'change', 'pointerup' ].forEach( function ( type ) {
				slider.addEventListener( type, touched );
			} );
			// Only the keys that set a slider value count. Tab moves the focus to the
			// next slider and must not mark it as answered.
			slider.addEventListener( 'keydown', function ( e ) {
				var keys = [ 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'PageUp', 'PageDown' ];
				if ( keys.indexOf( e.key ) !== -1 ) {
					touched();
				}
			} );
		} );

		button.addEventListener( 'click', function () {
			var today = {};
			var line  = [];
			Array.prototype.forEach.call( sliders, function ( slider ) {
				var key = slider.getAttribute( 'data-vhd-q' );
				today[ key ] = parseInt( slider.value, 10 ) || 0;
				line.push( ( labels[ key ] || key ) + ' ' + today[ key ] );
			} );
			while ( result.firstChild ) {
				result.removeChild( result.firstChild );
			}
			result.appendChild( el( 'p', '', ( config.today || 'Today' ) + ': ' + line.join( ', ' ) ) );

			var last = config.last_v;
			if ( last ) {
				var lastLine = [];
				Object.keys( today ).forEach( function ( key ) {
					lastLine.push( ( labels[ key ] || key ) + ' ' + last[ key ] );
				} );
				result.appendChild( el( 'p', '', ( config.last || '%s' ).replace( '%s', config.last_at || '' ) + ': ' + lastLine.join( ', ' ) ) );
			}
			result.appendChild( el( 'p', 'vhd-flare__quick-follow', config.follow || '' ) );

			// Point to the member's own plan. No verdict: the member decides.
			// The early signs and first steps are in the same group as the quick check.
			var plan = document.querySelector( '.vhd-flare__plan--core' ) || document.querySelector( '.vhd-flare__plan' );
			if ( plan ) {
				plan.classList.add( 'is-highlight' );
				var calm = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
				if ( plan.scrollIntoView ) {
					plan.scrollIntoView( { behavior: calm ? 'auto' : 'smooth', block: 'nearest' } );
				}
			}
			// Focus last, so the result stays in view after the plan is shown.
			result.focus();
		} );
	}

	/* ---- character counters --------------------------------------------- */

	function initCount( counter ) {
		var target = document.getElementById( counter.getAttribute( 'data-count-for' ) || '' );
		if ( ! target ) {
			return;
		}
		var max      = counter.getAttribute( 'data-count-max' ) || '';
		var template = counter.getAttribute( 'data-count-text' ) || '%d of %d characters';

		function show() {
			// Count code points, as the server does, so one emoji counts once.
			var n = Array.from( target.value ).length;
			counter.textContent = template.replace( '%d', String( n ) ).replace( '%d', max );
		}

		counter.hidden = false;
		target.addEventListener( 'input', show );
		show();
	}

	/* ---- focus on load -------------------------------------------------- */

	function initFlash() {
		var flash = document.getElementById( 'vhd-flare-flash' );
		if ( ! flash ) {
			return;
		}
		flash.focus();
		if ( flash.scrollIntoView ) {
			flash.scrollIntoView( { block: 'center' } );
		}
	}

	function initSubtabs() {
		var active = document.querySelector( '.vhd-subtabs .is-active' );
		if ( active && active.scrollIntoView ) {
			active.scrollIntoView( { inline: 'center', block: 'nearest' } );
		}
	}

	function init() {
		initPrint();
		var card = document.querySelector( '.vhd-flare' );
		if ( card ) {
			initOptional( card );
		}
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-vhd-wizard]' ), initWizard );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-vhd-flare-quick]' ), initQuick );
		Array.prototype.forEach.call( document.querySelectorAll( '.vhd-flare__count[data-count-for]' ), initCount );
		initFlash();
		initSubtabs();
	}

	window.addEventListener( 'beforeprint', markPrint );
	window.addEventListener( 'afterprint', unmarkPrint );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
