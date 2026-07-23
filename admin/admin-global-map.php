<?php
/**
 * Indexation — Bare Bones SEO
 *
 * Controls built from WordPress internals matching core sitemap logic.
 * Only shows sections with at least 1 published item.
 * Right column shows source of truth — all sections with ✗ for removed ones.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build the list of sections that WordPress would include in its sitemap.
 *
 * Matches WordPress core sitemap logic exactly:
 * - Public post types with at least 1 published post
 * - Public taxonomies with at least 1 term that has published posts
 * - Users (author archives) only if at least 1 published post exists
 *
 * Sections with zero items are excluded — they won't be in the sitemap
 * and would just clutter the UI.
 *
 * @return array Array of section data keyed by section key
 */
function bare_bones_seo_get_sitemap_sections() {
    $sections = array();

    // POST TYPES
    $post_types_public = get_post_types(array('public' => true), 'objects');
    $post_types_queryable = get_post_types(array('publicly_queryable' => true), 'objects');
    $post_types = array_merge($post_types_public, $post_types_queryable);

    foreach ($post_types as $post_type) {
        // Skip attachments — WordPress excludes these from sitemaps
        if ($post_type->name === 'attachment') {
            continue;
        }

        $count = wp_count_posts($post_type->name);
        $published = isset($count->publish) ? (int) $count->publish : 0;

        // Only include if at least 1 published post
        if ($published === 0) {
            continue;
        }

        $sections[$post_type->name] = array(
            'key'   => $post_type->name,
            'type'  => 'posts',
            'label' => $post_type->label,
            'count' => $published,
            'url'   => home_url('/wp-sitemap-posts-' . $post_type->name . '-1.xml'),
        );
    }

    // TAXONOMIES
    $taxonomies = get_taxonomies(array('public' => true), 'objects');

    foreach ($taxonomies as $taxonomy) {
        $count = wp_count_terms(array(
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => true, // Only count terms with published posts
        ));

        if (is_wp_error($count) || (int) $count === 0) {
            continue;
        }

        $sections[$taxonomy->name] = array(
            'key'   => $taxonomy->name,
            'type'  => 'taxonomies',
            'label' => $taxonomy->label,
            'count' => (int) $count,
            'url'   => home_url('/wp-sitemap-taxonomies-' . $taxonomy->name . '-1.xml'),
        );
    }

    // USERS (Author Archives)
    $published_posts = wp_count_posts('post');
    $total_published = isset($published_posts->publish) ? (int) $published_posts->publish : 0;

    if ($total_published > 0) {
        $user_data = count_users();
        $user_count = (int) ($user_data['total_users'] ?? 0);

        if ($user_count > 0) {
            $sections['user'] = array(
                'key'   => 'user',
                'type'  => 'users',
                'label' => 'Author Archives',
                'count' => $user_count,
                'url'   => home_url('/wp-sitemap-users-1.xml'),
            );
        }
    }

    return $sections;
}

/**
 * Archive views the noindex engine understands but that WordPress core does not
 * put in a sitemap. Without these rows the keys existed only in code, so the
 * settings always resolved to the 'yes' default and could never be switched off.
 */
function bare_bones_seo_get_archive_sections() {
    return array(
        'paged' => array(
            'key'   => 'paged',
            'type'  => 'archives',
            'label' => 'Paginated Archives',
        ),
        'date' => array(
            'key'   => 'date',
            'type'  => 'archives',
            'label' => 'Date Archives',
        ),
    );
}

/**
 * Get plain-English description for a section.
 *
 * @param string $key
 * @param string $type
 * @return string
 */
function bare_bones_seo_get_section_description($key, $type) {
    $descriptions = array(
        'post'     => 'Your blog posts and articles. You\'ll almost always want these indexed.',
        'page'     => 'Your site pages (About, Contact, Services, etc.). Normally keep these indexed.',
        'category' => 'Archive pages that organize your posts. Helps Google understand your site structure.',
        'post_tag' => 'Often create thin or duplicate content. Consider noindexing to preserve crawl budget.',
        'user'     => 'Only index these if your author pages include a photo, bio, and original content.',
        'paged'    => 'Page 2, 3, 4... of any archive. These repeat content from page one and are a common source of duplicate-content dilution.',
        'date'     => 'Year, month, and day archives. Rarely useful to searchers and almost always duplicate content already covered elsewhere.',
    );

    return $descriptions[$key] ?? 'Index if these are actual pages with unique, valuable content that should appear in Google as standalone pages.';
}

