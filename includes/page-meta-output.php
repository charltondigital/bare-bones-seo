<?php
/**
 * Page-level meta output. Emits the custom title, meta description, and JSON-LD
 * schema set per post via the meta box or bulk manager. Front-end only, and only
 * on singular views — archives, home, and search have no single post to describe,
 * so on those requests this does one is_singular() check and nothing else.
 *
 * Reads the same stored meta both admin screens write, so it serves both.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Replace the document <title> with the custom SEO title when one is set.
// (A filter, not an echo — we short-circuit WordPress's title before it renders
// rather than fighting the theme's <title>.)
add_filter( 'pre_get_document_title', 'bare_bones_seo_filter_document_title' );
function bare_bones_seo_filter_document_title( $title ) {
	if ( ! is_singular() ) {
		return $title;
	}
	$custom = get_post_meta( get_queried_object_id(), BARE_BONES_SEO_META_TITLE, true );
	// Stored via sanitize_text_field, so it's safe plain text; return as-is.
	return ( is_string( $custom ) && '' !== trim( $custom ) ) ? $custom : $title;
}

// Emit the meta description and JSON-LD schema into <head>.
add_action( 'wp_head', 'bare_bones_seo_output_head_meta', 1 );
function bare_bones_seo_output_head_meta() {
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();

	$desc = get_post_meta( $post_id, BARE_BONES_SEO_META_DESC, true );
	if ( is_string( $desc ) && '' !== trim( $desc ) ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}

	$schema = get_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, true );
	if ( is_string( $schema ) && '' !== trim( $schema ) ) {
		$decoded = json_decode( $schema );
		// Fail safe: only output valid JSON. Re-encode with JSON_HEX_TAG so a
		// stray </script> in the data becomes \u003C... and can't break out of
		// the script element (search engines decode it back normally).
		if ( null !== $decoded ) {
			echo '<script type="application/ld+json">'
				. wp_json_encode( $decoded, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
				. '</script>' . "\n";
		}
	}
}
