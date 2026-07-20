<?php
/**
 * Noindex engine. Emits a noindex robots directive on front-end views the
 * user has marked noindex, using the shared resolver in helpers.php.
 * Sitemap removal is handled separately in sitemap-control.php.
 *
 * Requires WordPress 5.7+ (wp_robots).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bare_bones_seo_apply_noindex( $robots ) {
	if ( is_admin() ) {
		return $robots;
	}

	// If another SEO plugin is managing output, stay out of its way.
	if ( function_exists( 'bare_bones_seo_detect_sitemap_conflict' ) ) {
		$conflict = bare_bones_seo_detect_sitemap_conflict();
		if ( ! empty( $conflict['conflict'] ) ) {
			return $robots;
		}
	}

	$noindex = false;

	if ( is_singular() ) {
		// Resolver merges the post type's site setting with the page setting.
		$noindex = bare_bones_seo_state_is_noindex(
			bare_bones_seo_get_effective_post_state( get_queried_object_id() )
		);
	} else {
		// Archives + system pages are site-level only. Noindex if any section
		// that applies to this view says so (more restrictive wins).
		foreach ( bare_bones_seo_current_section_keys() as $key ) {
			if ( bare_bones_seo_state_is_noindex( bare_bones_seo_get_site_state( $key ) ) ) {
				$noindex = true;
				break;
			}
		}
	}

	if ( $noindex ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'bare_bones_seo_apply_noindex' );

// Section keys that apply to the current (non-singular) view. More than one can
// apply at once — e.g. page 2 of a category is both 'category' and 'paged'.
function bare_bones_seo_current_section_keys() {
	$keys = array();

	if ( is_404() ) {
		$keys[] = '404';
	}
	if ( is_search() ) {
		$keys[] = 'search';
	}
	if ( is_date() ) {
		$keys[] = 'date';
	}
	if ( is_paged() ) {
		$keys[] = 'paged';
	}
	if ( is_author() ) {
		$keys[] = 'user';
	}

	if ( is_category() ) {
		$keys[] = 'category';
	} elseif ( is_tag() ) {
		$keys[] = 'post_tag';
	} elseif ( is_tax() ) {
		$term = get_queried_object();
		if ( $term && isset( $term->taxonomy ) ) {
			$keys[] = $term->taxonomy;
		}
	}

	if ( is_post_type_archive() ) {
		$obj = get_queried_object();
		if ( $obj && isset( $obj->name ) ) {
			$keys[] = $obj->name;
		}
	}

	return $keys;
}
