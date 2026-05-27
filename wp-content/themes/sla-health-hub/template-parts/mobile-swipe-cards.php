<?php
/**
 * Swipeable homepage category cards (Phase 2.3).
 *
 * Progressive enhancement: finds the EXISTING category-cards grid on the front
 * page and upgrades it to a horizontal scroll-snap carousel with dot indicators
 * on phones only. front-page.php is NOT modified — if this enhancer is disabled
 * or the markup changes, the grid simply renders as before.
 *
 * Renders only on the front page and only when enabled in Customizer → Mobile
 * Experience. Dot styles live in assets/css/mobile-components.css §2.3.
 *
 * @package sla-health-hub
 */

if ( ! is_front_page() || ! vance_get_theme_mod( 'vance_mobile_swipecards_enable', false ) ) {
    return;
}
?>
<script>
( function () {
    var mq = window.matchMedia( '(max-width: 767.98px)' );

    function enhance() {
        var firstCard = document.querySelector( '.category-cards-section .vance-category-card' );
        if ( ! firstCard ) { return; }
        var grid = firstCard.parentElement;
        if ( ! grid || grid.getAttribute( 'data-vsw-ready' ) === '1' ) { return; }

        var cards = Array.prototype.filter.call( grid.children, function ( el ) {
            return el.classList && el.classList.contains( 'vance-category-card' );
        } );
        if ( cards.length < 2 ) { return; }

        grid.setAttribute( 'data-vsw-ready', '1' );
        grid.classList.add( 'vsw-grid' );
        grid.setAttribute( 'role', 'region' );
        grid.setAttribute( 'aria-label', 'Category carousel' );

        // Override the inline `display:grid` layout with a snap row (inline wins,
        // so we must set inline styles directly).
        grid.style.display = 'flex';
        grid.style.gridTemplateColumns = 'none';
        grid.style.flexWrap = 'nowrap';
        grid.style.overflowX = 'auto';
        grid.style.scrollSnapType = 'x mandatory';
        grid.style.webkitOverflowScrolling = 'touch';
        grid.style.scrollPaddingLeft = '16px';

        cards.forEach( function ( card ) {
            card.style.scrollSnapAlign = 'start';
            card.style.flex = '0 0 66%';   // ~1.5-card peek so swipeability is discoverable
            card.style.maxWidth = '66%';
        } );

        // Build dot indicators.
        var dots = document.createElement( 'div' );
        dots.className = 'vsw-dots';
        dots.setAttribute( 'aria-hidden', 'true' );
        cards.forEach( function ( _, i ) {
            var d = document.createElement( 'span' );
            d.className = 'vsw-dot' + ( i === 0 ? ' is-active' : '' );
            dots.appendChild( d );
        } );
        grid.parentNode.insertBefore( dots, grid.nextSibling );
        var dotEls = dots.querySelectorAll( '.vsw-dot' );

        // Track the most-visible card and light its dot.
        if ( 'IntersectionObserver' in window ) {
            var io = new IntersectionObserver( function ( entries ) {
                entries.forEach( function ( e ) {
                    if ( e.isIntersecting ) {
                        var idx = cards.indexOf( e.target );
                        if ( idx > -1 ) {
                            dotEls.forEach( function ( dot, j ) {
                                dot.classList.toggle( 'is-active', j === idx );
                            } );
                        }
                    }
                } );
            }, { root: grid, threshold: 0.6 } );
            cards.forEach( function ( c ) { io.observe( c ); } );
        }
    }

    function init() {
        if ( mq.matches ) { enhance(); }
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
    // If the viewport crosses into mobile after load, enhance then.
    if ( mq.addEventListener ) {
        mq.addEventListener( 'change', function ( e ) { if ( e.matches ) { enhance(); } } );
    }
} )();
</script>
<?php
// no closing tag
