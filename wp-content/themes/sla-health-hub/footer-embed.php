<?php
/**
 * Chromeless footer counterpart to header-embed.php. Emits wp_footer() (so tool
 * scripts run) and closes the document. No site footer, no site-wide modals —
 * in particular NOT inc/tool-modal.php, so a tool embedded in the modal can
 * never recursively mount another tool modal inside itself.
 */
?>
<?php wp_footer(); ?>
</body>
</html>
