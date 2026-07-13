<?php
/**
 * Site Level Search Engine Instructions — Bare Bones SEO
 *
 * Fetches the real wp-sitemap.xml to build controls dynamically.
 * Detects sitemap conflicts and locks the page if found.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detect plugins that override the WordPress core sitemap.
 *
 * @since 1.0.3
 * @return array { conflict: bool, plugin_name: string }
 */
function bare_bones_seo_detect_sitemap_conflict() {
    $plugins = array(
        array(
            'name'  => 'Yoast SEO',
            'check' => function() {
                return class_exists('WPSEO_Sitemaps') ||
                       (function_exists('wpseo_init') && get_option('wpseo_xml') !== false);
            },
        ),
        array(
            'name'  => 'Rank Math',
            'check' => function() {
                return class_exists('RankMath\Sitemap\Sitemap');
            },
        ),
        array(
            'name'  => 'All in One SEO',
            'check' => function() {
                return class_exists('AIOSEO\Plugin\Common\Sitemap\Sitemap');
            },
        ),
        array(
            'name'  => 'Google XML Sitemaps',
            'check' => function() {
                return function_exists('sm_init') || class_exists('GoogleSitemapGeneratorLoader');
            },
        ),
        array(
            'name'  => 'Simple Sitemap',
            'check' => function() {
                return class_exists('Simple_Sitemap');
            },
        ),
        array(
            'name'  => 'Slim SEO',
            'check' => function() {
                return class_exists('SlimSEO\Sitemap\Sitemap');
            },
        ),
    );

    foreach ($plugins as $plugin) {
        if (call_user_func($plugin['check'])) {
            return array('conflict' => true, 'plugin_name' => $plugin['name']);
        }
    }

    return array('conflict' => false, 'plugin_name' => '');
}

/**
 * Fetch and parse the WordPress core sitemap index.
 *
 * @since 1.0.3
 * @return array|false Array of sections or false on failure
 */
function bare_bones_seo_fetch_sitemap_sections() {
    $sitemap_url = get_home_url() . '/wp-sitemap.xml';

    $response = wp_remote_get($sitemap_url, array('timeout' => 10));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return false;
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    libxml_clear_errors();

    if (!$xml) {
        return false;
    }

    $sections = array();

    foreach ($xml->sitemap as $sitemap) {
        $url = (string) $sitemap->loc;

        // Get the filename only, e.g. "wp-sitemap-posts-post-1.xml"
        $filename = basename($url);

        // Remove prefix "wp-sitemap-" and suffix "-N.xml" to get middle part
        // e.g. "posts-post", "taxonomies-category", "users"
        $middle = preg_replace('/^wp-sitemap-/', '', $filename);
        $middle = preg_replace('/-\d+\.xml$/', '', $middle);

        // Split on first dash to get type and key
        // "posts-post" → type=posts, key=post
        // "posts-kadence_element" → type=posts, key=kadence_element
        // "taxonomies-category" → type=taxonomies, key=category
        // "users" → type=users, key=user
        $parts = explode('-', $middle, 2);
        $type  = $parts[0];
        $key   = isset($parts[1]) ? $parts[1] : 'user';

        // Normalize type to what WordPress uses
        if (!in_array($type, array('posts', 'taxonomies', 'users'))) {
            continue;
        }

        if ($type === 'users') {
            $key = 'user';
        }

        // Already added this section (multiple pages of same type) — just increment
        if (isset($sections[$key])) {
            $sections[$key]['pages']++;
            continue;
        }

        $sections[$key] = array(
            'key'   => $key,
            'type'  => $type,
            'url'   => $url,
            'pages' => 1,
        );
    }

    return !empty($sections) ? $sections : false;
}

/**
 * Get actual item count for a section.
 *
 * @since 1.0.3
 * @param string $key  Section key
 * @param string $type posts|taxonomies|users
 * @return int
 */
function bare_bones_seo_get_section_count($key, $type) {
    if ($type === 'users') {
        $data = count_users();
        return (int) ($data['total_users'] ?? 0);
    }

    if ($type === 'taxonomies') {
        $count = wp_count_terms(array('taxonomy' => $key, 'hide_empty' => false));
        return is_wp_error($count) ? 0 : (int) $count;
    }

    // Post type
    $counts = wp_count_posts($key);
    return (int) ($counts->publish ?? 0);
}

/**
 * Get human-readable label for a section.
 *
 * @since 1.0.3
 * @param string $key
 * @param string $type
 * @return string
 */
