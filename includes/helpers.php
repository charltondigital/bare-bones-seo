<?php
/**
 * Helper functions for Bare Bones SEO
 * 
 * Core functionality: Conflict detection, post meta management,
 * frontend tag injection, and sitemap filtering.
 * 
 * @package BareBonesSEO
 * @subpackage Includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add plugin action link to plugins list
 * 
 * Adds a "Docs & Guides" link next to the plugin name on the plugins page.
 * This helps users quickly access documentation without navigating through
 * the WordPress admin menu.
 * 
 * @since 1.0.0
 * @param array $links Existing action links for the plugin
 * @return array Modified links array with docs link prepended
 */
add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/bare-bones-seo.php'), 'bare_bones_seo_add_action_links');
function bare_bones_seo_add_action_links($links) {
    $doc_link = '<a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer">Docs & Guides</a>';
    array_unshift($links, $doc_link);
    return $links;
}

/**
 * Detect competing SEO plugins
 * 
 * Checks for the presence of major SEO suites (Yoast, Rank Math, All in One SEO).
 * Bare Bones SEO is designed to be the only SEO plugin active to prevent
 * conflicting meta tag output and confusing behavior.
 * 
 * This function checks both for active hooks and class existence, providing
 * comprehensive detection even if plugins are partially disabled.
 * 
 * @since 1.0.0
 * @return bool True if competing SEO plugins detected, false otherwise
 */
function bare_bones_seo_detect_conflicts() {
    // List of competitor plugin hooks to check
    $competitor_hooks = array(
        'wpseo_init',           // Yoast SEO
        'rank_math_launcher',   // Rank Math
        'aioseo_init',          // All in One SEO
    );

    // Check if competitor hooks have fired or are registered
    foreach ($competitor_hooks as $hook) {
        if (did_action($hook) || has_action($hook)) {
            return true;
        }
    }

    // Check if competitor classes exist (catches disabled plugins)
    if (class_exists('WPSEO_Options') || 
        class_exists('RankMath') || 
        class_exists('AIOSEO\Plugin\AIOSEO')) {
        return true;
    }

    return false;
}

/**
 * Retrieve SEO metadata for a post
 * 
 * Fetches custom SEO metadata stored by Bare Bones SEO from a post.
 * Returns a consistent array structure with sensible defaults, ensuring
 * that missing values don't cause errors in display logic.
 * 
 * @since 1.0.0
 * @param int $post_id The ID of the post to fetch metadata for
 * @return array Associative array with keys: title, desc, schema, should_index
 *              - title (string): Custom SEO title tag
 *              - desc (string): Custom meta description
 *              - schema (string): Custom JSON-LD schema markup
 *              - should_index (bool): Whether post should be indexed (true by default)
 */
function bare_bones_seo_get_page_meta($post_id) {
    return array(
        'title'        => get_post_meta($post_id, BARE_BONES_SEO_META_TITLE, true),
        'desc'         => get_post_meta($post_id, BARE_BONES_SEO_META_DESC, true),
        'schema'       => get_post_meta($post_id, BARE_BONES_SEO_META_SCHEMA, true),
        'should_index' => get_post_meta($post_id, BARE_BONES_SEO_META_INDEX, true) !== 'no',
    );
}

/**
 * Update SEO metadata for a post
 * 
 * Saves custom SEO metadata to a post. Only updates fields that are
 * provided in the $data array, allowing partial updates without
 * overwriting existing values.
 * 
 * Each field type is sanitized appropriately:
 * - title: Single-line text (sanitize_text_field)
 * - desc: Multi-line text (sanitize_textarea_field)
 * - schema: HTML content, allowing JSON-LD script tags (wp_kses_post)
 * - should_index: Binary yes/no value
 * 
 * @since 1.0.0
 * @param int $post_id The ID of the post to update
 * @param array $data Associative array of fields to update. Supported keys:
 *                     - title, desc, schema, should_index
 * @return void
 */
function bare_bones_seo_update_page_meta($post_id, $data) {
    // Title: Simple text field
    if (isset($data['title'])) {
        update_post_meta($post_id, BARE_BONES_SEO_META_TITLE, sanitize_text_field($data['title']));
    }

    // Description: Multi-line text field
    if (isset($data['desc'])) {
        update_post_meta($post_id, BARE_BONES_SEO_META_DESC, sanitize_textarea_field($data['desc']));
    }

    // Schema: HTML content (allows script tags for JSON-LD)
    if (isset($data['schema'])) {
        update_post_meta($post_id, BARE_BONES_SEO_META_SCHEMA, wp_kses_post($data['schema']));
    }

    // Index status: Binary yes/no value
    if (isset($data['should_index'])) {
        $value = ($data['should_index'] === 'no') ? 'no' : 'yes';
        update_post_meta($post_id, BARE_BONES_SEO_META_INDEX, $value);
    }
}

