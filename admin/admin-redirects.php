<?php
/**
 * Redirects Tab — Bare Bones SEO
 *
 * Integrates with the official Redirection plugin (redirection/redirection.php).
 * Shows an install prompt if not present, activation prompt if inactive,
 * and a redirect overview if active.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if Redirection plugin is installed (not necessarily active).
 *
 * @since 1.0.5
 * @return bool
 */
function bare_bones_seo_redirection_is_installed() {
    return file_exists(WP_PLUGIN_DIR . '/redirection/redirection.php');
}

/**
 * Check if Redirection plugin is active.
 *
 * @since 1.0.5
 * @return bool
 */
function bare_bones_seo_redirection_is_active() {
    return is_plugin_active('redirection/redirection.php');
}

/**
 * Check if Redirection's 404 logging is enabled.
 *
 * Redirection stores its options in wp_options as 'redirection_options'.
 * The log_404 setting controls 404 logging.
 *
 * @since 1.0.5
 * @return bool
 */
function bare_bones_seo_redirection_404_logging_on() {
    $options = get_option('redirection_options', array());
    return !empty($options['log_404']);
}

/**
 * Get redirect overview data from Redirection's database table.
 *
 * Reads directly from wp_redirection_items — no hooks, no overhead.
 * Only runs when the Redirects tab is open.
 * action_data is stored as a plain URL string in newer versions of Redirection.
 *
 * @since 1.0.5
 * @return array|false
 */
function bare_bones_seo_get_redirection_data() {
    global $wpdb;

    $table = $wpdb->prefix . 'redirection_items';

    // Check table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        return false;
    }

    // Total active redirects
    $total = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table} WHERE status = 'enabled'"
    );

    // Never triggered count
    $never_triggered = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table} WHERE status = 'enabled' AND last_count = 0"
    );

    // Last 5 redirects sorted by most recently accessed
    $redirects = $wpdb->get_results(
        "SELECT url, action_data, last_count, last_access
         FROM {$table}
         WHERE status = 'enabled'
         ORDER BY last_access DESC, id DESC
         LIMIT 5"
    );

    // Most recent hit timestamp
    $last_hit = $wpdb->get_var(
        "SELECT last_access FROM {$table}
         WHERE status = 'enabled' AND last_count > 0
         ORDER BY last_access DESC
         LIMIT 1"
    );

    return array(
        'total'           => $total,
        'never_triggered' => $never_triggered,
        'redirects'       => $redirects,
        'last_hit'        => $last_hit,
    );
}

/**
 * Format a timestamp as a human-readable relative time.
 *
 * @since 1.0.5
 * @param string $timestamp MySQL datetime string
 * @return string
 */
function bare_bones_seo_relative_time($timestamp) {
    if (empty($timestamp) || $timestamp === '0000-00-00 00:00:00' || $timestamp === '0000-00-00') {
        return 'never';
    }

    $time = strtotime($timestamp);
    if (!$time || $time <= 0) {
        return 'never';
    }

    $diff = time() - $time;

    if ($diff < 0) {
        return 'never';
    }

    if ($diff < 3600) {
        $mins = max(1, round($diff / 60));
        return $mins . ' minute' . ($mins !== 1 ? 's' : '') . ' ago';
    }

    if ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' ago';
    }

    if ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' day' . ($days !== 1 ? 's' : '') . ' ago';
    }

    if ($diff < 2592000) {
        $weeks = round($diff / 604800);
        return $weeks . ' week' . ($weeks !== 1 ? 's' : '') . ' ago';
    }

    $months = round($diff / 2592000);

    // Cap at 24 months to avoid "688 months ago" from bad dates
    if ($months > 24) {
        return 'never';
    }

    return $months . ' month' . ($months !== 1 ? 's' : '') . ' ago';
}

/**
 * Render the Redirects tab screen.
 *
 * @since 1.0.5
 */
