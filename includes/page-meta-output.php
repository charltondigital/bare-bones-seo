<?php
/**
 * Page-level meta output. 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Filter the <title> tag
add_filter( 'pre_get_document_title', 'bare_bones_seo_filter_document_title' );
function bare_bones_seo_filter_document_title( $title ) {
	if ( ! is_singular() ) {
		return $title;
	}
	$custom = get_post_meta( get_queried_object_id(), BARE_BONES_SEO_META_TITLE, true );
	return ( is_string( $custom ) && '' !== trim( $custom ) ) ? $custom : $title;
}

// Output Meta Description and Schema
add_action( 'wp_head', 'bare_bones_seo_output_head_meta', 1 );
function bare_bones_seo_output_head_meta() {
	if ( ! is_singular() ) {
		return;
	}
	$post_id = get_queried_object_id();
	$desc = get_post_meta( $post_id, BARE_BONES_SEO_META_DESC, true );
	if ( ! empty( $desc ) ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	$schema = get_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, true );
	if ( ! empty( $schema ) ) {
		$decoded = json_decode( $schema );
		if ( null !== $decoded ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $decoded, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	}
}

/**
 * --- TRACKING SCRIPTS OUTPUT ---
 */
add_action('wp_head', 'bare_bones_seo_inject_head_scripts', 0);
add_action('wp_footer', 'bare_bones_seo_inject_footer_scripts', 99);

function bare_bones_seo_inject_head_scripts() {
    bare_bones_seo_output_scripts_by_location('head');
}

function bare_bones_seo_inject_footer_scripts() {
    bare_bones_seo_output_scripts_by_location('footer');
}

function bare_bones_seo_output_scripts_by_location($location) {
    $global = get_option(BARE_BONES_SEO_OPTION_TRACKING, array());
    $page   = is_singular() ? get_post_meta(get_queried_object_id(), BARE_BONES_SEO_META_TRACKING, true) : array();
    
    $all = array_merge(is_array($global) ? $global : array(), is_array($page) ? $page : array());

    foreach ($all as $s) {
        if (!is_array($s) || empty($s['code'])) continue;
        if (($s['status'] ?? 'active') !== 'active') continue;
        if (($s['loc'] ?? 'head') !== $location) continue;
        if (($s['scope'] ?? 'all') === 'home' && !is_front_page()) continue;

        echo "\n" . $s['code'] . "\n";
    }
}
