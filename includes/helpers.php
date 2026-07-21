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
		// Store raw JSON. wp_kses_post is for HTML and would corrupt it; safety
		// is enforced on output, where it's validated and re-encoded with tag
		// escaping. update_post_meta unslashes for us.
		update_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, trim( (string) $data['schema'] ) );
	}
	if ( isset( $data['should_index'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, sanitize_key( $data['should_index'] ) );
	}
}

/**
 * Log 404s. Stores the request path only (no host — it's always this site,
 * and it's rebuilt with home_url() on display); skips common bot/scanner probes.
 */
function bbseo_log_404_error() {
	if ( ! is_404() ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'bbseo_404_logs';

	$path    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

	// Skip bot/scanner probe noise so the table stays lean.
	$bot_patterns = array(
		'.env', 'wp-config', 'xmlrpc.php', 'wp-admin', '.git', 'eval-stdin.php',
		'phpunit', 'autodiscover.xml', '.well-known', 'wp-login.php', '.php',
	);
	foreach ( $bot_patterns as $pattern ) {
		if ( false !== stripos( $path, $pattern ) ) {
			return;
		}
	}

	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM `$table_name` WHERE url = %s", $path ) );

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
				'url'           => $path,
				'hits'          => 1,
				'last_accessed' => current_time( 'mysql' ),
				'referer'       => $referer,
			),
			array( '%s', '%d', '%s', '%s' )
		);
	}
}
add_action( 'template_redirect', 'bbseo_log_404_error' );
