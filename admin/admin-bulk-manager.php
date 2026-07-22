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

    // Field names carry the post ID suffix because bare_bones_seo_render_fields()
    // renders many rows on one screen and the names have to stay unique.
    $title  = 'bb_seo_title_' . $post_id;
    $desc   = 'bb_seo_desc_' . $post_id;
    $schema = 'bb_seo_schema_' . $post_id;
    $index  = 'bb_seo_should_index_' . $post_id;

    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => isset($_POST[$title])  ? sanitize_text_field($_POST[$title]) : '',
        'desc'         => isset($_POST[$desc])   ? sanitize_text_field($_POST[$desc])  : '',
        'schema'       => isset($_POST[$schema]) ? $_POST[$schema]                     : '',
        'should_index' => isset($_POST[$index])  ? sanitize_key($_POST[$index])        : 'yes',
    ));

    $saved = bare_bones_seo_get_page_meta($post_id);

    wp_send_json_success(array(
        'desc'     => $saved['desc'],
        'schema'   => $saved['schema'],
        'noindexed' => bare_bones_seo_state_removes_from_sitemap($saved['index']),
    ));
}

/**
 * Render the Bulk Manager screen.
 *
 * @since 1.0.0
 */
function bare_bones_seo_render_bulk_manager_screen() {
    $per_page = 50;
    $paged    = isset($_GET['bb_paged']) ? max(1, intval($_GET['bb_paged'])) : 1;

    $query = new WP_Query(array(
        'post_type'      => array('page', 'post'),
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => false,
    ));
    ?>
    <!-- CONTAINER BOX -->
    <div style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; margin-top:20px;">
        
        <!-- Box Page Header -->
        <div style="border-bottom:1px solid #f0f0f0; padding-bottom:15px; margin-bottom:20px;">
            <h2 style="margin:0; font-size:16px; font-weight:600; color:#1d2327;">Page Meta</h2>
            <p style="margin:5px 0 0; font-size:13px; color:#646970; line-height: 1.5;">
                Titles, descriptions, and schema for every published page and post. Click a row to edit it here, or find the same fields at the bottom of any individual edit screen.
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
                        class="bb-row-toggle" data-post-id="<?php echo esc_attr($post->ID); ?>">
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
                                        class="button bb-row-toggle" data-post-id="<?php echo esc_attr($post->ID); ?>"
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

        <?php if ($query->max_num_pages > 1) :
            $base = add_query_arg('bb_paged', '%#%');
            ?>
            <div class="tablenav bottom" style="margin-top:12px;">
                <div class="tablenav-pages" style="float:none; text-align:right;">
                    <span class="displaying-num"><?php echo esc_html(number_format_i18n($query->found_posts)); ?> items</span>
                    <?php
                    echo paginate_links(array(
                        'base'      => $base,
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $query->max_num_pages,
                        'current'   => $paged,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
