<?php
/**
 * Custom 301 redirects for moved pages and legacy/external URLs — the cases
 * WordPress's native _wp_old_slug (rename) redirects can't cover.
 *
 * Gated on is_404(): a working URL is never a redirect candidate, so normal
 * page loads pay only one is_404() check. The map lookup runs only on requests
 * that already failed. Stored as a [ source path => target ] map in the
 * bare_bones_seo_redirects option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'bare_bones_seo_apply_redirects' );
function bare_bones_seo_apply_redirects() {
	if ( ! is_404() ) {
		return;
	}

	$map = get_option( 'bare_bones_seo_redirects', array() );
	if ( empty( $map ) || ! is_array( $map ) ) {
		return;
	}

	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	// Reduced to a path and used only as a lookup key into the redirect map.
	$path = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( '' !== $path && isset( $map[ $path ] ) ) {
		// wp_redirect (not wp_safe_redirect) so admin-configured external
		// targets are allowed; these are deliberate config, not user input.
		wp_redirect( $map[ $path ], 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}
}
