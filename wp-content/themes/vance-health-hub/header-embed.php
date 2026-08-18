<?php
/**
 * Chromeless header for tools opened inside the unified glass tool modal
 * (inc/tool-modal.php). Loaded via get_header( 'embed' ) from the tool page
 * templates when the request carries ?tool_embed=1.
 *
 * It deliberately omits the site header, nav and footer — just enough document
 * shell for wp_head() (so main.css + tool assets enqueue) and wp_body_open().
 * The site chrome lives on the parent page around the modal.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.png' ); ?>">
    <?php wp_head(); ?>
    <style>
        /* Chromeless: fill the modal iframe, no page chrome. */
        html, body { margin: 0; padding: 0; background: #fff; min-height: 100%; }
        body.vance-tool-embed { overflow-x: hidden; }
    </style>
</head>
<body <?php body_class( 'vance-tool-embed' ); ?>>
<?php wp_body_open(); ?>
