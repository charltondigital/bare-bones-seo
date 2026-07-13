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
        'user'      => 'no',
    );

    // Get counts for display
    $counts = array(
        'post'      => wp_count_posts('post')->publish ?? 0,
        'page'      => wp_count_posts('page')->publish ?? 0,
        'category'  => wp_count_terms(array('taxonomy' => 'category')) ?? 0,
        'post_tag'  => wp_count_terms(array('taxonomy' => 'post_tag')) ?? 0,
    );
    $user_data = count_users();
    $counts['user'] = $user_data['total_users'] ?? 0;
    
    $home_url = get_home_url();
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

            <!-- LEFT COLUMN: Controls with Descriptions -->
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field(BARE_BONES_SEO_NONCE_GLOBAL_MAP); ?>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr style="background: #f6f7f7;">
                                <th style="font-weight: 600; padding: 15px;">Section</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 18%;">YES</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 18%;">NO</th>
                                <th style="font-weight: 600; text-align: center; padding: 15px; width: 19%;">It's Complicated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // POSTS
                            $status = isset($current_options['post']) ? $current_options['post'] : $defaults['post'];
                            $post_count = $counts['post'];
                            ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: top;">
                                    <strong>Blog Posts</strong>
                                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                        <?php echo esc_html($post_count); ?> posts
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post]" value="yes" <?php checked($status, 'yes'); ?> disabled>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post]" value="no" <?php checked($status, 'no'); ?> disabled>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post]" value="advanced" <?php checked($status, 'advanced'); ?> disabled>
                                </td>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                    Your blog posts and articles. Always indexed by default.
                                </td>
                            </tr>

                            <?php 
                            // PAGES
                            $status = isset($current_options['page']) ? $current_options['page'] : $defaults['page'];
                            $page_count = $counts['page'];
                            ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: top;">
                                    <strong>Pages</strong>
                                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                        <?php echo esc_html($page_count); ?> pages
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[page]" value="yes" <?php checked($status, 'yes'); ?> disabled>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[page]" value="no" <?php checked($status, 'no'); ?> disabled>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[page]" value="advanced" <?php checked($status, 'advanced'); ?> disabled>
                                </td>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                    Your site pages (About, Contact, Services, etc.). Always indexed by default.
                                </td>
                            </tr>

                            <?php 
                            // CATEGORIES
                            $status = isset($current_options['category']) ? $current_options['category'] : $defaults['category'];
                            $cat_count = $counts['category'];
                            ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: top;">
                                    <strong>Categories</strong>
                                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                        <?php echo esc_html($cat_count); ?> categories
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[category]" value="yes" <?php checked($status, 'yes'); ?> class="bb-radio-toggle" data-section="category">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[category]" value="no" <?php checked($status, 'no'); ?> class="bb-radio-toggle" data-section="category">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[category]" value="advanced" <?php checked($status, 'advanced'); ?> class="bb-radio-toggle" data-section="category">
                                </td>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                    Archive pages that organize your posts. Helps Google understand your site structure.
                                </td>
                            </tr>

                            <?php 
                            // TAGS
                            $status = isset($current_options['post_tag']) ? $current_options['post_tag'] : $defaults['post_tag'];
                            $tag_count = $counts['post_tag'];
                            ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: top;">
                                    <strong>Tags</strong>
                                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                        <?php echo esc_html($tag_count); ?> tags
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post_tag]" value="yes" <?php checked($status, 'yes'); ?> class="bb-radio-toggle" data-section="post_tag">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post_tag]" value="no" <?php checked($status, 'no'); ?> class="bb-radio-toggle" data-section="post_tag">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[post_tag]" value="advanced" <?php checked($status, 'advanced'); ?> class="bb-radio-toggle" data-section="post_tag">
                                </td>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                    Often create thin or duplicate content. Noindexed by default to preserve crawl budget.
                                </td>
                            </tr>

                            <?php 
                            // AUTHOR ARCHIVES
                            $status = isset($current_options['user']) ? $current_options['user'] : $defaults['user'];
                            $user_count = $counts['user'];
                            ?>
                            <tr>
                                <td style="padding: 15px; vertical-align: top;">
                                    <strong>Author Archives</strong>
                                    <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                        <?php echo esc_html($user_count); ?> authors
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[user]" value="yes" <?php checked($status, 'yes'); ?> class="bb-radio-toggle" data-section="user">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[user]" value="no" <?php checked($status, 'no'); ?> class="bb-radio-toggle" data-section="user">
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="radio" name="section_index[user]" value="advanced" <?php checked($status, 'advanced'); ?> class="bb-radio-toggle" data-section="user">
                                </td>
                            </tr>
                            <tr style="background: #f5f5f5;">
                                <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                    Archive pages showing all posts by each author. Duplicate content risk. Noindexed by default.
                                </td>
                            </tr>

                            <?php 
                            // Custom post types (if any)
                            foreach ($post_types as $type) :
                                if (in_array($type->name, array('post', 'page', 'attachment'))) continue;
                                
                                $status = isset($current_options[$type->name]) ? $current_options[$type->name] : 'yes';
                                $type_count = wp_count_posts($type->name)->publish ?? 0;
                            ?>
                                <tr>
                                    <td style="padding: 15px; vertical-align: top;">
                                        <strong><?php echo esc_html($type->label); ?></strong>
                                        <div style="font-size: 11px; color: #999; margin-top: 3px;">
                                            <?php echo esc_html($type_count); ?> items
                                        </div>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" name="section_index[<?php echo esc_attr($type->name); ?>]" value="yes" <?php checked($status, 'yes'); ?> class="bb-radio-toggle" data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" name="section_index[<?php echo esc_attr($type->name); ?>]" value="no" <?php checked($status, 'no'); ?> class="bb-radio-toggle" data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="radio" name="section_index[<?php echo esc_attr($type->name); ?>]" value="advanced" <?php checked($status, 'advanced'); ?> class="bb-radio-toggle" data-section="<?php echo esc_attr($type->name); ?>">
                                    </td>
                                </tr>
                                <tr style="background: #f5f5f5;">
                                    <td colspan="4" style="padding: 8px 15px; font-size: 12px; color: #666;">
                                        Custom content type for your site.
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit" style="margin-top: 20px;">
                        <input type="submit" name="bb_save_global_map" class="button button-primary button-large" value="Save Settings">
                    </p>
                </form>
            </div>

            <!-- RIGHT COLUMN: Actual Sitemap with Clickable Links -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 20px; height: fit-content; position: sticky; top: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 14px; color: #333;">
                    🗺️ It's Complicated: What Google will see
                </h3>
                
                <div style="background: white; border: 1px solid #e0e0e0; border-radius: 3px; padding: 15px; font-size: 12px; line-height: 2; color: #333; max-height: 600px; overflow-y: auto;">
                    <?php
                    $sitemap_sections = array(
                        'post'      => 'Blog Posts',
                        'page'      => 'Pages',
                        'category'  => 'Categories',
                        'post_tag'  => 'Tags',
                        'user'      => 'Author Archives',
                    );

                    foreach ($sitemap_sections as $section_key => $section_label) :
                        $section_status = isset($current_options[$section_key]) ? $current_options[$section_key] : $defaults[$section_key];
                        $is_indexed = $section_status === 'yes';
                        $icon = $is_indexed ? '✓' : '✗';
                        $color = $is_indexed ? '#46b450' : '#dc3232';
                        $count = $counts[$section_key] ?? 0;
                        $sitemap_url = $home_url . '/sitemap-' . $section_key . '-1.xml';
                    ?>
                        <div style="color: <?php echo esc_attr($color); ?>; margin-bottom: 10px;">
                            <span style="font-weight: bold;"><?php echo esc_html($icon); ?></span>
                            <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" style="color: #0073aa; text-decoration: none;">
                                sitemap-<?php echo esc_html($section_key); ?>.xml
                            </a>
                            <span style="color: #999; font-size: 11px;">(<?php echo esc_html($count); ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p style="margin-top: 15px; font-size: 11px; color: #666; line-height: 1.6;">
                    <strong>✓</strong> = Included in sitemap<br>
                    <strong>✗</strong> = Hidden from Google<br>
                    <br>
                    Click any link to preview what Google sees.
                </p>
            </div>
        </div>
    </div>

    <?php
}
