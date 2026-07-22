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
 * @since 1.0.11
 */
function bare_bones_seo_render_overview_screen() {
    // Get the current monitor status dynamically
    $monitor_active = has_action('template_redirect', 'bbseo_log_404_error') ? 'Active' : 'Disabled';

    // The custom redirect engine, not a hook that never existed.
    $redirect_tracking = has_action('template_redirect', 'bare_bones_seo_apply_redirects') ? 'Active' : 'Disabled';

    $version = defined('BARE_BONES_SEO_VERSION') ? BARE_BONES_SEO_VERSION : 'unknown';

    // Hardcoded per release — measuring at runtime cost a directory walk and an
    // options row for a number that only changes when the plugin does.
    $plugin_size = defined('BARE_BONES_SEO_SIZE') ? BARE_BONES_SEO_SIZE : '';

    // Split size into numeric and unit segments dynamically for the styling layout
    $size_number = preg_replace('/[^0-9.]/', '', $plugin_size);
    $size_unit   = preg_replace('/[0-9.\s]/', '', $plugin_size);

    // Fallback security checks
    if (empty($size_number)) { $size_number = '100'; }
    if (empty($size_unit))   { $size_unit   = 'KB'; }
    ?>
    <div class="wrap" style="max-width: 1200px; margin-top: 20px;">
        
        <!-- High-Impact Bare Bones Welcome Panel -->
        <div class="bbseo-welcome-card" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-start; gap: 40px; padding: 40px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ccd0d4; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden;">
            
            <!-- Left Column: Scaled Up Custom SVG -->
            <div style="flex: 0 0 auto; display: flex; align-items: center; justify-content: center; background: #f6f7f8; padding: 30px; border-radius: 6px; border: 1px solid #e2e4e7; color: #1d2327;">
                <svg width="169" height="204" viewBox="0 0 169 204" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M55 204H33V180H55V204ZM94 204H72V180H94V204ZM134 204H112V180H134V204ZM84.5 0C131.168 0 169 38.9512 169 87C169 116.014 155.205 141.709 134 157.516V174H32V155.173C12.5036 139.236 0 114.622 0 87C0 38.9512 37.8319 0 84.5 0ZM84.5 117C73 117 72.5 137.5 73.5 141C74.5001 144.5 94.9999 144.5 95 141C95 137.5 96 117 84.5 117ZM46.5 62C34.0736 62 24 72.0736 24 84.5C24 96.9264 34.0736 107 46.5 107C58.9264 107 69 96.9264 69 84.5C69 72.0736 58.9264 62 46.5 62ZM120.5 62C108.074 62 98 72.0736 98 84.5C98 96.9264 108.074 107 120.5 107C132.926 107 143 96.9264 143 84.5C143 72.0736 132.926 62 120.5 62Z" fill="#383737"/>
</svg>
            </div>

            <!-- Right Column: Massive Size Callout & Philosophy Statement -->
            <div style="flex: 1; min-width: 300px;">
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
                    <h3 style="font-size: 14px; margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; text-transform: uppercase; letter-spacing: 0.05em; color: #1d2327;">Tools Overview</h3>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <li style="margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=indexation')); ?>" style="display: block; font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; text-decoration: none;">Indexation <span style="color: #a7aaad; font-weight: 400;">&rsaquo;</span></a>
                            A bird's-eye view of your site's search footprint. Set index and sitemap rules for every post type, taxonomy, and archive from one board.
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=bulk')); ?>" style="display: block; font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; text-decoration: none;">Page Meta <span style="color: #a7aaad; font-weight: 400;">&rsaquo;</span></a>
                            Titles, descriptions, schema, and indexing for individual posts and pages &mdash; editable one at a time in the editor, or in bulk from this screen.
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=redirects')); ?>" style="display: block; font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; text-decoration: none;">301 Redirects <span style="color: #a7aaad; font-weight: 400;">&rsaquo;</span></a>
                            Forward old and dead paths to live pages, with hit counts kept in a rolling 90-day window.
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=404-monitor')); ?>" style="display: block; font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; text-decoration: none;">404 Monitor <span style="color: #a7aaad; font-weight: 400;">&rsaquo;</span></a>
                            A passive listener for broken links, filtering out bot probes and vulnerability scans so only real visitor misses are logged.
                        </li>
                        <li style="border-top: 1px solid #f0f0f1; padding-top: 15px; margin-bottom: 0; font-size: 13px; line-height: 1.5;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=tracking')); ?>" style="display: block; font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; text-decoration: none;">Tracking <span style="color: #a7aaad; font-weight: 400;">&rsaquo;</span></a>
                            Analytics, verification, and pixel snippets for the whole site or a single page, without editing your theme.
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
                        <span style="font-weight: 600; color: #50575e;">301 Redirects</span>
                        <span style="color: #1d2327;"><?php echo esc_html($redirect_tracking); ?></span>
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