/**
 * Render the Indexation screen.
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

        // Clear native core sitemap cached transients to apply changes instantly
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wp_sitemaps_%'" );
        flush_rewrite_rules( false );

        echo '<div class="updated"><p>Your settings have been saved and sitemaps rebuilt instantly!</p></div>';
    }

    $current_options = get_option(BARE_BONES_SEO_OPTION_GLOBAL_MAP, array());
    $conflict        = bare_bones_seo_detect_sitemap_conflict();
    $has_conflict    = $conflict['conflict'];
    $conflict_plugin = $conflict['plugin_name'];
    $sections        = bare_bones_seo_get_sitemap_sections();
    ?>
    
    <?php if ($has_conflict) : ?>
        <div style="background:#fff5f5; border-left:4px solid #dc3232; padding:15px 20px; border-radius:3px; margin-bottom:20px;">
            <strong>⚠️ <?php echo esc_html($conflict_plugin); ?> sitemap is overriding your built-in WordPress sitemap.</strong>
            Please deactivate your <?php echo esc_html($conflict_plugin); ?> sitemap before making changes here.
        </div>
    <?php endif; ?>

    <!-- CONTAINER BOX -->
    <div style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; margin-top:20px;">
        
        <!-- Box Page Header -->
        <div style="border-bottom:1px solid #f0f0f0; padding-bottom:15px; margin-bottom:20px;">
            <h2 style="margin:0; font-size:16px; font-weight:600; color:#1d2327;">Global Indexation Settings</h2>
            <p style="margin:5px 0 0; font-size:13px; color:#646970;">Should search engines and AI scrapers index these sections? Review each section description below to configure your routing rules.</p>
        </div>

        <!-- TWO-COLUMN LAYOUT -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">

            <!-- LEFT: Controls -->
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field(BARE_BONES_SEO_NONCE_GLOBAL_MAP); ?>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr style="background:#f6f7f7;">
                                <th style="font-weight:600; padding:15px; width:34%;">Section</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:16%;">YES</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:16%;">NO</th>
                                <th style="font-weight:600; text-align:center; padding:15px; width:34%;">Remove from Sitemap Only</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sections)) :
                                foreach ($sections as $section) :
                                    $key         = $section['key'];
                                    $label       = $section['label'];
                                    $count       = $section['count'];
                                    $description = bare_bones_seo_get_section_description($key, $section['type']);
                                    $status      = isset($current_options[$key]) ? $current_options[$key] : 'yes';
                                    $disabled    = $has_conflict ? 'disabled' : '';

                                    // Core content is deliberately not switchable here. Blocking a whole site
                                    // belongs in WordPress's own Reading setting, where it's expected, reversible,
                                    // and flagged by the warning at the top of every admin screen.
                                    if (in_array($key, array('page', 'post'), true)) : ?>
                                    <tr>
                                        <td style="padding:15px; vertical-align:top;">
                                            <strong><?php echo esc_html($label); ?> (<?php echo esc_html($count); ?>)</strong>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle; color:#46b450; font-weight:600;">Always</td>
                                        <td style="text-align:center; vertical-align:middle; color:#c3c4c7;">&mdash;</td>
                                        <td style="text-align:center; vertical-align:middle; color:#c3c4c7;">&mdash;</td>
                                    </tr>
                                    <tr style="background:#f9f9f9;">
                                        <td colspan="4" style="padding:6px 15px 12px; font-size:12px; color:#666;">
                                            Always indexed &mdash; there's no version of a live site where hiding all of these is the right call. To hide the whole site while it's in development, use WordPress's <a href="<?php echo esc_url(admin_url('options-reading.php')); ?>">Reading settings</a>, which Bare Bones SEO will warn you about for as long as it stays on.
                                        </td>
                                    </tr>
                                    <?php else : ?>
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
                                                   value="complicated_sitemap"
                                                   <?php checked($status, 'complicated_sitemap'); ?>
                                                   <?php echo esc_attr($disabled); ?>>
                                        </td>
                                    </tr>
                                    <tr style="background:#f9f9f9;">
                                        <td colspan="4" style="padding:6px 15px 12px; font-size:12px; color:#666;">
                                            <?php echo esc_html($description); ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach;

                                foreach (bare_bones_seo_get_archive_sections() as $section) :
                                    $key         = $section['key'];
                                    $label       = $section['label'];
                                    $description = bare_bones_seo_get_section_description($key, $section['type']);
                                    $status      = isset($current_options[$key]) ? $current_options[$key] : 'yes';
                                    $disabled    = $has_conflict ? 'disabled' : '';
                                ?>
                                    <tr>
                                        <td style="padding:15px; vertical-align:top;">
                                            <strong><?php echo esc_html($label); ?></strong>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="radio" name="section_index[<?php echo esc_attr($key); ?>]" value="yes" <?php checked($status, 'yes'); ?> <?php echo esc_attr($disabled); ?>>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="radio" name="section_index[<?php echo esc_attr($key); ?>]" value="no" <?php checked($status, 'no'); ?> <?php echo esc_attr($disabled); ?>>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle; color:#c3c4c7;" title="These views are never in the sitemap, so there is nothing to remove.">&mdash;</td>
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
                                        No sitemap sections found. Make sure you have published content.
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>

                    <?php if (!$has_conflict && !empty($sections)) : ?>
                        <p class="submit" style="margin-top:20px;">
                            <input type="submit" name="bb_save_global_map" class="button button-primary button-large" value="Save Settings">
                        </p>
                    <?php endif; ?>
                </form>
            </div>

            <!-- RIGHT: Source of truth sitemap preview -->
            <div style="background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:20px; height:fit-content; position:sticky; top:20px;">
                <h3 style="margin-top:0; margin-bottom:15px; font-size:14px; color:#333;">
                    This is what you're pushing to search engines and AI
                </h3>

                <div style="background:white; border:1px solid #e0e0e0; border-radius:3px; padding:15px; font-size:12px; line-height:2; color:#333; max-height:600px; overflow-y:auto;">
                    <?php if ($has_conflict) : ?>
                        <div style="color:#dc3232; padding:10px 0;">
                            ⚠️ Sitemap controlled by <?php echo esc_html($conflict_plugin); ?>
                        </div>
                    <?php elseif (empty($sections)) : ?>
                        <div style="color:#888; padding:10px 0;">
                            No published content found.
                        </div>
                    <?php else : ?>
                        <div style="color:#666; margin-bottom:10px; font-family:monospace;">
                            <a href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" target="_blank" style="color:#0073aa; text-decoration:none;">
                                /wp-sitemap.xml
                            </a>
                        </div>
                        <?php foreach ($sections as $section) :
                            $key    = $section['key'];
                            $status = isset($current_options[$key]) ? $current_options[$key] : 'yes';

                            $removed_from_sitemap = in_array($status, array('no', 'complicated_sitemap'));
                            $icon  = $removed_from_sitemap ? '✗' : '✓';
                            $color = $removed_from_sitemap ? '#d63638' : '#46b450';
                        ?>
                            <div style="color:<?php echo esc_attr($color); ?>; margin-bottom:8px; font-family:monospace;">
                                <span style="font-weight:bold;"><?php echo esc_html($icon); ?></span>
                                <a href="<?php echo esc_url($section['url']); ?>" target="_blank" style="color:#0073aa; text-decoration:none;">
                                    <?php echo esc_html(basename($section['url'])); ?>
                                </a>
                                <span style="color:#999; font-size:11px;">(<?php echo esc_html($section['count']); ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <p style="margin-top:15px; font-size:11px; color:#666; line-height:1.6;">
                    <strong>✓</strong> = In sitemap &nbsp;|&nbsp; <strong>✗</strong> = Removed from sitemap<br>
                    Click any link to preview what Google sees.
                </p>
            </div>
        </div>
    </div>
    <?php
}
