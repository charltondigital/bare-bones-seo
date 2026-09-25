<?php
/**
 * Runs only when the plugin is deleted from the Plugins screen — never on
 * deactivation, and never on a normal page load.
 *
 * Removes the 404 log and nothing else. Titles, descriptions, schema,
 * redirects, tracking scripts and Indexation settings are kept, so deleting
 * and reinstalling to troubleshoot loses no real work.
 *
 * The main plugin isn't loaded here, so names are written out rather than
 * taken from its constants (BARE_BONES_SEO_DB_VERSION_OPTION and the table
 * name in bare_bones_seo_install()).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop the current site's 404 log. The version option goes with it: without
 * it, the plugin recreates the table on next load (see the admin_init check
 * in bare-bones-seo.php). Leaving it behind would skip that rebuild on a
 * reinstall that bypasses activation, and every 404 would hit a missing table.
 */
function bare_bones_seo_uninstall_site() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'bbseo_404_logs' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- removing the plugin's own table on uninstall.
	delete_option( 'bare_bones_seo_db_version' );
}

// Each site on a network has its own table, and uninstall runs only once.
if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $bare_bones_seo_site_id ) {
		switch_to_blog( $bare_bones_seo_site_id );
		bare_bones_seo_uninstall_site();
		restore_current_blog();
	}
} else {
	bare_bones_seo_uninstall_site();
}
