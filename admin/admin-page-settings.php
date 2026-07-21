<?php
/**
 * Page-level meta output. 
 * Emits titles, descriptions, schema, and tracking scripts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Replace the document <title>
add_filter( 'pre_get_document_title', 'bare_bones_seo_filter_document_title' );
function bare_bones_seo_filter_document_title( $title ) {
	if ( ! is_singular() ) {
		return $title;
	}
	$custom = get_post_meta( get_queried_object_id(), BARE_BONES_SEO_META_TITLE, true );
	return ( is_string( $custom ) && '' !== trim( $custom ) ) ? $custom : $title;
}

// Emit Meta Description and Schema
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
		if ( null !== $decoded ) {
			echo '<script type="application/ld+json">'
				. wp_json_encode( $decoded, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
				. '</script>' . "\n";
		}
	}
}

/**
 * --- TRACKING SCRIPTS OUTPUT ---
 */

// Hook tracking injection
add_action('wp_head', 'bare_bones_seo_inject_head_scripts', 0); // High priority for GSC/Analytics
add_action('wp_footer', 'bare_bones_seo_inject_footer_scripts', 99); // Low priority for Pixels

function bare_bones_seo_inject_head_scripts() {
    bare_bones_seo_output_scripts_by_location('head');
}

function bare_bones_seo_inject_footer_scripts() {
    bare_bones_seo_output_scripts_by_location('footer');
}

/**
 * Logic to merge and print scripts based on location (head/footer)
 */
function bare_bones_seo_output_scripts_by_location($location) {
    // 1. Get Global Scripts
    $global = get_option(BARE_BONES_SEO_OPTION_TRACKING, array());
    
    // 2. Get Page-Level Scripts
    $page = array();
    if (is_singular()) {
        $page = get_post_meta(get_queried_object_id(), BARE_BONES_SEO_META_TRACKING, true);
    }

    $all_scripts = array_merge(
        is_array($global) ? $global : array(), 
        is_array($page) ? $page : array()
    );

    if (empty($all_scripts)) return;

    foreach ($all_scripts as $script) {
        // Validation: Must be active and match the location hook
        if ($script['status'] !== 'active' || $script['loc'] !== $location) {
            continue;
        }

        // Scope check: If "home only" and we aren't on the home page, skip.
        if (isset($script['scope']) && $script['scope'] === 'home' && !is_front_page()) {
            continue;
        }

        // Print the code
        echo "\n<!-- BB SEO Tracking: " . esc_html($script['label']) . " -->\n";
        echo $script['code'] . "\n";
    }
}
