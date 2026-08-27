<?php
/**
 * 404 template — before this existed, a bad URL (including the two
 * dead-tool pages removed 2026-08-07) fell through to index.php's generic
 * "No content found." with no heading, no branding, and a <title> identical
 * to the homepage's. This gives visitors an actual "page not found" message,
 * the site's normal header/footer, and a way back in.
 */
get_header();
?>

<div class="container" style="padding: 100px 20px; text-align: center;">
    <p style="font-size: 15px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--primary-color); margin: 0 0 12px;">404 error</p>
    <h1 style="font-size: 40px; font-weight: 800; color: var(--secondary-color); margin: 0 0 16px;">Oops, this page isn't active right now</h1>
    <p style="font-size: 17px; color: var(--text-light); max-width: 560px; margin: 0 auto 36px; line-height: 1.7;">
        The page you're looking for may have moved, been renamed, or is temporarily unavailable. Let's get you back on track.
    </p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 700; border-radius: var(--radius-control, 6px); text-decoration: none;">Home</a>
</div>

<?php get_footer(); ?>