function bare_bones_seo_get_section_label($key, $type) {
    if ($type === 'users') {
        return 'Author Archives';
    }

    if ($type === 'taxonomies') {
        $taxonomy = get_taxonomy($key);
        return $taxonomy ? $taxonomy->label : ucwords(str_replace('_', ' ', $key));
    }

    $post_type = get_post_type_object($key);
    return $post_type ? $post_type->label : ucwords(str_replace('_', ' ', $key));
}

/**
 * Get plain-English description for a section.
 *
 * Known sections get specific descriptions.
 * Unknown sections get a generic fallback.
 *
 * @since 1.0.3
 * @param string $key
 * @param string $type
 * @return string
 */
function bare_bones_seo_get_section_description($key, $type) {
    $descriptions = array(
        'post'     => 'Your blog posts and articles. Always indexed.',
        'page'     => 'Your site pages (About, Contact, Services, etc.). Always indexed.',
        'category' => 'Archive pages that organize your posts. Helps Google understand your site structure.',
        'post_tag' => 'Often create thin or duplicate content. Noindexed by default to preserve crawl budget.',
        'user'     => 'Only index these if your author pages include a photo, bio, and original content.',
    );

    return $descriptions[$key] ?? 'Index if these are actual pages with unique, valuable content that should appear in Google as standalone pages.';
}

/**
 * Get smart default indexing status for a section.
 *
 * @since 1.0.3
 * @param string $key
 * @return string yes|no
 */
function bare_bones_seo_get_section_default($key) {
    $defaults = array(
        'post'     => 'yes',
        'page'     => 'yes',
        'category' => 'yes',
        'post_tag' => 'no',
        'user'     => 'no',
    );

    return $defaults[$key] ?? 'yes';
}

/**
 * Render the Site Level Search Engine Instructions screen.
 *
 * @since 1.0.0
 */
