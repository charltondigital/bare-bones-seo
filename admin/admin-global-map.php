<?php
/**
 * Global Site Indexing Map
 * 
 * Provides a dashboard for blanket noindex rules by post type.
 * Allows administrators to disable indexing for entire sections
 * (e.g., "don't index any pages") without editing individual posts.
 * 
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Global Indexing Map screen
 * 
 * Displays a table showing all public post types with radio buttons
 * to control whether each post type should be indexed.
 * 
 * Also handles form submission: when the user saves changes,
 * validates nonce and updates the global indexing rules option.
 * 
 * @since 1.0.0
 * @return void
 */
function bare_bones_seo_render_global_map_screen() {
    // Handle form submission
    if (isset($_POST['bb_save_global_map']) && check_admin_referer(BARE_BONES_SEO_NONCE_GLOBAL_MAP)) {
        // Extract submitted post type settings
        $submitted_settings = isset($_POST['section_index']) ? $_POST['section_index'] : array();

        // Sanitize each setting to prevent injection
        $clean_settings = array();
        foreach ($submitted_settings as $key => $val) {
            $clean_settings[sanitize_key($key)] = sanitize_text_field($val);
        }

        // Save to wp_options
        update_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, $clean_settings);
        echo '<div class="updated"><p>Global Section Indexing Rules Saved Successfully.</p></div>';
    }

    // Fetch current settings and post types
    $current_options = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());
    $post_types = get_post_types(array('public' => true), 'objects');
    ?>
    <div class="wrap">
        <!-- Header with title and docs link -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 15px;">
            <h1 style="margin: 0;">☠️ Bare Bones SEO — Global Site Indexing Map</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="dashicons dashicons-external" style="font-size: 16px; width:16px; height:16px; margin-top:2px;"></span> 
                Plugin Documentation
            </a>
        </div>

        <!-- Tab navigation -->
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=bare-bones-seo" class="nav-tab nav-tab-active">Global Indexing Map</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab">Bulk Page Manager</a>
        </h2>

        <!-- Settings form -->
        <form method="post" action="">
            <?php wp_nonce_field(BARE_BONES_SEO_NONCE_GLOBAL_MAP); ?>
            
            <table class="wp-list-table widefat fixed striped" style="max-width: 1000px; margin-top: 20px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="font-size: 14px; padding: 15px; vertical-align: middle; width: 40%;">PAGE SECTION / POST TYPE</th>
                        <th colspan="3" style="text-align: center; font-size: 13px; background: #f6f7f7; border-bottom: 1px solid #dcdcde;">DO YOU WANT SEARCH ENGINES TO INDEX THIS CATEGORY OF PAGES?</th>
                    </tr>
                    <tr>
                        <th style="text-align: center; padding: 10px; width: 20%;">YES (Index & Include)</th>
                        <th style="text-align: center; padding: 10px; width: 20%;">NO (Noindex & Drop)</th>
                        <th style="text-align: center; padding: 10px; width: 20%;">IT'S COMPLICATED...</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($post_types as $type) : 
                        // Get current status, default to 'yes' (index everything)
                        $status = isset($current_options[$type->name]) ? $current_options[$type->name] : 'yes';
                    ?>
                        <tr>
                            <td style="padding: 15px; font-weight: 600;">
                                <?php echo esc_html($type->label); ?>
                                <span style="display: block; font-weight: normal; font-size: 11px; color: #666; margin-top: 4px;">
                                    [🔗 Raw Endpoint target: <code>wp-sitemap-posts-<?php echo esc_html($type->name); ?>-1.xml</code>]
                                </span>
                            </td>
                            <!-- YES: Index this post type -->
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="radio" 
                                       name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                       value="yes" 
                                       <?php checked($status, 'yes'); ?> 
                                       class="bb-radio-toggle"
                                       data-post-type="<?php echo esc_attr($type->name); ?>">
                            </td>
                            <!-- NO: Don't index this post type -->
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="radio" 
                                       name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                       value="no" 
                                       <?php checked($status, 'no'); ?> 
                                       class="bb-radio-toggle"
                                       data-post-type="<?php echo esc_attr($type->name); ?>">
                            </td>
                            <!-- ADVANCED: Custom rules per post -->
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="radio" 
                                       name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                       value="advanced" 
                                       <?php checked($status, 'advanced'); ?> 
                                       class="bb-radio-toggle"
                                       data-post-type="<?php echo esc_attr($type->name); ?>">
                            </td>
                        </tr>
                        <!-- Advanced row: Shown only when 'advanced' is selected -->
                        <tr class="bb-advanced-row" 
                            data-post-type="<?php echo esc_attr($type->name); ?>" 
                            style="<?php echo ($status === 'advanced') ? '' : 'display:none;'; ?> background: #fffcf0;">
                            <td colspan="4" style="padding: 12px 25px; border-left: 4px solid #f0b849;">
                                <div style="display: flex; gap: 40px; font-size: 12px;">
                                    <label>
                                        <input type="checkbox" 
                                               name="advanced_noindex[<?php echo esc_attr($type->name); ?>]" 
                                               value="1" 
                                               checked 
                                               disabled> 
                                        Output hard <code>&lt;noindex&gt;</code> rules on layout headers
                                    </label>
                                    <label>
                                        <input type="checkbox" 
                                               name="advanced_sitemap[<?php echo esc_attr($type->name); ?>]" 
                                               value="1"> 
                                        Force retain structural URL maps within the primary sitemap index loop
                                    </label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Submit button -->
            <p class="submit">
                <input type="submit" 
                       name="bb_save_global_map" 
                       class="button button-primary button-large" 
                       value="Save Site Indexing Rules">
            </p>
        </form>
    </div>

    <?php
    // JavaScript is now in assets/admin-script.js
    // See that file for the radio button toggle logic
}