function bare_bones_seo_render_redirects_screen() {
    $is_installed = bare_bones_seo_redirection_is_installed();
    $is_active    = bare_bones_seo_redirection_is_active();
    $logging_on   = $is_active ? bare_bones_seo_redirection_404_logging_on() : false;
    $data         = $is_active ? bare_bones_seo_get_redirection_data() : false;

    // URLs
    $install_url  = wp_nonce_url(
        admin_url('update.php?action=install-plugin&plugin=redirection'),
        'install-plugin_redirection'
    );
    $activate_url = wp_nonce_url(
        admin_url('plugins.php?action=activate&plugin=redirection/redirection.php'),
        'activate-plugin_redirection/redirection.php'
    );
    $manage_url   = admin_url('tools.php?page=redirection.php');
    $logging_url  = admin_url('tools.php?page=redirection.php&sub=options');
    ?>
    <div class="wrap">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #ccc; padding-bottom:15px;">
            <h1 style="margin:0;">Redirects — <?php echo bare_bones_seo_skull_icon(18); ?>Bare Bones SEO</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display:inline-flex; align-items:center; gap:5px;">
                <span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px; margin-top:2px;"></span>
                Documentation
            </a>
        </div>

        <!-- Tabs -->
        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="?page=bare-bones-seo" class="nav-tab">Indexation</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab">Bulk Manager</a>
            <a href="?page=bare-bones-seo-redirects" class="nav-tab nav-tab-active">Redirects</a>
        </h2>

        <?php if (!$is_installed) : ?>
            <!-- STATE 1: Not installed -->
            <div style="max-width:600px;">
                <p style="font-size:14px; color:#444; margin-bottom:20px;">
                    We recommend the free <strong>Redirection</strong> plugin for managing 301 redirects when you move or delete pages. It's lightweight, well maintained, and trusted by 2+ million sites.
                </p>
                <p style="font-size:13px; color:#666; margin-bottom:24px;">
                    <strong>Tip:</strong> After installing, disable 404 logging in Redirection's settings for best performance.
                </p>
                <a href="<?php echo esc_url($install_url); ?>" class="button button-primary button-large">
                    Install Redirection
                </a>
            </div>

        <?php elseif (!$is_active) : ?>
            <!-- STATE 2: Installed but not active -->
            <div style="max-width:600px;">
                <p style="font-size:14px; color:#444; margin-bottom:20px;">
                    Redirection is installed but not active. Activate it to manage your 301 redirects.
                </p>
                <a href="<?php echo esc_url($activate_url); ?>" class="button button-primary button-large">
                    Activate Redirection
                </a>
            </div>

        <?php else : ?>
            <!-- STATE 3 / 4: Active -->

            <?php if ($logging_on) : ?>
                <!-- 404 Logging Warning -->
                <div style="background:#fff8e5; border-left:4px solid #f0ad4e; padding:12px 16px; border-radius:3px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:13px; color:#333;">
                        ⚠️ <strong>404 logging is enabled in Redirection.</strong> This can slow down your site by writing a database row on every 404 error.
                    </span>
                    <a href="<?php echo esc_url($logging_url); ?>" class="button button-secondary" style="margin-left:16px; white-space:nowrap;">
                        Disable it →
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($data) : ?>
                <!-- Overview Stats -->
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px; max-width:700px;">
                    <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:16px; text-align:center;">
                        <div style="font-size:28px; font-weight:600; color:#1d2327;"><?php echo esc_html($data['total']); ?></div>
                        <div style="font-size:12px; color:#666; margin-top:4px;">Active redirects</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:16px; text-align:center;">
                        <div style="font-size:28px; font-weight:600; color:<?php echo $data['never_triggered'] > 0 ? '#f0ad4e' : '#1d2327'; ?>;">
                            <?php echo esc_html($data['never_triggered']); ?>
                        </div>
                        <div style="font-size:12px; color:#666; margin-top:4px;">Never triggered</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:16px; text-align:center;">
                        <div style="font-size:13px; font-weight:600; color:#1d2327; margin-top:4px;">
                            <?php echo $data['last_hit'] ? esc_html(bare_bones_seo_relative_time($data['last_hit'])) : 'No hits yet'; ?>
                        </div>
                        <div style="font-size:12px; color:#666; margin-top:4px;">Most recent hit</div>
                    </div>
                </div>

                <?php if ($data['never_triggered'] > 0) : ?>
                    <p style="font-size:12px; color:#888; margin-bottom:16px;">
                        <?php echo esc_html($data['never_triggered']); ?> redirect<?php echo $data['never_triggered'] !== 1 ? 's have' : ' has'; ?> never been triggered and may no longer be needed.
                    </p>
                <?php endif; ?>

                <!-- Recent Redirects Table -->
                <?php if (!empty($data['redirects'])) : ?>
                    <table class="wp-list-table widefat fixed striped" style="max-width:900px; margin-bottom:20px;">
                        <thead>
                            <tr>
                                <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; width:35%;">From</th>
                                <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; width:35%;">To</th>
                                <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; width:15%; text-align:center;">Hits</th>
                                <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; width:15%;">Last Hit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['redirects'] as $redirect) :
                                // action_data is stored as a plain URL string in current Redirection versions
                                // Try JSON decode first for backwards compatibility with older versions
                                $to_url = '';
                                $raw    = $redirect->action_data;

                                if (!empty($raw)) {
                                    $decoded = json_decode($raw, true);
                                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['url'])) {
                                        $to_url = $decoded['url'];
                                    } else {
                                        // Plain URL string (current Redirection format)
                                        $to_url = $raw;
                                    }
                                }

                                $never = ($redirect->last_count == 0);
                                $from  = $redirect->url;
                            ?>
                                <tr>
                                    <td style="padding:10px 12px; font-family:monospace; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:0;">
                                        <span title="<?php echo esc_attr($from); ?>" style="cursor:help;">
                                            <?php echo esc_html($from); ?>
                                        </span>
                                    </td>
                                    <td style="padding:10px 12px; font-family:monospace; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:0; color:#0073aa;">
                                        <span title="<?php echo esc_attr($to_url); ?>" style="cursor:help;">
                                            <?php echo esc_html($to_url ?: '—'); ?>
                                        </span>
                                    </td>
                                    <td style="padding:10px 12px; text-align:center; font-size:13px; color:<?php echo $never ? '#bbb' : '#1d2327'; ?>;">
                                        <?php echo esc_html($redirect->last_count); ?>
                                    </td>
                                    <td style="padding:10px 12px; font-size:12px; color:<?php echo $never ? '#bbb' : '#555'; ?>;">
                                        <?php echo esc_html(bare_bones_seo_relative_time($redirect->last_access)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <a href="<?php echo esc_url($manage_url); ?>" class="button button-primary">
                    Manage Redirects in Redirection →
                </a>

            <?php else : ?>
                <p style="color:#666;">Could not load redirect data. Make sure Redirection has been set up.</p>
                <a href="<?php echo esc_url($manage_url); ?>" class="button button-primary">Open Redirection →</a>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <?php
}
