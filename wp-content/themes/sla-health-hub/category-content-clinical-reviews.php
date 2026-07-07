<?php
/**
 * Category template: Clinical Reviews (slug: content-clinical-reviews).
 *
 * Renders this category's posts grouped by sub-category (child category), each
 * group with its own Customizer-selected layout and description block. All
 * shared logic lives in template-parts/subcategory-grouped-archive.php.
 *
 * @package sla-health-hub
 */

get_header();
get_template_part( 'template-parts/subcategory-grouped-archive' );
get_footer();
