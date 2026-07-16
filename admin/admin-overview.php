<?php
/**
 * SEO Page Settings: Overview — Bare Bones SEO
 *
 * Welcomes users, explains the zero-bloat philosophy, and provides diagnostics.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Overview screen.
 *
 * @since 1.0.10
 */
function bare_bones_seo_render_overview_screen() {
    // Get the current monitor status dynamically
    $monitor_active = has_action('template_redirect', 'bbseo_log_404_error') ? 'Active' : 'Disabled';
    
    // Check footprint estimate (e.g., measuring database tables if needed, or keeping it static/light)
    $version = '1.0.10'; // Update dynamically if you have a defined constant
    if (defined('BARE_BONES_SEO_VERSION')) {
        $version = BARE_BONES_SEO_VERSION;
    }
    ?>
    <div class="wrap" style="max-width: 1200px; margin-top: 20px;">
        <!-- Hero Welcome Panel -->
        <div class="welcome-panel" style="padding: 30px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #ccd0d4; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="welcome-panel-content">
                <h2 style="font-size: 21px; font-weight: 400; margin-top: 0; margin-bottom: 10px;">Welcome to Bare Bones SEO</h2>
                <p style="font-size: 15px; color: #50575e; max-width: 800px; line-height: 1.5; margin-bottom: 0;">
                    A performance-first SEO engine built for speed. While heavy plugins slow your website down with bloated databases, endless notification banners, and hidden background tracking scripts, Bare Bones SEO gives you surgical, direct control over your search footprint. <strong>This plugin is 100% free with absolutely no upsells, locked features, or premium versions.</strong>
                </p>
            </div>
        </div>

        <!-- Two Column Grid -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            
            <!-- Left Column: Guide/How-To -->
            <div>
                <div class="card" style="max-width: 100%; margin-top: 0; margin-bottom: 20px; padding: 20px;">
                    <h3 style="font-size: 14px; margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; text-transform: uppercase; letter-spacing: 0.05em; color: #1d2327;">Core Toolset Overview</h3>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <li style="margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <strong style="display: block; font-size: 14px; color: #1d2327; margin-bottom: 2px;">🗺️ Indexation (Global Map)</strong>
                            A bird's-eye view of your entire website's search footprint. Configure default, fallback index and sitemap rules across all custom post types from a single, centralized settings board.
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <strong style="display: block; font-size: 14px; color: #1d2327; margin-bottom: 2px;">✏️ Page-Level Control</strong>
                            Granular control over individual post and page metadata. Override global rules to set custom titles, descriptions, schema JSON, and indexation settings directly within the WordPress editor. <em>Includes a built-in <strong>Bulk Manager</strong> to quickly tweak these values across your entire site from a single, fast-loading screen.</em>
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 0; font-size: 13px; line-height: 1.5;">
                            <strong style="display: block; font-size: 14px; color: #1d2327; margin-bottom: 2px;">🚫 404 Monitor</strong>
                            A silent listener tracking broken links across your environment. It automatically filters out common background bot exploits and vulnerability scans, logging only high-value user metrics.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Status & Statistics -->
            <div>
                <div class="card" style="max-width: 100%; margin-top: 0; margin-bottom: 20px; padding: 20px;">
                    <h3 style="font-size: 14px; margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; text-transform: uppercase; letter-spacing: 0.05em; color: #1d2327;">Engine Diagnostics</h3>
                    
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px;">
                        <span style="font-weight: 600; color: #50575e;">Core Engine</span>
                        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; background: #ecfdf5; color: #047857; padding: 1px 8px; border-radius: 12px; border: 1px solid #a7f3d0;">Active</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px;">
                        <span style="font-weight: 600; color: #50575e;">Pricing Model</span>
                        <span style="color: #047857; font-weight: 600;">100% Free / No Upsells</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px;">
                        <span style="font-weight: 600; color: #50575e;">404 Monitor</span>
                        <span style="color: #1d2327;"><?php echo esc_html($monitor_active); ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px;">
                        <span style="font-weight: 600; color: #50575e;">Data Footprint</span>
                        <span style="color: #1d2327;">&lt; 100 KB</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: none; font-size: 13px;">
                        <span style="font-weight: 600; color: #50575e;">Version</span>
                        <span style="color: #1d2327;"><?php echo esc_html($version); ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php
}
