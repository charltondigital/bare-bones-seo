<?php
/**
 * Plugin Name: Bare Bones SEO
 * Plugin URI:   https://github.com/charltondigital/bare-bones-seo
 * Description: A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.
 * Version:     0.1.0
 * Author:       Charlton Digital
 * License:      GPLv2 or later
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
define('BARE_BONES_SEO_META_TRACKING', '_bare_bones_seo_page_scripts');

// Option keys
define('BARE_BONES_SEO_OPTION_GLOBAL_MAP', 'bare_bones_seo_global_map');
define('BARE_BONES_SEO_OPTION_TRACKING', 'bare_bones_seo_tracking_scripts');

// Nonce actions
define('BARE_BONES_SEO_NONCE_PAGE',       'bare_bones_seo_save_nonce');
define('BARE_BONES_SEO_NONCE_GLOBAL_MAP', 'bb_global_map_nonce');
define('BARE_BONES_SEO_NONCE_BULK_AJAX',  'bb_bulk_manager_nonce');

// AJAX action
define('BARE_BONES_SEO_AJAX_ACTION', 'bb_seo_bulk_save');

// Plugin paths
define('BARE_BONES_SEO_PATH',    plugin_dir_path(__FILE__));
define('BARE_BONES_SEO_URL',     plugin_dir_url(__FILE__));
define('BARE_BONES_SEO_VERSION', '0.1.0');

/**
 * 1. Load Core Includes
 */
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';
require_once BARE_BONES_SEO_PATH . 'includes/indexation-resolver.php';
require_once BARE_BONES_SEO_PATH . 'includes/noindex-control.php';
require_once BARE_BONES_SEO_PATH . 'includes/sitemap-control.php';
require_once BARE_BONES_SEO_PATH . 'includes/page-meta-output.php';
require_once BARE_BONES_SEO_PATH . 'includes/redirect-engine.php';

/**
 * 2. Load Admin Files
 * We load Tracking FIRST so its table rendering functions are available 
 * to the Page Settings and Bulk Manager screens.
 */
require_once BARE_BONES_SEO_PATH . 'admin/admin-tracking.php'; 
require_once BARE_BONES_SEO_PATH . 'admin/admin-overview.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-404-monitor.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-redirects.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-other-tools.php';

/**
 * Calculate and save the plugin size to the database.
 */
function bbseo_update_stored_plugin_size() {
    $plugin_dir = plugin_dir_path(__FILE__);
    $total_size = 0;
    if (is_dir($plugin_dir)) {
        $directory = new RecursiveDirectoryIterator($plugin_dir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($directory);
        foreach ($files as $file) {
            $total_size += $file->getSize();
        }
    }
    $formatted_size = size_format($total_size, 1);
    update_option('bbseo_plugin_disk_size', $formatted_size);
}

/**
 * Combined master activation setup routine.
 */
function bare_bones_seo_master_activation() {
    bbseo_create_404_table();
    delete_option('bbseo_plugin_disk_size');
    bbseo_update_stored_plugin_size();
}
register_activation_hook(__FILE__, 'bare_bones_seo_master_activation');

/**
 * Update calculation handler for updates and zip installations.
 */
function bbseo_update_size_on_upgrade($upgrader_object, $options) {
    if (isset($options['action']) && $options['action'] === 'update' && $options['type'] === 'plugin') {
        if (isset($options['plugins']) && is_array($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if (strpos($plugin, 'bare-bones-seo.php') !== false) {
                    delete_option('bbseo_plugin_disk_size');
                    bbseo_update_stored_plugin_size();
                    break;
                }
            }
        }
    }
}
add_action('upgrader_process_complete', 'bbseo_update_size_on_upgrade', 10, 2);

/**
 * Return inline skull SVG for use in admin headings.
 */
function bare_bones_seo_skull_icon($size = 18) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 204" width="' . intval($size) . '" height="' . intval($size) . '" style="vertical-align:middle; margin-right:4px; position:relative; top:-1px;" aria-hidden="true">'
        . '<path fill="currentColor" d="M55 204H33V180H55V204ZM94 204H72V180H94V204ZM134 204H112V180H134V204ZM84.5 0C131.168 0 169 38.9512 169 87C169 116.014 155.205 141.709 134 157.516V174H32V155.173C12.5036 139.236 0 114.622 0 87C0 38.9512 37.8319 0 84.5 0ZM84.5 117C73 117 72.5 137.5 73.5 141C74.5001 144.5 94.9999 144.5 95 141C95 137.5 96 117 84.5 117ZM46.5 62C34.0736 62 24 72.0736 24 84.5C24 96.9264 34.0736 107 46.5 107C58.9264 107 69 96.9264 69 84.5C69 72.0736 58.9264 62 46.5 62ZM120.5 62C108.074 62 98 72.0736 98 84.5C98 96.9264 108.074 107 120.5 107C132.926 107 143 96.9264 143 84.5C143 72.0736 132.926 62 120.5 62Z"/>'
        . '</svg>';
}

