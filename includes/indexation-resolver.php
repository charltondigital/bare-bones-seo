<?php
/**
 * Indexation resolver — the shared source of truth for both engines
 * (noindex + sitemap). Pure functions, no hooks.
 *
 * State ladder, least -> most restrictive:
 *   'yes'                 -> indexed, in sitemap
 *   'complicated_sitemap' -> indexed, not in sitemap
 *   'no'                  -> noindexed (and out of sitemap automatically)
 *
 * Ceiling rule: effective state = the more restrictive of site + page.
 * Unknown/legacy values normalize to 'yes' (safe default: never a silent deindex).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force any stored value to one of the three canonical tokens.
function bare_bones_seo_normalize_state( $value ) {
	return in_array( $value, array( 'no', 'complicated_sitemap' ), true ) ? $value : 'yes';
}

// The ceiling rule: whichever of the two states sits higher on the ladder.
function bare_bones_seo_more_restrictive( $a, $b ) {
	$rank = array( 'yes' => 0, 'complicated_sitemap' => 1, 'no' => 2 );
	$a    = bare_bones_seo_normalize_state( $a );
	$b    = bare_bones_seo_normalize_state( $b );
	return ( $rank[ $a ] >= $rank[ $b ] ) ? $a : $b;
}

// Site-level state for a section key (post type, taxonomy, 'user', system page).
function bare_bones_seo_get_site_state( $section_key ) {
	$map = get_option( BARE_BONES_SEO_OPTION_GLOBAL_MAP, array() );
	$raw = ( is_array( $map ) && isset( $map[ $section_key ] ) ) ? $map[ $section_key ] : 'yes';
	return bare_bones_seo_normalize_state( $raw );
}

// Page-level state stored on an individual post.
function bare_bones_seo_get_page_state( $post_id ) {
	return bare_bones_seo_normalize_state( get_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, true ) );
}

// Effective state for a post = ceiling of its post type's site state + its page state.
// (A post follows its post type only, not the category/tag archives it belongs to.)
function bare_bones_seo_get_effective_post_state( $post_id ) {
	$post_type  = get_post_type( $post_id );
	$site_state = $post_type ? bare_bones_seo_get_site_state( $post_type ) : 'yes';
	return bare_bones_seo_more_restrictive( $site_state, bare_bones_seo_get_page_state( $post_id ) );
}

function bare_bones_seo_state_is_noindex( $state ) {
	return ( 'no' === bare_bones_seo_normalize_state( $state ) );
}

// Anything past 'yes' on the ladder is out of the sitemap.
function bare_bones_seo_state_removes_from_sitemap( $state ) {
	return ( 'yes' !== bare_bones_seo_normalize_state( $state ) );
}
