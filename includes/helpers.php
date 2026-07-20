<?php
/**
 * Bare Bones SEO Helper Functions
 * 
 * Path: includes/helpers.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get SEO metadata for a specific post with sensible defaults.
 *
 * @param int $post_id The ID of the post.
 * @return array Sanitized SEO metadata.
 */
function bare_bones_seo_get_page_meta( $post_id ) {
	$title  = get_post_meta( $post_id, BARE_BONES_SEO_META_TITLE, true );
	$desc   = get_post_meta( $post_id, BARE_BONES_SEO_META_DESC, true );
	$schema = get_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, true );
	$index  = get_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, true );

	// If the indexing option is empty (never set), default it to 'index' or 'yes'
	if ( '' === $index ) {
		$index = 'yes';
	}

	return array(
		'title'  => sanitize_text_field( $title ),
		'desc'   => sanitize_text_field( $desc ),
		'schema' => $schema, // Raw text area storage for JSON schema
		'index'  => sanitize_key( $index ),
	);
}

/**
 * Save SEO metadata for a specific post.
 *
 * @param int   $post_id The ID of the post.
 * @param array $data    The array of data to save.
 */
function bare_bones_seo_update_page_meta( $post_id, $data ) {
	if ( isset( $data['title'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_TITLE, sanitize_text_field( $data['title'] ) );
	}
	if ( isset( $data['desc'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_DESC, sanitize_text_field( $data['desc'] ) );
	}
	if ( isset( $data['schema'] ) ) {
		// Keep raw text input for JSON schema, but run it through safe post-filtering
		update_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, wp_kses_post( $data['schema'] ) );
	}
	if ( isset( $data['should_index'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, sanitize_key( $data['should_index'] ) );
	}
}

/**
 * Log 404 errors in database
 */
function bbseo_log_404_error() {
	if ( ! is_404() ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'bbseo_404_logs';

	$requested_url = esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	$referer       = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '';

	// Simple exclusion array for common vulnerability scanner paths
	$bot_patterns = array(
		'.env', 'wp-config', 'xmlrpc.php', 'wp-admin', '.git', 'eval-stdin.php', 
		'phpunit', 'autodiscover.xml', '.well-known', 'wp-login.php', '.php'
	);

	foreach ( $bot_patterns as $pattern ) {
		if ( false !== stripos( $requested_url, $pattern ) ) {
			return; // Skip logging bot-probing noise
		}
	}

	// Check if already logged
	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM `$table_name` WHERE url = %s", $requested_url ) );

	if ( $existing ) {
		$wpdb->update(
			$table_name,
			array(
				'hits'          => $existing->hits + 1,
				'last_accessed' => current_time( 'mysql' ),
				'referer'       => $referer,
			),
			array( 'id' => $existing->id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	} else {
		$wpdb->insert(
			$table_name,
			array(
				'url'           => $requested_url,
				'hits'          => 1,
				'last_accessed' => current_time( 'mysql' ),
				'referer'       => $referer,
			),
			array( '%s', '%d', '%s', '%s' )
		);
	}
}
add_action( 'template_redirect', 'bbseo_log_404_error' );

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
