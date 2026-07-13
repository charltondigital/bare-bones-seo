<?php
/**
 * Global Site Indexing Map with Live Sitemap Preview
 */

if (!defined('ABSPATH')) {
    exit;
}

function bare_bones_seo_render_global_map_screen() {
    if (isset($_POST['bb_save_global_map']) && check_admin_referer(BARE_BONES_SEO_NONCE_GLOBAL_MAP)) {
        $submitted_settings = isset($_POST['section_index']) ? $_POST['section_index'] : array();

        $clean_settings = array();
        foreach ($submitted_settings as $key => $val) {
            $clean_settings[sanitize_key($key)] = sanitize_text_field($val);
        }

        update_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, $clean_settings);
        echo '<div class="updated"><p>Your settings have been saved.</p></div>';
    }

    $current_options = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());
    $post_types = get_post_types(array('public' => true), 'objects');
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    
    $critical_sections = array('page', 'post');
    $defaults = array(
        'page'      => 'yes',
        'post'      => 'yes',
        'category'  => 'yes',
        'post_tag'  => 'no',
    );
    ?>
    <div class="wrap">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 15px;">
            <h1 style="margin: 0;">☠️ Bare Bones SEO — Global Indexing Map</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="dashicons dashicons-external" style="font-size: 16px; width:16px; height:16px; margin-top:2px;"></span> 
                Documentation
            </a>
        </div>

        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=bare-bones-seo" class="nav-tab nav-tab-active">Global Indexing Map</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab">Bulk Page Manager</a>
        </h2>

        <!-- TWO-COLUMN LAYOUT: Controls Left, Preview Right -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">

            <!-- LEFT COLUMN: Radio Button Controls -->
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field(BARE_BONES_SEO_NONCE_GLOBAL_MAP); ?>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr style="background: #f6f7f7;">
                                <th style="font-weight: 600; padding: 15px; width: 45%;">Page Type</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 18%;">YES</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 18%;">NO</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 19%;">It's Complicated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($post_types as $type) :
                                $status = isset($current_options[$type->name]) ? $current_options[$type->name] : $defaults[$type->name] ?? 'yes';
                                $is_critical = in_array($type->name, $critical_sections);
                            ?>
                                <tr>
                                    <td style="padding: 15px; font-weight: 600;">
                                        <?php echo esc_html($type->label); ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                               value="yes" 
                                               <?php checked($status, 'yes'); ?>
                                               <?php echo $is_critical ? 'disabled' : ''; ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                               value="no" 
                                               <?php checked($status, 'no'); ?>
                                               <?php echo $is_critical ? 'disabled' : ''; ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($type->name); ?>]" 
                                               value="advanced" 
                                               <?php checked($status, 'advanced'); ?>
                                               <?php echo $is_critical ? 'disabled' : ''; ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php foreach ($taxonomies as $taxonomy) :
                                $status = isset($current_options[$taxonomy->name]) ? $current_options[$taxonomy->name] : $defaults[$taxonomy->name] ?? 'yes';
                            ?>
                                <tr>
                                    <td style="padding: 15px; font-weight: 600;">
                                        <?php echo esc_html($taxonomy->label); ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($taxonomy->name); ?>]" 
                                               value="yes" 
                                               <?php checked($status, 'yes'); ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($taxonomy->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($taxonomy->name); ?>]" 
                                               value="no" 
                                               <?php checked($status, 'no'); ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($taxonomy->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" 
                                               name="section_index[<?php echo esc_attr($taxonomy->name); ?>]" 
                                               value="advanced" 
                                               <?php checked($status, 'advanced'); ?>
                                               class="bb-radio-toggle"
                                               data-section="<?php echo esc_attr($taxonomy->name); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit" style="margin-top: 20px;">
                        <input type="submit" 
                               name="bb_save_global_map" 
                               class="button button-primary button-large" 
                               value="Save Settings">
                    </p>
                </form>
            </div>

<!-- RIGHT COLUMN: Live Sitemap Preview -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 20px; height: fit-content; position: sticky; top: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 14px; color: #333;">
                    🗺️ Sitemap: This is what you're telling Google
                </h3>
                
                <div id="bb-sitemap-preview" style="background: white; border: 1px solid #e0e0e0; border-radius: 3px; padding: 15px; font-size: 13px; line-height: 2; color: #333; max-height: 500px; overflow-y: auto;">
                    <!-- Rendered directly, no loading state -->
                    <?php
                    $all_sections = array_merge($post_types, $taxonomies);
                    foreach ($all_sections as $section) :
                        $status = isset($current_options[$section->name]) ? $current_options[$section->name] : $defaults[$section->name] ?? 'yes';
                        $is_indexed = $status === 'yes';
                        $icon = $is_indexed ? '✓' : '✗';
                        $color = $is_indexed ? '#46b450' : '#dc3232';
                        $desc = bare_bones_seo_get_section_description($section->name);
                    ?>
                        <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;">
                            <div style="color: <?php echo esc_attr($color); ?>; font-weight: bold; margin-bottom: 3px;">
                                <span><?php echo esc_html($icon); ?></span>
                                <?php echo esc_html($section->label); ?>
                            </div>
                            <div style="color: #999; font-size: 12px; margin-left: 20px;">
                                <?php echo esc_html($desc); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p style="margin-top: 15px; font-size: 12px; color: #666;">
                    ✓ = Included in sitemap<br>
                    ✗ = Hidden from Google
                </p>
            </div>
        </div>
    </div>

    <!-- Inline data for JavaScript -->
    <script type="application/json" id="bb-section-data">
        {
            "sections": {
                <?php 
                $all_sections = array_merge($post_types, $taxonomies);
                $section_data = array();
                foreach ($all_sections as $section) {
                    $status = isset($current_options[$section->name]) ? $current_options[$section->name] : $defaults[$section->name] ?? 'yes';
                    $section_data[$section->name] = array(
                        'label' => $section->label,
                        'status' => $status,
                        'default_status' => $defaults[$section->name] ?? 'yes'
                    );
                }
                echo json_encode($section_data);
                ?>
            }
        }
    </script>

    <?php
}
