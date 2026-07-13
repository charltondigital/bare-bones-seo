<?php
/**
 * Plugin Name: Bare Bones SEO
 * Plugin URI:  https://github.com/charltondigital/bare-bones-seo
 * Description: A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.
 * Version:     1.0.2
 * Author:      Charlton Digital
 * License:     GPLv2 or later
 * Text Domain: bare-bones-seo
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BARE_BONES_SEO_PATH', plugin_dir_path(__FILE__));
define('BARE_BONES_SEO_URL', plugin_dir_url(__FILE__));

// Include structural architectures
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';

// Fresh Install Check
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

// Register Settings Menus
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