/**
 * Admin menu registration.
 */
add_action('admin_menu', 'bare_bones_seo_register_menus');
function bare_bones_seo_register_menus() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 204"><path fill="white" d="M55 204H33V180H55V204ZM94 204H72V180H94V204ZM134 204H112V180H134V204ZM84.5 0C131.168 0 169 38.9512 169 87C169 116.014 155.205 141.709 134 157.516V174H32V155.173C12.5036 139.236 0 114.622 0 87C0 38.9512 37.8319 0 84.5 0ZM84.5 117C73 117 72.5 137.5 73.5 141C74.5001 144.5 94.9999 144.5 95 141C95 137.5 96 117 84.5 117ZM46.5 62C34.0736 62 24 72.0736 24 84.5C24 96.9264 34.0736 107 46.5 107C58.9264 107 69 96.9264 69 84.5C69 72.0736 58.9264 62 46.5 62ZM120.5 62C108.074 62 98 72.0736 98 84.5C98 96.9264 108.074 107 120.5 107C132.926 107 143 96.9264 143 84.5C143 72.0736 132.926 62 120.5 62Z"/></svg>';
    add_menu_page(
        'Bare Bones SEO',
        'Bare Bones SEO',
        'manage_options',
        'bare-bones-seo',
        'bare_bones_seo_render_dashboard',
        'data:image/svg+xml;base64,' . base64_encode($svg),
        80
    );

    add_submenu_page('bare-bones-seo', 'Overview', 'Overview', 'manage_options', 'bare-bones-seo', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Indexation', 'Indexation', 'manage_options', 'bare-bones-seo&tab=indexation', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Page Meta', 'Page Meta', 'manage_options', 'bare-bones-seo&tab=bulk', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', '301 Redirects', '301 Redirects', 'manage_options', 'bare-bones-seo&tab=redirects', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', '404 Monitor', '404 Monitor', 'manage_options', 'bare-bones-seo&tab=404-monitor', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Tracking', 'Tracking', 'manage_options', 'bare-bones-seo&tab=tracking', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Other Tools', 'Other Tools', 'manage_options', 'bare-bones-seo&tab=other-tools', 'bare_bones_seo_render_dashboard');
}

/**
 * Render the unified plugin dashboard.
 */
