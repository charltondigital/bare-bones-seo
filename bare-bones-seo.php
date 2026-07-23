<?php
/**
 * Plugin Name: Bare Bones SEO
 * Author: Charlton Digital
 * Version: 0.1.2
 */

if (!defined('ABSPATH')) { exit; }

// Constants
define('BARE_BONES_SEO_META_TITLE',  '_bare_bones_seo_title');
define('BARE_BONES_SEO_META_DESC',   '_bare_bones_seo_desc');
define('BARE_BONES_SEO_META_SCHEMA', '_bare_bones_seo_schema');
define('BARE_BONES_SEO_META_INDEX',  '_bare_bones_seo_should_index');
define('BARE_BONES_SEO_META_TRACKING', '_bare_bones_seo_page_scripts');
define('BARE_BONES_SEO_OPTION_GLOBAL_MAP', 'bare_bones_seo_global_map');
define('BARE_BONES_SEO_OPTION_TRACKING', 'bare_bones_seo_tracking_scripts');
define('BARE_BONES_SEO_NONCE_PAGE', 'bare_bones_seo_save_nonce');
define('BARE_BONES_SEO_NONCE_GLOBAL_MAP', 'bb_global_map_nonce');
define('BARE_BONES_SEO_NONCE_BULK_AJAX', 'bb_bulk_manager_nonce');
define('BARE_BONES_SEO_AJAX_ACTION', 'bb_seo_bulk_save');
define('BARE_BONES_SEO_AJAX_TRACKING', 'bb_seo_load_tracking');
define('BARE_BONES_SEO_PATH', plugin_dir_path(__FILE__));
define('BARE_BONES_SEO_URL',  plugin_dir_url(__FILE__));
define('BARE_BONES_SEO_VERSION', '0.1.2');
// Measured per release with the Plugin Size Meter tool. Update alongside VERSION.
define('BARE_BONES_SEO_SIZE', '127 KB');
define('BARE_BONES_SEO_DB_VERSION', '2');
define('BARE_BONES_SEO_DB_VERSION_OPTION', 'bare_bones_seo_db_version');

// Load Core
require_once BARE_BONES_SEO_PATH . 'includes/helpers.php';
require_once BARE_BONES_SEO_PATH . 'includes/indexation-resolver.php';
require_once BARE_BONES_SEO_PATH . 'includes/noindex-control.php';
require_once BARE_BONES_SEO_PATH . 'includes/sitemap-control.php';
require_once BARE_BONES_SEO_PATH . 'includes/page-meta-output.php';
require_once BARE_BONES_SEO_PATH . 'includes/redirect-engine.php';

// Load Admin Logic
// These two register hooks that can fire outside a plugin screen (meta box save,
// and the tracking table/sanitizer they share), so they always load.
require_once BARE_BONES_SEO_PATH . 'admin/admin-tracking.php';
require_once BARE_BONES_SEO_PATH . 'admin/admin-page-settings.php';

// Everything below is admin-screen rendering only — no reason to parse it on
// front-end requests.
if (is_admin()) {
    require_once BARE_BONES_SEO_PATH . 'includes/health-notice.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-overview.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-global-map.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-bulk-manager.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-404-monitor.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-redirects.php';
    require_once BARE_BONES_SEO_PATH . 'admin/admin-other-tools.php';
}

register_activation_hook(__FILE__, 'bare_bones_seo_install');

// Covers installs that skip activation (GitHub updater, manual upload) and any
// site where the table was dropped or never created.
add_action('admin_init', function() {
    if (get_option(BARE_BONES_SEO_DB_VERSION_OPTION) !== BARE_BONES_SEO_DB_VERSION) {
        bare_bones_seo_install();
    }
});

// Menu Registration
add_action('admin_menu', function() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 204"><path fill="white" d="M55 204H33V180H55V204ZM94 204H72V180H94V204ZM134 204H112V180H134V204ZM84.5 0C131.168 0 169 38.9512 169 87C169 116.014 155.205 141.709 134 157.516V174H32V155.173C12.5036 139.236 0 114.622 0 87C0 38.9512 37.8319 0 84.5 0ZM84.5 117C73 117 72.5 137.5 73.5 141C74.5001 144.5 94.9999 144.5 95 141C95 137.5 96 117 84.5 117ZM46.5 62C34.0736 62 24 72.0736 24 84.5C24 96.9264 34.0736 107 46.5 107C58.9264 107 69 96.9264 69 84.5C69 72.0736 58.9264 62 46.5 62ZM120.5 62C108.074 62 98 72.0736 98 84.5C98 96.9264 108.074 107 120.5 107C132.926 107 143 96.9264 143 84.5C143 72.0736 132.926 62 120.5 62Z"/></svg>';
    add_menu_page('Bare Bones SEO', 'Bare Bones SEO', 'manage_options', 'bare-bones-seo', 'bare_bones_seo_render_dashboard', 'data:image/svg+xml;base64,' . base64_encode($svg), 24.6);
    add_submenu_page('bare-bones-seo', 'Overview', 'Overview', 'manage_options', 'bare-bones-seo', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Indexation', 'Indexation', 'manage_options', 'bare-bones-seo&tab=indexation', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Page Meta', 'Page Meta', 'manage_options', 'bare-bones-seo&tab=bulk', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', '301 Redirects', '301 Redirects', 'manage_options', 'bare-bones-seo&tab=redirects', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', '404 Monitor', '404 Monitor', 'manage_options', 'bare-bones-seo&tab=404-monitor', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Tracking', 'Tracking', 'manage_options', 'bare-bones-seo&tab=tracking', 'bare_bones_seo_render_dashboard');
    add_submenu_page('bare-bones-seo', 'Other Tools', 'Other Tools', 'manage_options', 'bare-bones-seo&tab=other-tools', 'bare_bones_seo_render_dashboard');
});

