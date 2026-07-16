<?php
/**
 * Plugin Name: Bare Bones SEO
 * Plugin URI:  https://github.com/charltondigital/bare-bones-seo
 * Description: A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.
 * Version:     1.0.9
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
define('BARE_BONES_SEO_VERSION', '1.0.9');

// Load files
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-redirects.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-404-monitor.php';

/**
 * Activation hook — no hard blocks.
 *
 * @since 1.0.0
 */
register_activation_hook(__FILE__, 'bare_bones_seo_activation_check');
function bare_bones_seo_activation_check() {}

/**
 * Return inline skull SVG for use in admin headings.
 *
 * Single source of truth for the skull icon used throughout the UI.
 * Sized at 18px with vertical-align middle so it sits neatly inline
 * next to text. Fill is currentColor so it inherits text color.
 *
 * Usage: echo bare_bones_seo_skull_icon();
 *
 * @since 1.0.3
 * @param int $size Icon size in px (default 18)
 * @return string SVG HTML string
 */
function bare_bones_seo_skull_icon($size = 18) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="' . $size . '" height="' . $size . '" style="vertical-align:middle; margin-right:4px; position:relative; top:-1px;" aria-hidden="true">'
        . '<path fill="currentColor" d="M10 1C6.13 1 3 4.13 3 8c0 2.38 1.19 4.47 3 5.74V14.5c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26C15.81 12.47 17 10.38 17 8c0-3.87-3.13-7-7-7z"/>'
        . '<ellipse cx="7.5" cy="8" rx="1.8" ry="2" fill="white"/>'
        . '<ellipse cx="12.5" cy="8" rx="1.8" ry="2" fill="white"/>'
        . '<rect x="9.2" y="10" width="1.6" height="1.5" rx="0.4" fill="white"/>'
        . '<rect x="6" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"/>'
        . '<rect x="8.5" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"/>'
        . '<rect x="12" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"/>'
        . '</svg>';
}

/**
 * Admin menu registration.
 *
 * Uses skull SVG from assets/icon.svg as the menu icon.
 * WordPress colorizes the icon automatically on hover/active states.
 *
 * @since 1.0.0
 */
add_action('admin_menu', 'bare_bones_seo_register_menus');
function bare_bones_seo_register_menus() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="white" fill-rule="evenodd" d="M10 1C6.13 1 3 4.13 3 8c0 2.38 1.19 4.47 3 5.74V14.5c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V13.74C15.81 12.47 17 10.38 17 8c0-3.87-3.13-7-7-7z M5.7 6.2c0-.99.81-1.8 1.8-1.8s1.8.81 1.8 1.8-.81 1.8-1.8 1.8-1.8-.81-1.8-1.8z M10.7 6.2c0-.99.81-1.8 1.8-1.8s1.8.81 1.8 1.8-.81 1.8-1.8 1.8-1.8-.81-1.8-1.8z M9.2 10c0-.28.22-.5.5-.5h.6c.28 0 .5.22.5.5v.8c0 .28-.22.5-.5.5h-.6c-.28 0-.5-.22-.5-.5V10z M6 15.5h1.5v1.5H6z M8.5 15.5H10v1.5H8.5z M12 15.5h1.5v1.5H12z"/></svg>';

    // Register the main sidebar menu item
    add_menu_page(
        'Bare Bones SEO',
        'Bare Bones SEO',
        'manage_options',
        'bare-bones-seo',
        'bare_bones_seo_render_dashboard', // Updated callback name to reflect the dynamic layout
        'data:image/svg+xml;base64,' . base64_encode($svg),
        80
    );
}

/**
 * Render the unified plugin dashboard.
 */
function bare_bones_seo_render_dashboard() {
    // Determine the active tab (default to 'indexation')
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'indexation';
    ?>
    <div class="wrap">
        <!-- Render the shared header with the skull icon! -->
        <h1>
            <?php echo bare_bones_seo_skull_icon(22); ?>
            <?php _e('Bare Bones SEO', 'bare-bones-seo'); ?>
        </h1>

        <!-- Unified Tab Navigation -->
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=bare-bones-seo" class="nav-tab <?php echo $active_tab === 'indexation' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Indexation', 'bare-bones-seo'); ?>
            </a>
            <a href="?page=bare-bones-seo&tab=bulk" class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Bulk Manager', 'bare-bones-seo'); ?>
            </a>
            <a href="?page=bare-bones-seo&tab=redirects" class="nav-tab <?php echo $active_tab === 'redirects' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Redirects', 'bare-bones-seo'); ?>
            </a>
            <a href="?page=bare-bones-seo&tab=404-monitor" class="nav-tab <?php echo $active_tab === '404-monitor' ? 'nav-tab-active' : ''; ?>">
                <?php _e('404 Monitor', 'bare-bones-seo'); ?>
            </a>
        </h2>

        <!-- Dynamically Load Tab Content -->
        <div class="bbseo-tab-content">
            <?php
            switch ($active_tab) {
                case 'bulk':
                    // Calls your existing Bulk Manager renderer
                    bare_bones_seo_render_bulk_manager_screen();
                    break;
                case 'redirects':
                    // Calls your existing Redirects renderer
                    bare_bones_seo_render_redirects_screen();
                    break;
                case '404-monitor':
                    // Calls our brand new 404 renderer
                    bare_bones_seo_render_404_monitor_screen();
                    break;
                case 'indexation':
                default:
                    // Calls your existing Indexation/Global Map renderer
                    bare_bones_seo_render_global_map_screen();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin styles and scripts on all admin pages.
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
