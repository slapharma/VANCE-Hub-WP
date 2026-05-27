<?php
/**
 * Mobile sticky CTA bar (Phase 2.2).
 *
 * Slides up from the bottom of the viewport on phones after the reader scrolls
 * past ~30% of the page. Dismissable; the dismissal is remembered for the
 * session via localStorage (not user meta) so it doesn't nag on every scroll
 * but also doesn't persist forever.
 *
 * Renders only when enabled in Customizer → Mobile Experience and only when the
 * bottom nav is NOT showing (see vance_mobile_stickycta_active()). Styles live
 * in assets/css/mobile-components.css §2.2.
 *
 * @package sla-health-hub
 */

if ( ! function_exists( 'vance_mobile_stickycta_active' ) || ! vance_mobile_stickycta_active() ) {
    return;
}

$vsc_text = vance_get_theme_mod( 'vance_mobile_stickycta_text', 'Ready to take control of your gut health?' );
$vsc_btn  = vance_get_theme_mod( 'vance_mobile_stickycta_btn', 'Join for Free' );
$vsc_link = vance_get_theme_mod( 'vance_mobile_stickycta_link', home_url( '/register/' ) );

// A storage key that changes if the admin edits the copy, so a reworded CTA
// reappears even for visitors who dismissed the previous one.
$vsc_key = 'vanceStickyCta_' . substr( md5( $vsc_text . '|' . $vsc_btn . '|' . $vsc_link ), 0, 8 );

if ( empty( $vsc_btn ) || empty( $vsc_link ) ) {
    return; // Nothing actionable to show.
}
?>
<div class="vance-sticky-cta" data-vsc-key="<?php echo esc_attr( $vsc_key ); ?>" role="region" aria-label="<?php esc_attr_e( 'Call to action', 'sla-health-hub' ); ?>" hidden>
    <?php if ( ! empty( $vsc_text ) ) : ?>
        <p class="vsc-text"><?php echo esc_html( $vsc_text ); ?></p>
    <?php endif; ?>
    <a class="vsc-btn btn btn-primary" href="<?php echo esc_url( $vsc_link ); ?>"><?php echo esc_html( $vsc_btn ); ?></a>
    <button class="vsc-dismiss" type="button" aria-label="<?php esc_attr_e( 'Dismiss', 'sla-health-hub' ); ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
</div>

<script>
( function () {
    var bar = document.querySelector( '.vance-sticky-cta' );
    if ( ! bar ) { return; }
    var key = bar.getAttribute( 'data-vsc-key' );

    // Respect a prior dismissal for this exact CTA copy.
    try {
        if ( key && window.localStorage && window.localStorage.getItem( key ) === '1' ) { return; }
    } catch ( e ) { /* localStorage blocked — fall through and just show on scroll */ }

    var shown = false;
    function maybeShow() {
        if ( shown ) { return; }
        var doc = document.documentElement;
        var scrolled = ( window.scrollY || doc.scrollTop || 0 );
        var max = ( doc.scrollHeight - window.innerHeight );
        if ( max > 0 && ( scrolled / max ) >= 0.30 ) {
            shown = true;
            bar.hidden = false;
            // next frame so the transform transition runs
            window.requestAnimationFrame( function () { bar.classList.add( 'is-visible' ); } );
            window.removeEventListener( 'scroll', onScroll );
        }
    }
    function onScroll() { maybeShow(); }

    bar.querySelector( '.vsc-dismiss' ).addEventListener( 'click', function () {
        bar.classList.remove( 'is-visible' );
        window.setTimeout( function () { bar.hidden = true; }, 250 );
        try { if ( key && window.localStorage ) { window.localStorage.setItem( key, '1' ); } } catch ( e ) {}
    } );
    // Hide once the visitor reaches the CTA's destination context.
    bar.querySelector( '.vsc-btn' ).addEventListener( 'click', function () {
        try { if ( key && window.localStorage ) { window.localStorage.setItem( key, '1' ); } } catch ( e ) {}
    } );

    window.addEventListener( 'scroll', onScroll, { passive: true } );
    maybeShow();
} )();
</script>
