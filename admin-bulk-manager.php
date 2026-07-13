<?php
/**
 * Bulk Page Manager
 * 
 * Provides a spreadsheet-style interface to quickly view and edit
 * SEO metadata (title, index status) for all pages at once.
 * Updates via AJAX without page refresh.
 * 
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler: Process bulk save request
 * 
 * Called via JavaScript when user clicks "Quick Save" button.
 * Updates the SEO metadata for a single post via AJAX.
 * 
 * Security layers:
 * 1. AJAX nonce verification (CSRF protection)
 * 2. Capability check (only admins can modify)
 * 3. Input validation (post_id must exist and be positive)
 * 4. Data sanitization (title and indexed value)
 * 
 * Error responses include descriptive messages for debugging.
 * 
 * @since 1.0.2
 * @return void (via wp_send_json_success or wp_send_json_error)
 */
add_action('wp_ajax_' . BARE_BONES_SEO_AJAX_ACTION, 'bare_bones_seo_process_bulk_ajax_save');
function bare_bones_seo_process_bulk_ajax_save() {
    // SECURITY: Verify AJAX nonce
    check_ajax_referer(BARE_BONES_SEO_NONCE_BULK_AJAX, 'security');

    // SECURITY: Verify user capability (admin only)
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    // VALIDATION: Check that all required POST fields exist
    if (!isset($_POST['post_id']) || !isset($_POST['seo_title']) || !isset($_POST['should_index'])) {
        wp_send_json_error('Missing required fields');
        return;
    }

    // SANITIZATION: Convert and sanitize input values
    $post_id = intval($_POST['post_id']);
    $title   = sanitize_text_field($_POST['seo_title']);
    $indexed = sanitize_text_field($_POST['should_index']);

    // VALIDATION: Ensure post_id is valid
    if ($post_id <= 0) {
        wp_send_json_error('Invalid post ID');
        return;
    }

    // VALIDATION: Ensure the post exists
    if (!get_post($post_id)) {
        wp_send_json_error('Post not found');
        return;
    }

    // VALIDATION: Ensure indexed value is boolean-like
    if ($indexed !== 'true' && $indexed !== 'false') {
        wp_send_json_error('Invalid indexed value');
        return;
    }

    // Save the metadata (sanitization handled by helper function)
    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => $title,
        'should_index' => ($indexed === 'true') ? 'yes' : 'no',
    ));

    // Return success response
    wp_send_json_success(array(
        'message' => 'Post metadata updated successfully',
    ));
}

/**
 * Render the Bulk Page Manager screen
 * 
 * Displays all published pages and posts in a table with inline editing.
 * Users can edit title and indexing status, then click "Quick Save" to
 * update via AJAX without leaving the page.
 * 
 * Large sites (5k+ posts): May be slow. Consider adding pagination
 * if this becomes an issue.
 * 
 * @since 1.0.0
 * @return void
 */
function bare_bones_seo_render_bulk_manager_screen() {
    // Query all published pages and posts
    // PERFORMANCE WARNING: Large sites (50k+ posts) will load slowly
    $pages_query = new WP_Query(array(
        'post_type'      => array('page', 'post'),
        'posts_per_page' => -1, // Load all at once (see warning above)
        'post_status'    => 'publish',
    ));
    ?>
    <div class="wrap">
        <!-- Header with title and docs link -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 15px;">
            <h1 style="margin: 0;">☠️ Bare Bones SEO — Bulk Page Manager</h1>
            <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 5px;">
                <span class="dashicons dashicons-external" style="font-size: 16px; width:16px; height:16px; margin-top:2px;"></span> 
                Plugin Documentation
            </a>
        </div>

        <!-- Tab navigation -->
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=bare-bones-seo" class="nav-tab">Global Indexing Map</a>
            <a href="?page=bare-bones-seo-bulk" class="nav-tab nav-tab-active">Bulk Page Manager</a>
        </h2>

        <!-- Bulk editing table -->
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
                <?php if ($pages_query->have_posts()) : 
                    while ($pages_query->have_posts()) : 
                        $pages_query->the_post();
                        global $post;
                        $meta = bare_bones_seo_get_page_meta($post->ID);
                ?>
                    <tr id="bb-bulk-row-<?php echo esc_attr($post->ID); ?>">
                        <!-- Post Title Column -->
                        <td style="vertical-align: middle; font-weight: 600;">
                            <?php the_title(); ?>
                            <span style="display:block; font-weight:normal; font-size:10px; color:#666; text-transform:uppercase; margin-top:2px;">
                                Type: <?php echo esc_html(get_post_type()); ?>
                            </span>
                        </td>

                        <!-- SEO Title Input Column -->
                        <td style="vertical-align: middle;">
                            <input type="text" 
                                   id="bb-bulk-title-<?php echo esc_attr($post->ID); ?>" 
                                   class="widefat" 
                                   value="<?php echo esc_attr($meta['title']); ?>" 
                                   style="padding: 6px;" 
                                   placeholder="Using native title metadata rules...">
                        </td>

                        <!-- Indexed Status Column -->
                        <td style="text-align: center; vertical-align: middle;">
                            <label>
                                <input type="checkbox" 
                                       id="bb-bulk-index-<?php echo esc_attr($post->ID); ?>" 
                                       <?php checked($meta['should_index']); ?>>
                                <span class="bb-status-badge-<?php echo esc_attr($post->ID); ?>" 
                                      style="font-weight: 600; font-size:11px; margin-left:5px; color: <?php echo $meta['should_index'] ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo $meta['should_index'] ? 'INDEXED' : '🚨 HIDDEN'; ?>
                                </span>
                            </label>
                        </td>

                        <!-- Actions Column -->
                        <td style="text-align: center; vertical-align: middle;">
                            <button type="button" 
                                    class="button button-secondary bb-bulk-save-trigger" 
                                    data-id="<?php echo esc_attr($post->ID); ?>">
                                Quick Save
                            </button>
                            <a href="<?php echo esc_url(the_permalink()); ?>" 
                               target="_blank" 
                               class="button button-link" 
                               style="margin-left: 5px;">
                                View
                            </a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                    wp_reset_postdata(); 
                else: 
                ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                            No published pages or posts found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    // Output nonce for AJAX verification
    wp_nonce_field(BARE_BONES_SEO_NONCE_BULK_AJAX, 'bb_bulk_nonce_field');
    // JavaScript is now in assets/admin-script.js
    // See that file for the AJAX save logic
}
