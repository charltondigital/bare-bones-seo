<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_bb_seo_bulk_save', 'bare_bones_seo_process_bulk_ajax_save');
function bare_bones_seo_process_bulk_ajax_save() {
    check_ajax_referer('bb_bulk_manager_nonce', 'security');
    if (!current_user_can('manage_options')) { wp_send_json_error(); }

    // Validate all required POST fields exist
    if (!isset($_POST['post_id']) || !isset($_POST['seo_title']) || !isset($_POST['should_index'])) {
        wp_send_json_error('Missing required fields');
        return;
    }

    $post_id = intval($_POST['post_id']);
    $title   = sanitize_text_field($_POST['seo_title']);
    $indexed = sanitize_text_field($_POST['should_index']);

    // Extra validation
    if ($post_id === 0) {
        wp_send_json_error('Invalid post ID');
        return;
    }

    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => $title,
        'should_index' => ($indexed === 'true') ? 'yes' : 'no'
    ));

    wp_send_json_success();
}

function bare_bones_seo_render_bulk_manager_screen() {
    $pages_query = new WP_Query(array(
        'post_type'      => array('page', 'post'),
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ));
    ?>
    <div class="wrap">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 15px;">
            <h1 style="margin: 0;">☠️ Bare Bones SEO — Bulk Page Manager</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="dashicons dashicons-external" style="font-size: 16px; width:16px; height:16px; margin-top:2px;"></span> 
                Plugin Documentation
            </a>
        </div>

        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=bare-bones-seo" class="nav-tab">Global Indexing Map</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab nav-tab-active">Bulk Page Manager</a>
        </h2>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="font-weight: 600; width: 25%;">PAGE TITLE / NATIVE TYPE</th>
                    <th style="font-weight: 600; width: 45%;">SEO TITLE TAG (CUSTOM OVERRIDE)</th>
                    <th style="font-weight: 600; text-align: center; width: 15%;">INDEXED STATUS</th>
                    <th style="font-weight: 600; text-align: center; width: 15%;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pages_query->have_posts()) : while ($pages_query->have_posts()) : $pages_query->the_post(); 
                    global $post;
                    $meta = bare_bones_seo_get_page_meta($post->ID);
                ?>
                    <tr id="bb-bulk-row-<?php echo $post->ID; ?>">
                        <td style="vertical-align: middle; font-weight: 600;">
                            <?php the_title(); ?>
                            <span style="display:block; font-weight:normal; font-size:10px; color:#666; text-transform:uppercase; margin-top:2px;">
                                Type: <?php echo esc_html(get_post_type()); ?>
                            </span>
                        </td>
                        <td style="vertical-align: middle;">
                            <input type="text" id="bb-bulk-title-<?php echo $post->ID; ?>" class="widefat" value="<?php echo esc_attr($meta['title']); ?>" style="padding: 6px;" placeholder="Using native title metadata rules...">
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <label>
                                <input type="checkbox" id="bb-bulk-index-<?php echo $post->ID; ?>" <?php checked($meta['should_index']); ?>>
                                <span class="bb-status-badge-<?php echo $post->ID; ?>" style="font-weight: 600; font-size:11px; margin-left:5px; color: <?php echo $meta['should_index'] ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo $meta['should_index'] ? 'INDEXED' : '🚨 HIDDEN'; ?>
                                </span>
                            </label>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <button type="button" class="button button-secondary bb-bulk-save-trigger" data-id="<?php echo $post->ID; ?>">Quick Save</button>
                            <a href="<?php the_permalink(); ?>" target="_blank" class="button button-link" style="margin-left: 5px;">View</a>
                        </td>
                    </tr>
                <?php endwhile; wp_reset_postdata(); endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.bb-bulk-save-trigger').on('click', function() {
                var btn = $(this);
                var post_id = btn.data('id');
                var seoTitle = $('#bb-bulk-title-' + post_id).val();
                var shouldIndex = $('#bb-bulk-index-' + post_id).is(':checked');
                
                btn.text('Saving...').attr('disabled', true);

                $.post(ajaxurl, {
                    action: 'bb_seo_bulk_save',
                    security: '<?php echo wp_create_nonce("bb_bulk_manager_nonce"); ?>',
                    post_id: post_id,
                    seo_title: seoTitle,
                    should_index: shouldIndex
                }, function(response) {
                    btn.text('Quick Save').removeAttr('disabled');
                    if(response.success) {
                        var label = $('.bb-status-badge-' + post_id);
                        if(shouldIndex) {
                            label.text('INDEXED').css('color', '#46b450');
                        } else {
                            label.text('🚨 HIDDEN').css('color', '#dc3232');
                        }
                        btn.removeClass('button-secondary').addClass('button-primary').text('Saved ✓');
                        setTimeout(function() {
                            btn.removeClass('button-primary').addClass('button-secondary').text('Quick Save');
                        }, 2000);
                    }
                });
            });
        });
    </script>
    <?php
}