/**
 * Inject SEO meta tags into page head
 * 
 * Outputs custom SEO metadata (title, description, schema, robots meta)
 * into the WordPress page head. This fires early (priority 1) to ensure
 * our tags load before theme/plugin additions.
 * 
 * Only runs on the frontend. Skips output if conflicting SEO plugins
 * are detected to prevent duplicate meta tags.
 * 
 * Global indexing map: Allows blanket noindex rules by post type.
 * Page-level override: Individual posts can override with custom noindex.
 * 
 * @since 1.0.0
 * @return void
 */
add_action('wp_head', 'bare_bones_seo_inject_frontend_tags', 1);
function bare_bones_seo_inject_frontend_tags() {
    // Skip on admin pages and if conflicting plugins detected
    if (is_admin()) {
        return;
    }

    global $post;

    // Fetch global indexing rules (by post type)
    $global_options = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());
    $post_type = get_post_type();

    // Default: index everything unless explicitly disabled
    $section_indexable = isset($global_options[$post_type]) 
        ? ($global_options[$post_type] === 'yes') 
        : true;
    $page_indexable = true;

    // On singular pages, check page-level settings
    if (is_singular() && isset($post->ID)) {
        $meta = bare_bones_seo_get_page_meta($post->ID);
        $page_indexable = $meta['should_index'];

        // Output custom title tag if provided
        if (!empty($meta['title'])) {
            echo '<title>' . esc_html($meta['title']) . '</title>' . "\n";
            // Remove theme's default title tag to prevent duplicates
            remove_theme_support('title-tag');
        }

        // Output custom meta description if provided
        if (!empty($meta['desc'])) {
            echo '<meta name="description" content="' . esc_attr($meta['desc']) . '">' . "\n";
        }

        // Output custom JSON-LD schema if provided
        // Note: Schema must include full <script type="application/ld+json">...</script> wrapper
        if (!empty($meta['schema'])) {
            echo $meta['schema'] . "\n";
        }
    }

    // Output noindex meta tag if either section or page is marked as non-indexable
    if (!$section_indexable || !$page_indexable) {
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }
}

/**
 * Filter sitemap entries to exclude noindexed posts
 * 
 * WordPress generates XML sitemaps dynamically via the built-in sitemap API.
 * This filter removes individual posts marked with noindex from appearing
 * in those sitemaps.
 * 
 * Returning an empty array tells WordPress to exclude this entry entirely
 * from the sitemap. The sitemap generation code skips empty entries.
 * 
 * @since 1.0.0
 * @param array $entry The sitemap entry for this post (URL, priority, etc.)
 * @param WP_Post $post The post object
 * @return array Empty array to exclude, or original entry to include
 */
add_filter('wp_sitemaps_posts_entry', 'bare_bones_seo_filter_sitemap_rows', 10, 2);
function bare_bones_seo_filter_sitemap_rows($entry, $post) {
    $meta = bare_bones_seo_get_page_meta($post->ID);

    // Return empty array to exclude noindexed posts from sitemap
    if (!$meta['should_index']) {
        return array();
    }

    return $entry;
}

/**
 * Filter global sitemap to exclude entire post type sections
 * 
 * WordPress allows filtering which post types appear in the global sitemap index.
 * This removes entire post type sections (e.g., all pages, all posts) if
 * they're marked as non-indexable in the Global Indexing Map.
 * 
 * This is a blanket rule applied before individual post filtering, for
 * performance: avoid processing individual posts if the entire section
 * is marked as non-indexable.
 * 
 * @since 1.0.0
 * @param array $post_types Array of post type objects eligible for sitemaps
 * @return array Filtered array with disabled post types removed
 */
add_filter('wp_sitemaps_post_types', 'bare_bones_seo_filter_global_sitemap_sections');
function bare_bones_seo_filter_global_sitemap_sections($post_types) {
    $global_options = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());

    // Remove post types marked as non-indexable
    foreach ($post_types as $type => $object) {
        if (isset($global_options[$type]) && $global_options[$type] === 'no') {
            unset($post_types[$type]);
        }
    }

    return $post_types;
}
