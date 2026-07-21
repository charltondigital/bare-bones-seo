<?php
/**
 * SEO Page Settings: Bulk Manager — Bare Bones SEO
 *
 * Expandable table of all published posts and pages.
 * Collapsed row shows saved Bare Bones SEO values (blank if nothing set).
 * Expanding a row shows the full shared page-level SEO settings UI.
 * Red ✗ only shown when actively noindexed or removed from sitemap.
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
 * @since 1.0.3
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

    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => isset($_POST['bb_seo_title'])        ? sanitize_text_field($_POST['bb_seo_title'])        : '',
        'desc'         => isset($_POST['bb_seo_desc'])         ? sanitize_text_field($_POST['bb_seo_desc'])         : '',
        'schema'       => isset($_POST['bb_seo_schema'])       ? $_POST['bb_seo_schema']                            : '',
        'should_index' => isset($_POST['bb_seo_should_index']) ? sanitize_key($_POST['bb_seo_should_index'])          : 'yes',
    ));

    wp_send_json_success(array('message' => 'Saved'));
}

/**
 * Render the Bulk Manager screen.
 *
 * @since 1.0.0
 */
function bare_bones_seo_render_bulk_manager_screen() {
    $query = new WP_Query(array(
        'post_type'      => array('page', 'post'),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
    ?>
    <!-- CONTAINER BOX -->
    <div style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; margin-top:20px;">
        
        <!-- Box Page Header -->
        <div style="border-bottom:1px solid #f0f0f0; padding-bottom:15px; margin-bottom:20px;">
            <h2 style="margin:0; font-size:16px; font-weight:600; color:#1d2327;">Page-Level SEO Meta Manager</h2>
            <p style="margin:5px 0 0; font-size:13px; color:#646970; line-height: 1.5;">
                These parameters append structural metadata directly into the HTML <code>&lt;head&gt;</code> of your live pages. 
                You can manage overrides in bulk below, or access these exact fields inside the block/classic editor at the bottom of individual post edit screens.
            </p>
        </div>

        <table class="wp-list-table widefat fixed" style="table-layout:fixed;">
            <colgroup>
                <col style="width:20%;">
                <col style="width:27%;">
                <col style="width:10%;">
                <col style="width:33%;">
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

                        // Show red ✗ whenever this page isn't plain "index"
                        // (noindexed or removed from sitemap). Legacy values normalize to index.
                        $show_x = bare_bones_seo_state_removes_from_sitemap($index_status);
                        $badge  = $show_x
                            ? '<span style="color:#dc3232; font-size:16px; font-weight:700;">✗</span>'
                            : '';
                ?>
                    <!-- COLLAPSED ROW -->
                    <tr id="<?php echo esc_attr($uid); ?>-collapsed"
                        style="cursor:pointer;"
                        onclick="bbToggleRow(<?php echo esc_js($post->ID); ?>)">
                        <td style="padding:10px 12px; vertical-align:middle;">
                            <span id="<?php echo esc_attr($uid); ?>-chevron"
                                  style="color:#999; margin-right:6px; font-size:11px; display:inline-block; transition:transform 0.15s;">▶</span>
                            <strong style="font-size:13px;"><?php the_title(); ?></strong>
                            <div style="font-size:11px; color:#999; margin-top:2px;"><?php echo esc_html($post_type->labels->singular_name); ?></div>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle; overflow:hidden;">
                            <span id="<?php echo esc_attr($uid); ?>-desc-preview"
                                  style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12px; color:#555;">
                                <?php echo esc_html($meta['desc']); ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle;" id="<?php echo esc_attr($uid); ?>-badge-cell">
                            <?php echo $badge; ?>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle; overflow:hidden;">
                            <span id="<?php echo esc_attr($uid); ?>-schema-preview"
                                  style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:11px; color:#888; font-family:monospace;">
                                <?php echo esc_html($meta['schema']); ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px; vertical-align:middle;"></td>
                    </tr>

                    <!-- EXPANDED ROW -->
                    <tr id="<?php echo esc_attr($uid); ?>-expanded" style="display:none;">
                        <td colspan="5" style="padding:20px; background:#fafafa; border-top:2px solid #2271b1; border-bottom:2px solid #ddd;">
                            <?php bare_bones_seo_render_fields($post, true); ?>
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
    </div>

    <?php wp_nonce_field(BARE_BONES_SEO_NONCE_BULK_AJAX, 'bb_bulk_nonce_field'); ?>
    <?php
}
