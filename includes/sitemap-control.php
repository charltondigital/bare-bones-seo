<?php
/**
 * Sitemap engine. Removes sections and individual posts from the core
 * WordPress sitemap, using the shared resolver in helpers.php.
 * Noindex is handled separately in noindex-control.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Site level: drop whole providers (all-or-nothing, per WP's design) ---

add_filter( 'wp_sitemaps_post_types', 'bare_bones_seo_filter_sitemap_post_types' );
function bare_bones_seo_filter_sitemap_post_types( $post_types ) {
	foreach ( array_keys( $post_types ) as $key ) {
		if ( bare_bones_seo_state_removes_from_sitemap( bare_bones_seo_get_site_state( $key ) ) ) {
			unset( $post_types[ $key ] );
		}
	}
	return $post_types;
}

add_filter( 'wp_sitemaps_taxonomies', 'bare_bones_seo_filter_sitemap_taxonomies' );
function bare_bones_seo_filter_sitemap_taxonomies( $taxonomies ) {
	foreach ( array_keys( $taxonomies ) as $key ) {
		if ( bare_bones_seo_state_removes_from_sitemap( bare_bones_seo_get_site_state( $key ) ) ) {
			unset( $taxonomies[ $key ] );
		}
	}
	return $taxonomies;
}

// Author archives. Defaults to 'yes' (included) via the resolver, like every
// other section — no longer the odd-one-out that defaulted to removed.
add_filter( 'wp_sitemaps_add_provider', 'bare_bones_seo_filter_sitemap_users', 10, 2 );
function bare_bones_seo_filter_sitemap_users( $provider, $name ) {
	if ( 'users' === $name && bare_bones_seo_state_removes_from_sitemap( bare_bones_seo_get_site_state( 'user' ) ) ) {
		return false;
	}
	return $provider;
}

// --- Page level: drop individual posts opted out via their meta box ---
// Only runs for post types whose provider is still present (if the whole type
// was removed above, this query never fires for it).

add_filter( 'wp_sitemaps_posts_query_args', 'bare_bones_seo_exclude_posts_from_sitemap', 10, 2 );
function bare_bones_seo_exclude_posts_from_sitemap( $args, $post_type ) {
	$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

	// Keep posts that never set the option OR whose value isn't a removal state.
	// The NOT EXISTS branch is essential: without it, NOT IN would drop every
	// post that has no meta row at all (i.e. almost the whole sitemap).
	$meta_query[] = array(
		'relation' => 'OR',
		array(
			'key'     => BARE_BONES_SEO_META_INDEX,
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => BARE_BONES_SEO_META_INDEX,
			'value'   => array( 'no', 'complicated_sitemap' ),
			'compare' => 'NOT IN',
		),
	);

	$args['meta_query'] = $meta_query;
	return $args;
}
