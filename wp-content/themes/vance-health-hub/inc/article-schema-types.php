<?php
/**
 * Give each post the Article subtype it actually is, and type the two pages
 * that have a more specific schema type than WebPage.
 *
 * AIOSEO types every post as BlogPosting. That is valid but undifferentiated:
 * a dated news item, an evergreen patient guide and a summary of a journal
 * paper are three different things, and only one of them is a blog post.
 *
 *   content-healthcare-news   (32)  -> NewsArticle
 *   content-clinical-reviews  (28)  -> Article
 *   content-gastro-living     (89)  -> Article
 *
 * Of these, NewsArticle on the news items is the change that carries weight —
 * it is the type Google's news surfaces understand, and it is only correct
 * because those posts really are dated reporting. Swapping BlogPosting for
 * Article elsewhere is semantic tidying: both are valid Article subtypes and
 * Google treats them alike, but a clinically reviewed condition guide is not
 * a blog post and should not say it is.
 *
 * Historical note: the site used to stamp NewsArticle on all 215 singular URLs,
 * including the accessibility statement, from a second JSON-LD block in
 * ai-visibility.php. Turning that block off removed it everywhere. This file is
 * about restoring NewsArticle deliberately, to the 32 posts that earn it.
 *
 * Filters AIOSEO's graph selection rather than printing schema of our own, for
 * the same reason as inc/medical-schema.php: one graph per page.
 *
 * @package Vance_Health_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Article subtype per top-level category slug.
 *
 * @return array
 */
function vance_article_schema_map() {
	return array(
		'content-healthcare-news'  => 'NewsArticle',
		'content-clinical-reviews' => 'Article',
		'content-gastro-living'    => 'Article',
	);
}

/**
 * The Article graph this post should use, or '' to leave AIOSEO's choice alone.
 *
 * Categories are walked up to their top-level parent, so a post filed only
 * under a child such as "Tests & Treatments" still resolves.
 *
 * @param  int $postId Post ID.
 * @return string
 */
function vance_article_schema_type_for( $postId ) {
	$map  = vance_article_schema_map();
	$cats = get_the_category( $postId );
	if ( empty( $cats ) ) {
		return '';
	}

	foreach ( $cats as $cat ) {
		// Walk to the top-level ancestor.
		$term = $cat;
		$hops = 0;
		while ( $term && (int) $term->parent > 0 && $hops < 6 ) {
			$parent = get_category( $term->parent );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$term = $parent;
			$hops++;
		}

		if ( $term && isset( $map[ $term->slug ] ) ) {
			return $map[ $term->slug ];
		}
	}

	return '';
}

/**
 * Pages whose schema type is more specific than WebPage, keyed by slug.
 *
 * @return array
 */
function vance_page_schema_map() {
	return array(
		'about-us'   => 'AboutPage',
		'contact-us' => 'ContactPage',
	);
}

/**
 * Swap AIOSEO's chosen graph for a more accurate one.
 *
 * @param  array $graphs Graph names AIOSEO intends to output.
 * @return array
 */
function vance_article_schema_graphs( $graphs ) {
	if ( ! is_array( $graphs ) || ! is_singular() ) {
		return $graphs;
	}

	$postId = get_queried_object_id();
	if ( ! $postId ) {
		return $graphs;
	}

	if ( is_singular( 'post' ) ) {
		$type = vance_article_schema_type_for( $postId );
		if ( $type === '' || $type === 'BlogPosting' ) {
			return $graphs;
		}
		foreach ( $graphs as $i => $graph ) {
			if ( $graph === 'BlogPosting' ) {
				$graphs[ $i ] = $type;
			}
		}

		return $graphs;
	}

	if ( is_page() ) {
		$slug = get_post_field( 'post_name', $postId );
		$map  = vance_page_schema_map();
		if ( ! isset( $map[ $slug ] ) ) {
			return $graphs;
		}
		foreach ( $graphs as $i => $graph ) {
			if ( $graph === 'WebPage' ) {
				$graphs[ $i ] = $map[ $slug ];
			}
		}
	}

	return $graphs;
}
add_filter( 'aioseo_schema_graphs', 'vance_article_schema_graphs' );
