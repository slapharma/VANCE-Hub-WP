<?php
/**
 * Mobile bottom navigation bar (Phase 2.1).
 *
 * App-style persistent tab bar shown only on phones (≤767px via CSS) and only
 * when enabled in Appearance → Customize → Mobile Experience. Rendering is
 * gated server-side by vance_mobile_bottomnav_active() so it never outputs HTML
 * on the dashboard or when the toggle is OFF.
 *
 * Icons are inline SVG (not emoji) for consistent cross-platform rendering and
 * accessibility. Styles live in assets/css/mobile-components.css.
 *
 * @package sla-health-hub
 */

if ( ! function_exists( 'vance_mobile_bottomnav_active' ) || ! vance_mobile_bottomnav_active() ) {
    return;
}

// Resolve destinations once.
$vbn_home      = home_url( '/' );
$vbn_tools     = home_url( '/tools-resources/' );
$vbn_askai     = home_url( '/ask-ai/' );
$vbn_dashboard = is_user_logged_in() ? home_url( '/dashboard/' ) : wp_login_url( home_url( '/dashboard/' ) );

// Active-tab detection.
$vbn_is_home  = is_front_page();
$vbn_is_tools = is_page( 'tools-resources' );
$vbn_is_askai = is_page( 'ask-ai' );
$vbn_is_dash  = is_page( 'dashboard' );

/**
 * Tiny helper: print aria-current + active class when $cond is true.
 */
$vbn_active = function ( $cond ) {
    if ( $cond ) {
        echo ' is-active" aria-current="page';
    }
};
?>
<nav class="vance-bottom-nav" aria-label="<?php esc_attr_e( 'Primary mobile navigation', 'sla-health-hub' ); ?>">
    <a class="vbn-tab<?php $vbn_active( $vbn_is_home ); ?>" href="<?php echo esc_url( $vbn_home ); ?>">
        <svg class="vbn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 10.5 12 3l9 7.5" /><path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5" /></svg>
        <span class="vbn-label"><?php esc_html_e( 'Home', 'sla-health-hub' ); ?></span>
    </a>
    <a class="vbn-tab<?php $vbn_active( $vbn_is_tools ); ?>" href="<?php echo esc_url( $vbn_tools ); ?>">
        <svg class="vbn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.1-2.1 2.6-2.4Z" /></svg>
        <span class="vbn-label"><?php esc_html_e( 'Tools', 'sla-health-hub' ); ?></span>
    </a>
    <a class="vbn-tab<?php $vbn_active( $vbn_is_askai ); ?>" href="<?php echo esc_url( $vbn_askai ); ?>">
        <svg class="vbn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12a8 8 0 0 1-8 8H7l-4 3 1.2-4.6A8 8 0 1 1 21 12Z" /><circle cx="8.5" cy="12" r="1.1" /><circle cx="12" cy="12" r="1.1" /><circle cx="15.5" cy="12" r="1.1" /></svg>
        <span class="vbn-label"><?php esc_html_e( 'Ask AI', 'sla-health-hub' ); ?></span>
    </a>
    <a class="vbn-tab<?php $vbn_active( $vbn_is_dash ); ?>" href="<?php echo esc_url( $vbn_dashboard ); ?>">
        <svg class="vbn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="5" /><rect x="14" y="12" width="7" height="9" /><rect x="3" y="16" width="7" height="5" /></svg>
        <span class="vbn-label"><?php esc_html_e( 'Dashboard', 'sla-health-hub' ); ?></span>
    </a>
    <button class="vbn-tab vbn-more-toggle" type="button" aria-expanded="false" aria-controls="vbn-more-sheet">
        <svg class="vbn-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" /></svg>
        <span class="vbn-label"><?php esc_html_e( 'More', 'sla-health-hub' ); ?></span>
    </button>
</nav>

<div class="vbn-sheet-backdrop" hidden></div>
<div id="vbn-more-sheet" class="vbn-sheet" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'More navigation', 'sla-health-hub' ); ?>" hidden>
    <div class="vbn-sheet-handle" aria-hidden="true"></div>
    <ul class="vbn-sheet-links">
        <li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About Us', 'sla-health-hub' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/healthcare-quiz/' ) ); ?>"><?php esc_html_e( 'Health Quiz', 'sla-health-hub' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/turn-evidence-into-action/' ) ); ?>"><?php esc_html_e( 'Get Started', 'sla-health-hub' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact', 'sla-health-hub' ); ?></a></li>
        <?php if ( is_user_logged_in() ) : ?>
            <li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign Out', 'sla-health-hub' ); ?></a></li>
        <?php else : ?>
            <li><a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"><?php esc_html_e( 'Join for Free', 'sla-health-hub' ); ?></a></li>
            <li><a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Sign In', 'sla-health-hub' ); ?></a></li>
        <?php endif; ?>
    </ul>
</div>

<script>
( function () {
    var toggle   = document.querySelector( '.vbn-more-toggle' );
    var sheet    = document.getElementById( 'vbn-more-sheet' );
    var backdrop = document.querySelector( '.vbn-sheet-backdrop' );
    if ( ! toggle || ! sheet || ! backdrop ) { return; }

    function setOpen( open ) {
        toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
        sheet.classList.toggle( 'is-open', open );
        backdrop.classList.toggle( 'is-open', open );
        if ( open ) {
            sheet.hidden = false;
            backdrop.hidden = false;
        } else {
            // Allow the CSS transition to finish before hiding from AT.
            window.setTimeout( function () {
                if ( ! sheet.classList.contains( 'is-open' ) ) {
                    sheet.hidden = true;
                    backdrop.hidden = true;
                }
            }, 250 );
        }
        document.body.style.overflow = open ? 'hidden' : '';
    }

    toggle.addEventListener( 'click', function () {
        setOpen( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
    } );
    backdrop.addEventListener( 'click', function () { setOpen( false ); } );
    sheet.querySelectorAll( 'a' ).forEach( function ( a ) {
        a.addEventListener( 'click', function () { setOpen( false ); } );
    } );
    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' && sheet.classList.contains( 'is-open' ) ) { setOpen( false ); }
    } );
} )();
</script>
