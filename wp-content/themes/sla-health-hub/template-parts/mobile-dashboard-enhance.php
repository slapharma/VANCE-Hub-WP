<?php
/**
 * Mobile dashboard enhancements (Phase 2.4) — sidebar backdrop.
 *
 * The dashboard sidebar (#sidebar.dash-sidebar) slides in via the `.active`
 * class (toggleSidebar() in page-dashboard.php) but has no backdrop, so tapping
 * outside it does nothing (audit §4.2 / P1). This enhancer injects a backdrop,
 * watches the sidebar's class, and closes the drawer on outside tap — WITHOUT
 * modifying page-dashboard.php.
 *
 * Included from footer-dashboard.php (dashboard only). Self-gated by the
 * Customizer toggle. Backdrop styles live in mobile-components.css §2.4.
 *
 * @package sla-health-hub
 */

if ( ! vance_get_theme_mod( 'vance_mobile_dashboard_enhance', false ) ) {
    return;
}
?>
<div class="dash-sidebar-backdrop" hidden></div>
<script>
( function () {
    var sidebar = document.getElementById( 'sidebar' );
    if ( ! sidebar ) { return; }
    var backdrop = document.querySelector( '.dash-sidebar-backdrop' );
    if ( ! backdrop ) { return; }

    function sync() {
        var open = sidebar.classList.contains( 'active' );
        backdrop.classList.toggle( 'is-open', open );
        if ( open ) {
            backdrop.hidden = false;
        } else {
            window.setTimeout( function () {
                if ( ! sidebar.classList.contains( 'active' ) ) { backdrop.hidden = true; }
            }, 250 );
        }
    }

    // Close the drawer when the backdrop is tapped. Prefer the theme's own
    // toggle so any related state stays consistent; fall back to class removal.
    backdrop.addEventListener( 'click', function () {
        if ( typeof window.toggleSidebar === 'function' ) {
            window.toggleSidebar();
        } else {
            sidebar.classList.remove( 'active' );
        }
        sync();
    } );

    // Close on Escape too.
    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' && sidebar.classList.contains( 'active' ) ) {
            if ( typeof window.toggleSidebar === 'function' ) { window.toggleSidebar(); }
            else { sidebar.classList.remove( 'active' ); }
            sync();
        }
    } );

    // Watch the sidebar's class so the backdrop tracks open/close however it was
    // triggered (☰ open button, ✕ close button, or our backdrop).
    if ( 'MutationObserver' in window ) {
        new MutationObserver( sync ).observe( sidebar, { attributes: true, attributeFilter: [ 'class' ] } );
    }
    sync();
} )();
</script>
<?php
// no closing tag
