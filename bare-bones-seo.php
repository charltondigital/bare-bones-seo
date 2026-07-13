<?php
/**
 * Plugin Name: Bare Bones SEO
 * Plugin URI:  https://github.com/charltondigital/bare-bones-seo
 * Description: A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.
 * Version:     1.0.3
 * Author:      Charlton Digital
 * License:     GPLv2 or later
 * Text Domain: bare-bones-seo
 *
 * @package BareBonesSEO
 */

if (!defined('ABSPATH')) {
    exit;
}

// Post meta keys
define('BARE_BONES_SEO_META_TITLE',  '_bare_bones_seo_title');
define('BARE_BONES_SEO_META_DESC',   '_bare_bones_seo_desc');
define('BARE_BONES_SEO_META_SCHEMA', '_bare_bones_seo_schema');
define('BARE_BONES_SEO_META_INDEX',  '_bare_bones_seo_should_index');

// Option keys
define('BARE_BONES_SEO_OPTION_GLOBAL_MAP', 'bare_bones_seo_global_map');

// Nonce actions
define('BARE_BONES_SEO_NONCE_PAGE',       'bare_bones_seo_save_nonce');
define('BARE_BONES_SEO_NONCE_GLOBAL_MAP', 'bb_global_map_nonce');
define('BARE_BONES_SEO_NONCE_BULK_AJAX',  'bb_bulk_manager_nonce');

// AJAX action
define('BARE_BONES_SEO_AJAX_ACTION', 'bb_seo_bulk_save');

// Plugin paths
define('BARE_BONES_SEO_PATH',    plugin_dir_path(__FILE__));
define('BARE_BONES_SEO_URL',     plugin_dir_url(__FILE__));
define('BARE_BONES_SEO_VERSION', '1.0.3');

// Load files
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';

/**
 * Activation hook — no hard blocks.
 * Sitemap conflicts flagged on admin page instead.
 *
 * @since 1.0.0
 */
register_activation_hook(__FILE__, 'bare_bones_seo_activation_check');
function bare_bones_seo_activation_check() {}

/**
 * Admin menu registration.
 *
 * Uses a custom SVG skull icon from assets/icon.svg.
 * WordPress inverts the icon color automatically on active/hover states
 * when using a base64-encoded SVG with fill="black".
 *
 * @since 1.0.0
 */
add_action('admin_menu', 'bare_bones_seo_register_menus');
function bare_bones_seo_register_menus() {
    // Load SVG and encode as base64 for use as menu icon
    $svg_path = BARE_BONES_SEO_PATH . 'assets/icon.svg';
    $svg_icon = 'dashicons-shield'; // fallback

    if (file_exists($svg_path)) {
        $svg_content = file_get_contents($svg_path);
        $svg_icon    = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
    }

    add_menu_page(
        'Site Level Search Engine Instructions — Bare Bones SEO',
        'Bare Bones SEO',
        'manage_options',
        'bare-bones-seo',
        'bare_bones_seo_render_global_map_screen',
        $svg_icon,
        80
    );

    add_submenu_page(
        'bare-bones-seo',
        'SEO Page Settings: Bulk Manager — Bare Bones SEO',
        'Bulk Manager',
        'manage_options',
        'bare-bones-seo-bulk',
        'bare_bones_seo_render_bulk_manager_screen'
    );
}

/**
 * Enqueue admin styles and scripts on ALL admin pages.
 *
 * Files are tiny (a few KB each) so the performance impact is negligible.
 * Loading everywhere eliminates hook-suffix mismatch bugs on post edit
 * screens and plugin admin pages.
 *
 * @since 1.0.3
 */
add_action('admin_enqueue_scripts', 'bare_bones_seo_enqueue_assets');
function bare_bones_seo_enqueue_assets() {
    wp_enqueue_style(
        'bare-bones-seo-admin',
        BARE_BONES_SEO_URL . 'assets/admin-style.css',
        array(),
        BARE_BONES_SEO_VERSION
    );

    wp_enqueue_script(
        'bare-bones-seo-admin',
        BARE_BONES_SEO_URL . 'assets/admin-script.js',
        array('jquery'),
        BARE_BONES_SEO_VERSION,
        true
    );
}
