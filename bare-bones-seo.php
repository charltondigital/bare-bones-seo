<?php
/**
 * Plugin Name: Bare Bones SEO
 * Plugin URI:  https://github.com/charltondigital/bare-bones-seo
 * Description: A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.
 * Version:     1.0.2
 * Author:      Charlton Digital
 * License:     GPLv2 or later
 * Text Domain: bare-bones-seo
 * 
 * @package BareBonesSEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * PLUGIN CONSTANTS
 * ============================================================================
 * Single source of truth for all field names and option keys.
 * Makes refactoring and maintenance trivial.
 */

// Post meta keys
define('BARE_BONES_SEO_META_TITLE', '_bare_bones_seo_title');
define('BARE_BONES_SEO_META_DESC', '_bare_bones_seo_desc');
define('BARE_BONES_SEO_META_SCHEMA', '_bare_bones_seo_schema');
define('BARE_BONES_SEO_META_INDEX', '_bare_bones_seo_should_index');

// Option keys (stored in wp_options)
define('BARE_BONES_SEO_OPTION_GLOBAL_MAP', 'bare_bones_seo_global_map');

// Nonce actions
define('BARE_BONES_SEO_NONCE_PAGE', 'bare_bones_seo_save_nonce');
define('BARE_BONES_SEO_NONCE_GLOBAL_MAP', 'bb_global_map_nonce');
define('BARE_BONES_SEO_NONCE_BULK_AJAX', 'bb_bulk_manager_nonce');

// AJAX action name
define('BARE_BONES_SEO_AJAX_ACTION', 'bb_seo_bulk_save');

// Plugin paths and URLs
define('BARE_BONES_SEO_PATH', plugin_dir_path(__FILE__));
define('BARE_BONES_SEO_URL', plugin_dir_url(__FILE__));
define('BARE_BONES_SEO_VERSION', '1.0.2');

/**
 * ============================================================================
 * PLUGIN INITIALIZATION
 * ============================================================================
 */

// Load helper functions
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';

// Load admin screens
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';

/**
 * Activation hook: Verify no competing SEO plugins are active
 * 
 * Bare Bones SEO is designed for fresh, clean WordPress installations.
 * We prevent activation if another SEO suite is already active to avoid
 * conflicting meta tag output and unpredictable behavior.
 * 
 * @since 1.0.0
 */
register_activation_hook(__FILE__, 'bare_bones_seo_activation_check');
function bare_bones_seo_activation_check() {
    if (bare_bones_seo_detect_conflicts()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<strong>☠️ Bare Bones SEO Notice:</strong> This plugin is optimized strictly for fresh environments. We detected another active SEO plugin. Please deactivate competing SEO plugins before activating Bare Bones SEO.',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
}

/**
 * Admin menu registration
 * 
 * Registers the main Bare Bones SEO menu and its subpages in the WordPress admin.
 * Both pages require 'manage_options' capability (admin only).
 * 
 * @since 1.0.0
 */
add_action('admin_menu', 'bare_bones_seo_register_menus');
function bare_bones_seo_register_menus() {
    add_menu_page(
        'Bare Bones SEO',
        '☠️ Bare Bones SEO',
        'manage_options',
        'bare-bones-seo',
        'bare_bones_seo_render_global_map_screen',
        'dashicons-shield',
        80
    );

    add_submenu_page(
        'bare-bones-seo',
        'Bulk Manager',
        'Bulk Page Manager',
        'manage_options',
        'bare-bones-seo-bulk',
        'bare_bones_seo_render_bulk_manager_screen'
    );
}

/**
 * Manual update check button
 */
add_action('admin_bar_menu', 'bare_bones_seo_add_update_check_link', 999);
function bare_bones_seo_add_update_check_link($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;

    $wp_admin_bar->add_node(array(
        'id'    => 'bb-check-updates',
        'title' => '☠️ Check Updates',
        'href'  => wp_nonce_url(admin_url('admin-ajax.php?action=bb_check_updates'), 'bb_nonce'),
        'parent' => 'top-secondary',
    ));
}

add_action('wp_ajax_bb_check_updates', function() {
    check_ajax_referer('bb_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die('No');
    wp_update_plugins();
    wp_redirect(admin_url('plugins.php?plugin_status=all'));
    exit;
});


/**
 * Enqueue admin styles and scripts
 * 
 * Loads CSS and JavaScript only on Bare Bones SEO admin pages.
 * This keeps the admin area fast for other pages and prevents
 * style/script conflicts in unused contexts.
 * 
 * @since 1.0.2
 */
add_action('admin_enqueue_scripts', 'bare_bones_seo_enqueue_assets');
function bare_bones_seo_enqueue_assets($hook_suffix) {
    // Only load on our plugin pages
    if (!in_array($hook_suffix, array('toplevel_page_bare-bones-seo', 'bare-bones-seo_page_bare-bones-seo-bulk'))) {
        return;
    }

    // Enqueue admin stylesheet
    wp_enqueue_style(
        'bare-bones-seo-admin',
        BARE_BONES_SEO_URL . 'assets/admin-style.css',
        array(),
        BARE_BONES_SEO_VERSION
    );

    // Enqueue admin scripts (jQuery is already available in WordPress admin)
    wp_enqueue_script(
        'bare-bones-seo-admin',
        BARE_BONES_SEO_URL . 'assets/admin-script.js',
        array('jquery'),
        BARE_BONES_SEO_VERSION,
        true // Load in footer
    );
}
/**
 * Add manual update check link to admin bar
 * 
 * Shows "☠️ Check Updates" button in top-right admin bar.
 * Admins can click to force immediate update check instead of
 * waiting for WordPress's automatic 2x daily schedule.
 * 
 * @since 1.0.3
 */
add_action('admin_bar_menu', 'bare_bones_seo_add_update_check', 999);
function bare_bones_seo_add_update_check($wp_admin_bar) {
    // Only show to admins
    if (!current_user_can('manage_options')) {
        return;
    }

    // Add link to check for updates
    $wp_admin_bar->add_node(array(
        'id'     => 'bb-check-updates',
        'title'  => '☠️ Check Updates',
        'href'   => wp_nonce_url(admin_url('admin-ajax.php?action=bb_check_updates'), 'bb_nonce'),
        'parent' => 'top-secondary',
    ));
}

/**
 * AJAX handler for manual update check
 * 
 * @since 1.0.3
 */
add_action('wp_ajax_bb_check_updates', 'bare_bones_seo_do_update_check');
function bare_bones_seo_do_update_check() {
    check_ajax_referer('bb_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Force WordPress to check for plugin updates right now
    wp_update_plugins();

    // Redirect to plugins page
    wp_redirect(admin_url('plugins.php'));
    exit;
}
