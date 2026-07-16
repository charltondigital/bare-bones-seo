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
    
    // Check footprint estimate
    $version = '1.0.10';
    if (defined('BARE_BONES_SEO_VERSION')) {
        $version = BARE_BONES_SEO_VERSION;
    }

    // Fetch the cached plugin size computed at hook installation/update
    $plugin_size = get_option('bbseo_plugin_disk_size', '29 KB');

    // Split size into numeric and unit segments dynamically for the styling layout
    $size_number = preg_replace('/[^0-9.]/', '', $plugin_size);
    $size_unit   = preg_replace('/[0-9.\s]/', '', $plugin_size);

    // Fallback security checks
    if (empty($size_number)) { $size_number = '29'; }
    if (empty($size_unit))   { $size_unit   = 'KB'; }
    ?>
    <div class="wrap" style="max-width: 1200px; margin-top: 20px;">
        
        <!-- High-Impact Bare Bones Welcome Panel -->
        <div class="bbseo-welcome-card" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 40px; padding: 40px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ccd0d4; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden;">
            
            <!-- Left Column: Scaled Up Custom SVG -->
            <div style="flex: 0 0 auto; display: flex; align-items: center; justify-content: center; background: #f6f7f8; padding: 30px; border-radius: 6px; border: 1px solid #e2e4e7; color: #1d2327;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="120" height="120" aria-hidden="true" style="display: block;">
                    <path fill="currentColor" d="M10 1C6.13 1 3 4.13 3 8c0 2.38 1.19 4.47 3 5.74V14.5c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26C15.81 12.47 17 10.38 17 8c0-3.87-3.13-7-7-7z"></path>
                    <ellipse cx="7.5" cy="8" rx="1.8" ry="2" fill="white"></ellipse>
                    <ellipse cx="12.5" cy="8" rx="1.8" ry="2" fill="white"></ellipse>
                    <rect x="9.2" y="10" width="1.6" height="1.5" rx="0.4" fill="white"></rect>
                    <rect x="6" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"></rect>
                    <rect x="8.5" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"></rect>
                    <rect x="12" y="15.5" width="1.5" height="1.5" rx="0.3" fill="currentColor"></rect>
                </svg>
            </div>

            <!-- Right Column: Massive Size Callout & Philosphy Statement -->
            <div style="flex: 1; min-width: 300px; max-width: 650px;">
                <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px;">
                    <span style="font-size: 96px; font-weight: 900; line-height: 0.8; letter-spacing: -4px; color: #1d2327; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                        <?php echo esc_html($size_number); ?>
                    </span>
                    <span style="font-size: 32px; font-weight: 800; color: #50575e; letter-spacing: -1px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                        <?php echo esc_html($size_unit); ?>
                    </span>
                </div>
                
                <h2 style="font-size: 24px; font-weight: 700; margin: 0 0 12px 0; color: #1d2327; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Surgical, Zero-Bloat SEO</h2>
                
                <p style="font-size: 15px; color: #50575e; line-height: 1.6; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    While mainstream SEO tools bog your site down with thousands of lines of bloated code, background tracking scripts, and heavy database clutter, Bare Bones SEO delivers ultimate speed. This entire plugin sits at an incredibly light footprint—meaning zero performance impact on your web server and lightning-fast page transitions. 
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
                        <span style="color: #1d2327;"><?php echo esc_html($plugin_size); ?></span>
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