function bare_bones_seo_render_dashboard() {
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
    ?>
    <div class="wrap" style="max-width: 1200px; margin-top: 0; padding-top: 0;">
        <div class="wp-header-end" style="height: 0; margin: 0; padding: 0; display: none;"></div>

        <div class="bbseo-header-wrapper" style="margin-top: 15px; margin-bottom: 15px;">
            <h1 style="display: flex; align-items: center; gap: 8px; margin: 0; padding: 0; font-size: 23px; font-weight: 400; line-height: 1.2;">
                <?php echo bare_bones_seo_skull_icon(24); ?>
                <?php _e('Bare Bones SEO', 'bare-bones-seo'); ?>
            </h1>
        </div>

        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px; margin-top: 10px;">
            <a href="?page=bare-bones-seo" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>"><?php _e('Overview', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=indexation" class="nav-tab <?php echo $active_tab === 'indexation' ? 'nav-tab-active' : ''; ?>"><?php _e('Indexation', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=bulk" class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>"><?php _e('Page Meta', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=redirects" class="nav-tab <?php echo $active_tab === 'redirects' ? 'nav-tab-active' : ''; ?>"><?php _e('301 Redirects', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=404-monitor" class="nav-tab <?php echo $active_tab === '404-monitor' ? 'nav-tab-active' : ''; ?>"><?php _e('404 Monitor', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=tracking" class="nav-tab <?php echo $active_tab === 'tracking' ? 'nav-tab-active' : ''; ?>"><?php _e('Tracking', 'bare-bones-seo'); ?></a>
            <a href="?page=bare-bones-seo&tab=other-tools" class="nav-tab <?php echo $active_tab === 'other-tools' ? 'nav-tab-active' : ''; ?>"><?php _e('Other Tools', 'bare-bones-seo'); ?></a>
        </h2>

        <div class="bbseo-tab-content">
            <?php
            switch ($active_tab) {
                case 'indexation': bare_bones_seo_render_global_map_screen(); break;
                case 'bulk': bare_bones_seo_render_bulk_manager_screen(); break;
                case 'redirects': render_bare_bones_redirects_tab(); break;
                case '404-monitor': bare_bones_seo_render_404_monitor_screen(); break;
                case 'tracking': bare_bones_seo_render_tracking_screen(); break;
                case 'other-tools': bare_bones_seo_render_other_tools_screen(); break;
                case 'overview':
                default: bare_bones_seo_render_overview_screen(); break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin scripts and styles.
 */
add_action( 'admin_enqueue_scripts', 'bare_bones_seo_enqueue_admin_assets' );
function bare_bones_seo_enqueue_admin_assets( $hook ) {
    global $post_type;
    $is_post_editor = false;
    if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        $public_types = get_post_types( array( 'public' => true ) );
        if ( in_array( $post_type, $public_types, true ) ) {
            $is_post_editor = true;
        }
    }
    $is_seo_dashboard = ( strpos( $hook, 'bare-bones-seo' ) !== false );
    if ( $is_post_editor || $is_seo_dashboard ) {
        wp_enqueue_style('bare-bones-seo-admin-css', plugins_url( 'assets/admin-style.css', __FILE__ ), array(), BARE_BONES_SEO_VERSION);
        wp_enqueue_script('bare-bones-seo-admin-js', plugins_url( 'assets/admin-script.js', __FILE__ ), array( 'jquery' ), BARE_BONES_SEO_VERSION, true);
    }
}

/**
 * Create custom 404 logging table.
 */
function bbseo_create_404_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bbseo_404_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        url varchar(2048) NOT NULL,
        referer varchar(2048) NOT NULL,
        hits int(11) DEFAULT 1 NOT NULL,
        last_accessed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY url (url(191)),
        KEY last_accessed (last_accessed)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    if (!wp_next_scheduled('bbseo_daily_cleanup_404_logs')) {
        wp_schedule_event(time(), 'daily', 'bbseo_daily_cleanup_404_logs');
    }
}

/**
 * Clear daily scheduled events on deactivation.
 */
function bbseo_deactivation_cleanup() {
    wp_clear_scheduled_hook('bbseo_daily_cleanup_404_logs');
}
register_deactivation_hook(__FILE__, 'bbseo_deactivation_cleanup');

/**
 * Automatically prune logs older than 90 days.
 */
add_action('bbseo_daily_cleanup_404_logs', 'bbseo_prune_old_404_logs');
function bbseo_prune_old_404_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bbseo_404_logs';
    $wpdb->query( "DELETE FROM $table_name WHERE last_accessed < DATE_SUB(NOW(), INTERVAL 90 DAY)" );
}

/**
 * Intercept WordPress template redirects on 404 to track hits over a rolling 90 days.
 */
add_action('template_redirect', 'bbseo_log_old_slug_redirect_90_days', 5);
function bbseo_log_old_slug_redirect_90_days() {
    if (is_404() && '' !== get_query_var('name')) {
        global $wpdb;
        $query_slug = get_query_var('name');
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_old_slug' AND meta_value = %s LIMIT 1",
            $query_slug
        ));
        if ($post_id) {
            $meta_key = '_wp_old_slug_hits_' . sanitize_key($query_slug);
            $hits = get_post_meta($post_id, $meta_key, true);
            if (!is_array($hits)) { $hits = array(); }
            $today = gmdate('Y-m-d');
            $hits[$today] = isset($hits[$today]) ? (int) $hits[$today] + 1 : 1;
            $cutoff = strtotime('-90 days');
            foreach ($hits as $date => $count) {
                if (strtotime($date) < $cutoff) { unset($hits[$date]); }
            }
            update_post_meta($post_id, $meta_key, $hits);
        }
    }
}

/**
 * Initialize Single-File Over-The-Air Update Engine.
 */
if ( is_admin() || wp_doing_cron() ) {
    require_once BARE_BONES_SEO_PATH . 'includes/github-updater.php';
    new BBSEO_GitHub_Updater(__FILE__, 'charltondigital/bare-bones-seo');
}
