<?php
/**
 * SEO Page Settings — Bulk Manager
 *
 * Displays all published posts and pages in an expandable table.
 * Each row shows saved Bare Bones SEO values (blank if not set).
 * Expanding a row shows the full page-level SEO settings UI.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler: Save all four SEO fields for a single post.
 *
 * Accepts: post_id, title, desc, schema, should_index
 * Security: nonce + capability check + post existence check
 *
 * @since 1.0.3
 * @return void
 */
add_action('wp_ajax_' . BARE_BONES_SEO_AJAX_ACTION, 'bare_bones_seo_process_bulk_ajax_save');
function bare_bones_seo_process_bulk_ajax_save() {
    check_ajax_referer(BARE_BONES_SEO_NONCE_BULK_AJAX, 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    if (!isset($_POST['post_id'])) {
        wp_send_json_error('Missing post ID');
        return;
    }

    $post_id = intval($_POST['post_id']);

    if ($post_id <= 0 || !get_post($post_id)) {
        wp_send_json_error('Invalid post ID');
        return;
    }

    // Save all four fields
    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => isset($_POST['bb_seo_title']) ? $_POST['bb_seo_title'] : '',
        'desc'         => isset($_POST['bb_seo_desc']) ? $_POST['bb_seo_desc'] : '',
        'schema'       => isset($_POST['bb_seo_schema']) ? $_POST['bb_seo_schema'] : '',
        'should_index' => isset($_POST['bb_seo_should_index']) ? $_POST['bb_seo_should_index'] : 'yes',
    ));

    wp_send_json_success(array('message' => 'Saved'));
}

/**
 * Render the Bulk Manager screen.
 *
 * Shows a table of all published posts/pages. Each row displays
 * saved Bare Bones SEO values (blank if nothing set yet).
 * Clicking a row expands the full page-level SEO settings UI.
 *
 * @since 1.0.0
 * @return void
 */
function bare_bones_seo_render_bulk_manager_screen() {
    // Query all published pages and posts
    $query = new WP_Query(array(
        'post_type'      => array('page', 'post'),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
    ?>
    <div class="wrap">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #ccc; padding-bottom:15px;">
            <h1 style="margin:0;">☠️ SEO Page Settings — Bulk Manager</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display:inline-flex; align-items:center; gap:5px;">
                <span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px; margin-top:2px;"></span>
                Documentation
            </a>
        </div>

        <!-- Tabs -->
        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="?page=bare-bones-seo" class="nav-tab">Site Level Search Engine Instructions</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab nav-tab-active">Bulk Manager</a>
        </h2>

        <!-- Table -->
        <table class="wp-list-table widefat fixed" style="margin-top:20px; table-layout:fixed;">
            <colgroup>
                <col style="width:20%;">
                <col style="width:25%;">
                <col style="width:10%;">
                <col style="width:35%;">
                <col style="width:10%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em;">Title</th>
                    <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em;">Description</th>
                    <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em;">Indexation</th>
                    <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em;">Schema</th>
                    <th style="padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($query->have_posts()) :
                    while ($query->have_posts()) :
                        $query->the_post();
                        global $post;

                        $meta         = bare_bones_seo_get_page_meta($post->ID);
                        $index_status = get_post_meta($post->ID, BARE_BONES_SEO_META_INDEX, true);
                        $post_type    = get_post_type_object(get_post_type());
                        $uid          = 'bb-' . $post->ID;

                        // Indexation badge
                        if ($index_status === 'yes') {
                            $badge = '<span style="color:#46b450; font-size:16px; font-weight:700;">✓</span>';
                        } elseif ($index_status === '') {
                            $badge = '<span style="color:#dc3232; font-size:16px; font-weight:700;">✗</span>';
                        } else {
                            $badge = '<span style="color:#dc3232; font-size:16px; font-weight:700;">✗</span>';
                        }
                ?>
                    <!-- COLLAPSED ROW -->
                    <tr id="<?php echo esc_attr($uid); ?>-collapsed"
                        style="cursor:pointer;"
                        onclick="bbToggleRow(<?php echo esc_js($post->ID); ?>)">
                        <td style="padding:10px 12px; vertical-align:middle;">
                            <span style="color:#888; margin-right:6px; font-size:11px;" id="<?php echo esc_attr($uid); ?>-chevron">▶</span>
                            <strong style="font-size:13px;"><?php the_title(); ?></strong>
                            <div style="font-size:11px; color:#999; margin-top:2px;"><?php echo esc_html($post_type->labels->singular_name); ?></div>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle; overflow:hidden;">
                            <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12px; color:#555;">
                                <?php echo esc_html($meta['desc']); ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle;">
                            <?php echo $badge; ?>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle; overflow:hidden;">
                            <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:11px; color:#888; font-family:monospace;">
                                <?php echo esc_html($meta['schema']); ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle;"></td>
                    </tr>

                    <!-- EXPANDED ROW -->
                    <tr id="<?php echo esc_attr($uid); ?>-expanded" style="display:none;">
                        <td colspan="5" style="padding:20px; background:#fafafa; border-top:2px solid #ddd; border-bottom:2px solid #ddd;">
                            <?php bare_bones_seo_render_fields($post, true); ?>

                            <!-- Save / Cancel buttons -->
                            <div style="display:flex; gap:8px; margin-top:16px; justify-content:flex-end; border-top:1px solid #eee; padding-top:16px;">
                                <button type="button"
                                        class="button"
                                        onclick="bbToggleRow(<?php echo esc_js($post->ID); ?>)"
                                        style="font-size:12px;">
                                    Cancel
                                </button>
                                <button type="button"
                                        class="button button-primary bb-bulk-save"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        data-uid="<?php echo esc_attr($uid); ?>"
                                        style="font-size:12px;">
                                    Update
                                </button>
                            </div>
                        </td>
                    </tr>

                <?php
                    endwhile;
                    wp_reset_postdata();
                else : ?>
                    <tr>
                        <td colspan="5" style="padding:20px; color:#666; text-align:center;">
                            No published pages or posts found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php wp_nonce_field(BARE_BONES_SEO_NONCE_BULK_AJAX, 'bb_bulk_nonce_field'); ?>
    </div>
    <?php
}
