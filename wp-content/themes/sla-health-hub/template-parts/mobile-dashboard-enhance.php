<?php
/**
 * Mobile dashboard enhancements (Phase 2.4 + Phase 3).
 *
 * Included from footer-dashboard.php (dashboard only). Two independent,
 * default-OFF Customizer toggles, both safe progressive enhancements that do
 * NOT modify page-dashboard.php:
 *
 *   vance_mobile_dashboard_enhance   → sidebar backdrop (tap-to-close),
 *                                       swipe-left-to-close, pull-to-refresh
 *   vance_mobile_dashboard_accordion → collapsible cards (skips form cards)
 *
 * Styles live in assets/css/mobile-components.css §2.4.
 *
 * @package sla-health-hub
 */

$vde_enhance   = vance_get_theme_mod( 'vance_mobile_dashboard_enhance', false );
$vde_accordion = vance_get_theme_mod( 'vance_mobile_dashboard_accordion', false );

if ( ! $vde_enhance && ! $vde_accordion ) {
    return;
}
?>
<?php if ( $vde_enhance ) : ?>
<div class="dash-sidebar-backdrop" hidden></div>
<div class="dash-ptr-indicator" aria-hidden="true"><span class="dash-ptr-spinner"></span></div>
<?php endif; ?>
<script>
( function () {
    var ENHANCE   = <?php echo $vde_enhance ? 'true' : 'false'; ?>;
    var ACCORDION = <?php echo $vde_accordion ? 'true' : 'false'; ?>;
    var isMobile  = window.matchMedia( '(max-width: 767.98px)' );

    /* ---- Sidebar backdrop + swipe-to-close (Phase 2.4 / 3.3) ---- */
    function initSidebar() {
        var sidebar = document.getElementById( 'sidebar' );
        if ( ! sidebar ) { return; }
        var backdrop = document.querySelector( '.dash-sidebar-backdrop' );

        function close() {
            if ( typeof window.toggleSidebar === 'function' ) { window.toggleSidebar(); }
            else { sidebar.classList.remove( 'active' ); }
        }
        function sync() {
            if ( ! backdrop ) { return; }
            var open = sidebar.classList.contains( 'active' );
            backdrop.classList.toggle( 'is-open', open );
            if ( open ) { backdrop.hidden = false; }
            else { window.setTimeout( function () { if ( ! sidebar.classList.contains( 'active' ) ) { backdrop.hidden = true; } }, 250 ); }
        }

        if ( backdrop ) {
            backdrop.addEventListener( 'click', function () { close(); sync(); } );
        }
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' && sidebar.classList.contains( 'active' ) ) { close(); sync(); }
        } );

        // Swipe-left on the open sidebar to close it.
        var sx = 0, sy = 0, tracking = false;
        sidebar.addEventListener( 'touchstart', function ( e ) {
            if ( ! sidebar.classList.contains( 'active' ) ) { return; }
            tracking = true; sx = e.touches[0].clientX; sy = e.touches[0].clientY;
        }, { passive: true } );
        sidebar.addEventListener( 'touchend', function ( e ) {
            if ( ! tracking ) { return; }
            tracking = false;
            var dx = e.changedTouches[0].clientX - sx;
            var dy = e.changedTouches[0].clientY - sy;
            if ( dx < -50 && Math.abs( dx ) > Math.abs( dy ) ) { close(); sync(); }
        }, { passive: true } );

        if ( 'MutationObserver' in window ) {
            new MutationObserver( sync ).observe( sidebar, { attributes: true, attributeFilter: [ 'class' ] } );
        }
        sync();
    }

    /* ---- Pull-to-refresh (Phase 3.2) ---- */
    function initPullToRefresh() {
        var indicator = document.querySelector( '.dash-ptr-indicator' );
        if ( ! indicator ) { return; }
        var startY = 0, pulling = false, dist = 0;
        var THRESHOLD = 70;

        window.addEventListener( 'touchstart', function ( e ) {
            if ( ! isMobile.matches ) { return; }
            var top = window.scrollY || document.documentElement.scrollTop || 0;
            if ( top <= 0 ) { pulling = true; startY = e.touches[0].clientY; dist = 0; }
        }, { passive: true } );

        window.addEventListener( 'touchmove', function ( e ) {
            if ( ! pulling ) { return; }
            dist = e.touches[0].clientY - startY;
            if ( dist > 0 ) {
                indicator.style.transform = 'translateY(' + Math.min( dist, THRESHOLD + 20 ) + 'px)';
                indicator.classList.toggle( 'is-armed', dist >= THRESHOLD );
            }
        }, { passive: true } );

        window.addEventListener( 'touchend', function () {
            if ( ! pulling ) { return; }
            pulling = false;
            if ( dist >= THRESHOLD ) {
                indicator.classList.add( 'is-refreshing' );
                window.location.reload();
            } else {
                indicator.style.transform = '';
                indicator.classList.remove( 'is-armed' );
            }
        }, { passive: true } );
    }

    /* ---- Collapsible cards (Phase 2.4 accordion) ---- */
    function initAccordion() {
        if ( ! isMobile.matches ) { return; }
        var cards = document.querySelectorAll( '.dash-content .d-card' );
        var openedFirst = false;
        Array.prototype.forEach.call( cards, function ( card ) {
            if ( card.getAttribute( 'data-acc-ready' ) === '1' ) { return; }
            // Never collapse cards that hold interactive controls.
            if ( card.querySelector( 'form, input, textarea, select' ) ) { return; }
            var header = card.querySelector( '.d-card-header' );
            if ( ! header ) { return; }

            card.setAttribute( 'data-acc-ready', '1' );
            card.classList.add( 'd-card-accordion' );
            header.setAttribute( 'role', 'button' );
            header.setAttribute( 'tabindex', '0' );

            // First eligible card stays open; the rest start collapsed.
            var open = ! openedFirst;
            openedFirst = true;
            card.classList.toggle( 'is-collapsed', ! open );
            header.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

            function toggle() {
                var collapsed = card.classList.toggle( 'is-collapsed' );
                header.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
            }
            header.addEventListener( 'click', toggle );
            header.addEventListener( 'keydown', function ( e ) {
                if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); toggle(); }
            } );
        } );
    }

    function run() {
        if ( ENHANCE ) { initSidebar(); initPullToRefresh(); }
        if ( ACCORDION ) { initAccordion(); }
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', run );
    } else {
        run();
    }
} )();
</script>
<?php
// no closing tag