function bare_bones_seo_skull_icon($size = 18, $color = 'currentColor') {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 169 204" width="' . intval($size) . '" height="' . intval($size) . '" style="vertical-align:middle; margin-right:4px;" aria-hidden="true"><path fill="' . esc_attr($color) . '" d="M55 204H33V180H55V204ZM94 204H72V180H94V204ZM134 204H112V180H134V204ZM84.5 0C131.168 0 169 38.9512 169 87C169 116.014 155.205 141.709 134 157.516V174H32V155.173C12.5036 139.236 0 114.622 0 87C0 38.9512 37.8319 0 84.5 0ZM84.5 117C73 117 72.5 137.5 73.5 141C74.5001 144.5 94.9999 144.5 95 141C95 137.5 96 117 84.5 117ZM46.5 62C34.0736 62 24 72.0736 24 84.5C24 96.9264 34.0736 107 46.5 107C58.9264 107 69 96.9264 69 84.5C69 72.0736 58.9264 62 46.5 62ZM120.5 62C108.074 62 98 72.0736 98 84.5C98 96.9264 108.074 107 120.5 107C132.926 107 143 96.9264 143 84.5C143 72.0736 132.926 62 120.5 62Z"/></svg>';
}

function bare_bones_seo_render_dashboard() {
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => array('l' => 'Overview', 'c' => 'bare_bones_seo_render_overview_screen'),
        'indexation' => array('l' => 'Indexation', 'c' => 'bare_bones_seo_render_global_map_screen'),
        'bulk' => array('l' => 'Page Meta', 'c' => 'bare_bones_seo_render_bulk_manager_screen'),
        'redirects' => array('l' => '301 Redirects', 'c' => 'render_bare_bones_redirects_tab'),
        '404-monitor' => array('l' => '404 Monitor', 'c' => 'bare_bones_seo_render_404_monitor_screen'),
        'tracking' => array('l' => 'Tracking', 'c' => 'bare_bones_seo_render_tracking_screen'),
        'other-tools' => array('l' => 'Other Tools', 'c' => 'bare_bones_seo_render_other_tools_screen'),
    );
    ?>
    <div class="wrap" style="max-width: 1200px;">
        <?php // Marker hoists admin notices ABOVE the title instead of below it. ?>
        <div class="wp-header-end"></div>
        <?php $bb_issues = bare_bones_seo_get_critical_issues(); ?>
        <h1 style="font-size:46px; font-weight:700; line-height:1.2; display:flex; align-items:center; gap:8px; margin:0 0 14px;"><?php echo bare_bones_seo_skull_icon(48, $bb_issues ? '#d63638' : 'currentColor'); ?> Bare Bones SEO</h1>
        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <?php foreach ($tabs as $id => $t) : ?>
                <a href="?page=bare-bones-seo<?php echo ($id == 'overview' ? '' : '&tab='.$id); ?>" class="nav-tab <?php echo ($active_tab == $id ? 'nav-tab-active' : ''); ?>"><?php echo $t['l']; ?></a>
            <?php endforeach; ?>
        </h2>
        <div id="bbseo-tabs-container">
            <?php foreach ($tabs as $id => $t) : ?>
                <div id="bbseo-tab-<?php echo $id; ?>" class="bbseo-tab-content" style="display:<?php echo ($active_tab == $id ? 'block' : 'none'); ?>;">
                    <?php if (function_exists($t['c'])) { call_user_func($t['c']); } ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function($hook) {
    if(strpos($hook, 'bare-bones-seo') !== false || in_array($hook, array('post.php', 'post-new.php'))) {
        wp_enqueue_style('bbs-css', plugins_url('assets/admin-style.css', __FILE__), array(), BARE_BONES_SEO_VERSION);
        wp_enqueue_script('bbs-js', plugins_url('assets/admin-script.js', __FILE__), array('jquery'), BARE_BONES_SEO_VERSION, true);
        wp_localize_script('bbs-js', 'bbSeoData', array(
            'ajaxAction'     => BARE_BONES_SEO_AJAX_ACTION,
            'trackingAction' => BARE_BONES_SEO_AJAX_TRACKING,
            'nonce'          => wp_create_nonce(BARE_BONES_SEO_NONCE_BULK_AJAX),
        ));
    }
});

if (is_admin() || wp_doing_cron()) {
    require_once BARE_BONES_SEO_PATH . 'includes/github-updater.php';
    new BBSEO_GitHub_Updater(__FILE__, 'charltondigital/bare-bones-seo');
}
