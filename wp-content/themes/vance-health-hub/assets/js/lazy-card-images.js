/**
 * Swap in [data-bg] thumbnails as they near the viewport.
 *
 * archive.php and template-parts/subcategory-grouped-archive.php mark card
 * thumbnails past the first few as data-bg instead of an inline
 * background-image, because CSS background-image has no native
 * loading="lazy" equivalent — see the "audit P5"/"41,000px" comments in
 * those two files for why that mattered here (a grouped archive with
 * pagination off can run to hundreds of cards on one page).
 */
( function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) ) {
		// No IO support: just show everything now rather than never.
		document.querySelectorAll( '[data-bg]' ).forEach( function ( el ) {
			el.style.backgroundImage = 'url(' + el.getAttribute( 'data-bg' ) + ')';
			el.removeAttribute( 'data-bg' );
		} );
		return;
	}

	var io = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}
				var el = entry.target;
				el.style.backgroundImage = 'url(' + el.getAttribute( 'data-bg' ) + ')';
				el.removeAttribute( 'data-bg' );
				io.unobserve( el );
			} );
		},
		{ rootMargin: '400px 0px' }
	);

	document.querySelectorAll( '[data-bg]' ).forEach( function ( el ) {
		io.observe( el );
	} );
} )();
