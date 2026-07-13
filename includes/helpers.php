<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/bare-bones-seo.php'), 'bare_bones_seo_add_action_links');
function bare_bones_seo_add_action_links($links) {
    $doc_link = '<a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer">Docs & Guides</a>';
    array_unshift($links, $doc_link);
    return $links;
}

function bare_bones_seo_detect_conflicts() {
    $competitors = array('wpseo_init', 'rank_math_launcher', 'aioseo_init');
    foreach ($competitors as $hook) {
        if (did_action($hook) || has_action($hook)) { return true; }
    }
    if (class_exists('WPSEO_Options') || class_exists('RankMath') || class_exists('AIOSEO\Plugin\AIOSEO')) {
        return true;
    }
    return false;
}

function bare_bones_seo_get_page_meta($post_id) {
    return array(
        'title'       => get_post_meta($post_id, '_bare_bones_seo_title', true),
        'desc'        => get_post_meta($post_id, '_bare_bones_seo_desc', true),
        'schema'      => get_post_meta($post_id, '_bare_bones_seo_schema', true),
        'should_index'=> get_post_meta($post_id, '_bare_bones_seo_should_index', true) !== 'no',
    );
}

function bare_bones_seo_update_page_meta($post_id, $data) {
    if (isset($data['title'])) update_post_meta($post_id, '_bare_bones_seo_title', sanitize_text_field($data['title']));
    if (isset($data['desc'])) update_post_meta($post_id, '_bare_bones_seo_desc', sanitize_textarea_field($data['desc']));
    if (isset($data['schema'])) update_post_meta($post_id, '_bare_bones_seo_schema', wp_kses_post($data['schema']));
    if (isset($data['should_index'])) update_post_meta($post_id, '_bare_bones_seo_should_index', $data['should_index'] === 'no' ? 'no' : 'yes');
}

add_action('wp_head', 'bare_bones_seo_inject_frontend_tags', 1);
function bare_bones_seo_inject_frontend_tags() {
    if (is_admin() || bare_bones_seo_detect_conflicts()) return;

    global $post;
    $global_options = get_option('bare_bones_seo_global_map', array());
    $post_type = get_post_type();
    
    $section_indexable = isset($global_options[$post_type]) ? $global_options[$post_type] === 'yes' : true;
    $page_indexable = true;

    if (is_singular() && isset($post->ID)) {
        $meta = bare_bones_seo_get_page_meta($post->ID);
        $page_indexable = $meta['should_index'];
        
        if (!empty($meta['title'])) {
            echo '<title>' . esc_html($meta['title']) . '</title>' . "\n";
            remove_theme_support('title-tag');
        }
        if (!empty($meta['desc'])) {
            echo '<meta name="description" content="' . esc_attr($meta['desc']) . '">' . "\n";
        }
        if (!empty($meta['schema'])) {
            echo $meta['schema'] . "\n";
        }
    }

    if (!$section_indexable || !$page_indexable) {
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }
}

add_filter('wp_sitemaps_posts_entry', 'bare_bones_seo_filter_sitemap_rows', 10, 2);
function bare_bones_seo_filter_sitemap_rows($entry, $post) {
    $meta = bare_bones_seo_get_page_meta($post->ID);
    return (!$meta['should_index']) ? array() : $entry;
}

add_filter('wp_sitemaps_post_types', 'bare_bones_seo_filter_global_sitemap_sections');
function bare_bones_seo_filter_global_sitemap_sections($post_types) {
    $global_options = get_option('bare_bones_seo_global_map', array());
    foreach ($post_types as $type => $object) {
        if (isset($global_options[$type]) && $global_options[$type] === 'no') {
            unset($post_types[$type]);
        }
    }
    return $post_types;
}
