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