function bare_bones_seo_render_global_map_screen() {
    // Handle save
    if (isset($_POST['bb_save_global_map']) && check_admin_referer(BARE_BONES_SEO_NONCE_GLOBAL_MAP)) {
        $submitted = isset($_POST['section_index']) ? $_POST['section_index'] : array();
        $clean     = array();
        foreach ($submitted as $key => $val) {
            $clean[sanitize_key($key)] = sanitize_text_field($val);
        }
        update_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, $clean);
        echo '<div class="updated"><p>Your settings have been saved.</p></div>';
    }

    $current_options    = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());
    $conflict           = bare_bones_seo_detect_sitemap_conflict();
    $has_conflict       = $conflict['conflict'];
    $conflict_plugin    = $conflict['plugin_name'];
    $sections           = bare_bones_seo_fetch_sitemap_sections();
    $sitemap_unreachable = ($sections === false);
    $critical_sections  = array('post', 'page');
    ?>
    <div class="wrap">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #ccc; padding-bottom:15px;">
            <h1 style="margin:0;">Site Level Search Engine Instructions — Bare Bones SEO</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display:inline-flex; align-items:center; gap:5px;">
                <span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px; margin-top:2px;"></span>
                Documentation
            </a>
        </div>

        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="?page=bare-bones-seo" class="nav-tab nav-tab-active">Site Level Search Engine Instructions</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab">Bulk Manager</a>
        </h2>

        <?php if ($has_conflict) : ?>
            <div style="background:#fff5f5; border-left:4px solid #dc3232; padding:15px 20px; border-radius:3px; margin-bottom:20px;">
                <strong>⚠️ <?php echo esc_html($conflict_plugin); ?> sitemap is overriding your built-in WordPress sitemap.</strong>
                Please deactivate your <?php echo esc_html($conflict_plugin); ?> sitemap before making changes here.
            </div>
        <?php endif; ?>

        <?php if (!$has_conflict && $sitemap_unreachable) : ?>
            <div style="background:#fff8e5; border-left:4px solid #f0ad4e; padding:15px 20px; border-radius:3px; margin-bottom:20px;">
                <strong>⚠️ Could not reach your WordPress sitemap.</strong>
                Make sure it is enabled at <a href="<?php echo esc_url(get_home_url() . '/wp-sitemap.xml'); ?>" target="_blank"><?php echo esc_url(get_home_url() . '/wp-sitemap.xml'); ?></a>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-top:20px;">

            <!-- LEFT: Controls -->
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field(BARE_BONES_SEO_NONCE_GLOBAL_MAP); ?>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr style="background:#f6f7f7;">
                                <th style="font-weight:600; padding:15px;">Section</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:15%;">YES</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:15%;">NO</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:19%;">It's Complicated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sections) :
                                foreach ($sections as $section) :
                                    $key         = $section['key'];
                                    $type        = $section['type'];
                                    $label       = bare_bones_seo_get_section_label($key, $type);
                                    $description = bare_bones_seo_get_section_description($key, $type);
                                    $default     = bare_bones_seo_get_section_default($key);
                                    $status      = isset($current_options[$key]) ? $current_options[$key] : $default;
                                    $is_critical = in_array($key, $critical_sections);
                                    $count       = bare_bones_seo_get_section_count($key, $type);
                                    $disabled    = ($has_conflict || $is_critical) ? 'disabled' : '';
                                ?>
                                    <tr>
                                        <td style="padding:15px; vertical-align:top;">
                                            <strong><?php echo esc_html($label); ?> (<?php echo esc_html($count); ?>)</strong>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="radio"
                                                   name="section_index[<?php echo esc_attr($key); ?>]"
                                                   value="yes"
                                                   <?php checked($status, 'yes'); ?>
                                                   <?php echo esc_attr($disabled); ?>>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="radio"
                                                   name="section_index[<?php echo esc_attr($key); ?>]"
                                                   value="no"
                                                   <?php checked($status, 'no'); ?>
                                                   <?php echo esc_attr($disabled); ?>>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="radio"
                                                   name="section_index[<?php echo esc_attr($key); ?>]"
                                                   value="advanced"
                                                   <?php checked($status, 'advanced'); ?>
                                                   <?php echo esc_attr($disabled); ?>>
                                        </td>
                                    </tr>
                                    <tr style="background:#f9f9f9;">
                                        <td colspan="4" style="padding:6px 15px 12px; font-size:12px; color:#666;">
                                            <?php echo esc_html($description); ?>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else : ?>
                                <tr>
                                    <td colspan="4" style="padding:20px; color:#666; text-align:center;">
                                        <?php echo $has_conflict ? 'Controls locked until sitemap conflict is resolved.' : 'Could not load sitemap sections.'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (!$has_conflict && $sections) : ?>
                        <p class="submit" style="margin-top:20px;">
                            <input type="submit" name="bb_save_global_map" class="button button-primary button-large" value="Save Settings">
                        </p>
                    <?php endif; ?>
                </form>
            </div>

            <!-- RIGHT: Real sitemap preview -->
            <div style="background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:20px; height:fit-content; position:sticky; top:20px;">
                <h3 style="margin-top:0; margin-bottom:15px; font-size:14px; color:#333;">
                    🗺️ This is what you're pushing to search engines and AI
                </h3>

                <div style="background:white; border:1px solid #e0e0e0; border-radius:3px; padding:15px; font-size:12px; line-height:2; color:#333; max-height:600px; overflow-y:auto;">
                    <?php if ($has_conflict) : ?>
                        <div style="color:#dc3232; padding:10px 0;">
                            ⚠️ Sitemap controlled by <?php echo esc_html($conflict_plugin); ?>
                        </div>
                    <?php elseif ($sitemap_unreachable) : ?>
                        <div style="color:#f0ad4e; padding:10px 0;">
                            ⚠️ Sitemap unreachable
                        </div>
                    <?php elseif ($sections) : ?>
                        <div style="color:#666; margin-bottom:10px; font-family:monospace;">
                            <a href="<?php echo esc_url(get_home_url() . '/wp-sitemap.xml'); ?>" target="_blank" style="color:#0073aa; text-decoration:none;">
                                /wp-sitemap.xml
                            </a>
                        </div>
                        <?php foreach ($sections as $section) :
                            $key        = $section['key'];
                            $type       = $section['type'];
                            $default    = bare_bones_seo_get_section_default($key);
                            $status     = isset($current_options[$key]) ? $current_options[$key] : $default;
                            $is_indexed = ($status === 'yes');
                            $icon       = $is_indexed ? '✓' : '✗';
                            $color      = $is_indexed ? '#46b450' : '#dc3232';
                            $count      = bare_bones_seo_get_section_count($key, $type);
                        ?>
                            <div style="color:<?php echo esc_attr($color); ?>; margin-bottom:8px; font-family:monospace;">
                                <span style="font-weight:bold;"><?php echo esc_html($icon); ?></span>
                                <a href="<?php echo esc_url($section['url']); ?>" target="_blank" style="color:#0073aa; text-decoration:none;">
                                    <?php echo esc_html(basename($section['url'])); ?>
                                </a>
                                <span style="color:#999; font-size:11px;">(<?php echo esc_html($count); ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <p style="margin-top:15px; font-size:11px; color:#666; line-height:1.6;">
                    <strong>✓</strong> = Included &nbsp;|&nbsp; <strong>✗</strong> = Hidden<br>
                    Click any link to preview what Google sees.
                </p>
            </div>
        </div>
    </div>
    <?php
}
