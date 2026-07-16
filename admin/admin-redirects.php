<?php
/**
 * Renders the "301 Redirects" tab for Bare Bones SEO.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook processing logic early so redirects can happen before headers are sent
add_action('admin_init', 'bb_handle_redirect_actions');

/**
 * Handles Add and Delete redirect actions before HTML output begins.
 */
function bb_handle_redirect_actions() {
    // Only run this processing on our specific redirects tab
    if (
        !isset($_GET['page']) || $_GET['page'] !== 'bare-bones-seo' || 
        !isset($_GET['tab']) || $_GET['tab'] !== 'redirects'
    ) {
        return;
    }

    global $wpdb;

    $notices = get_transient('bb_redirect_notices');
    if (!is_array($notices)) {
        $notices = array();
    }

    // --- HANDLE DELETE ACTION ---
    if (
        isset($_GET['action']) && $_GET['action'] === 'delete_redirect' && 
        isset($_GET['post_id']) && isset($_GET['slug'])
    ) {
        check_admin_referer('bb_delete_redirect_' . $_GET['post_id'] . '_' . $_GET['slug']);
        
        $post_id  = intval($_GET['post_id']);
        $old_slug = sanitize_title($_GET['slug']);

        // Remove redirect and its tracking key
        delete_post_meta($post_id, '_wp_old_slug', $old_slug);
        delete_post_meta($post_id, '_wp_old_slug_hits_' . sanitize_key($old_slug));

        $notices[] = "Deleted redirect for <code>/" . esc_html($old_slug) . "/</code>.";
        if (count($notices) > 5) {
            array_shift($notices);
        }
        set_transient('bb_redirect_notices', $notices, 300);

        // Safe redirect: Headers have not been sent yet
        wp_redirect(remove_query_arg(array('action', 'post_id', 'slug', '_wpnonce')));
        exit;
    }

    // --- HANDLE ADD ACTION ---
    if (
        isset($_POST['add_redirect']) && 
        isset($_POST['bb_add_redirect_nonce']) && 
        wp_verify_nonce($_POST['bb_add_redirect_nonce'], 'bb_add_redirect')
    ) {
        $source = trim(parse_url($_POST['redirect_source'], PHP_URL_PATH), '/');
        $target = trim($_POST['redirect_target']);

        if (!empty($source) && !empty($target)) {
            $source_slug = sanitize_title($source);
            
            // Resolve target path to an actual Post ID
            $target_path = parse_url($target, PHP_URL_PATH);
            $target_post_id = url_to_postid($target_path);

            if ($target_post_id) {
                // Save directly into WordPress's native system
                add_post_meta($target_post_id, '_wp_old_slug', $source_slug);
                
                $notices[] = "Redirect active: <code>/" . esc_html($source_slug) . "/</code> now points to <code>" . esc_html(wp_make_link_relative(get_permalink($target_post_id))) . "</code>.";
                if (count($notices) > 5) {
                    array_shift($notices);
                }
                set_transient('bb_redirect_notices', $notices, 300);

                // Redirect to prevent duplicate form submissions on page refresh
                wp_redirect(remove_query_arg(array('bb_add_redirect_nonce', 'add_redirect')));
                exit;
            } else {
                $notices[] = "ERROR: Could not resolve target URL to an existing page on this site.";
                set_transient('bb_redirect_notices', $notices, 300);
            }
        }
    }
}

/**
 * Renders the HTML Admin interface view.
 */
function render_bare_bones_redirects_tab() {
    global $wpdb;

    // Retrieve pending notices
    $notices = get_transient('bb_redirect_notices');
    ?>
    <div class="wrap bare-bones-seo-wrap" style="padding: 0; margin-top: 10px;">

        <!-- Display Session Notice Queue -->
        <?php if (is_array($notices) && !empty($notices)) : ?>
            <?php foreach ($notices as $notice) : 
                $class = (strpos($notice, 'ERROR:') === 0) ? 'notice-error' : 'notice-success';
                $clean_notice = str_replace('ERROR: ', '', $notice);
                ?>
                <div class="notice <?php echo $class; ?> is-dismissible" style="margin: 0 0 15px 0;">
                    <p style="margin: 0; padding: 2px 0; font-size: 13px;"><?php echo $clean_notice; ?></p>
                </div>
            <?php endforeach; ?>
            <?php delete_transient('bb_redirect_notices'); // Clear queue after rendering ?>
        <?php endif; ?>

        <div class="bb-redirects-container" style="max-width: 1200px;">
            
            <!-- Manual Add Form -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 16px; border: 1px solid #c3c4c7; box-shadow: none; background: #fff; border-radius: 4px;">
                <h2 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600;">Add New 301 Redirect</h2>
                <form method="post" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <?php wp_nonce_field('bb_add_redirect', 'bb_add_redirect_nonce'); ?>
                    
                    <div class="bb-field-group" style="margin: 0; flex: 1; min-width: 250px;">
                        <label for="redirect_source" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px;">Original Path (Source)</label>
                        <input type="text" id="redirect_source" name="redirect_source" class="regular-text bb-input" placeholder="/old-path/" required style="height: 32px; width: 100%;">
                    </div>
                    
                    <div class="bb-field-group" style="margin: 0; flex: 1; min-width: 250px;">
                        <label for="redirect_target" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px;">Destination URL (Target Page)</label>
                        <input type="text" id="redirect_target" name="redirect_target" class="regular-text bb-input" placeholder="/new-destination/" required style="height: 32px; width: 100%;">
                    </div>
                    
                    <div>
                        <input type="submit" name="add_redirect" id="add_redirect" class="button button-primary" value="Add Redirect" style="height: 32px; line-height: 30px;">
                    </div>
                </form>
            </div>

            <!-- Header sorting description -->
            <div class="tablenav top" style="margin-bottom: 10px; height: auto; padding: 0;">
                <div class="alignleft actions">
                    <span style="color: #646970; font-style: italic; font-size: 12px;">Existing native WordPress database redirects, sorted by 90-day hit volume.</span>
                </div>
            </div>

            <!-- Cleaned-up Redirects Table -->
            <table class="wp-list-table widefat striped table-view-list" style="border: 1px solid #c3c4c7; box-shadow: none;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 60%; padding: 10px 12px; font-weight: 600;">Target URL / Original Path</th>
                        <th scope="col" style="width: 20%; padding: 10px 12px; text-align: right; font-weight: 600;">90-Day Hits</th>
                        <th scope="col" style="width: 20%; padding: 10px 12px; text-align: right; padding-right: 20px; font-weight: 600;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // --- RETRIEVE & AGGREGATE REDIRECT DATA ---
                    $db_results = $wpdb->get_results("
                        SELECT post_id, meta_value AS old_slug 
                        FROM $wpdb->postmeta 
                        WHERE meta_key = '_wp_old_slug'
                    ");

                    $redirect_groups = array();

                    foreach ($db_results as $row) {
                        $post_id  = $row->post_id;
                        $old_slug = $row->old_slug;
                        
                        $target_url = wp_make_link_relative(get_permalink($post_id));
                        if (!$target_url || is_wp_error($target_url)) {
                            continue; // Skip orphan rows
                        }

                        // Get 90-day hit array and calculate total
                        $meta_key   = '_wp_old_slug_hits_' . sanitize_key($old_slug);
                        $hits_data  = get_post_meta($post_id, $meta_key, true);
                        $total_hits = is_array($hits_data) ? array_sum($hits_data) : 0;

                        if (!isset($redirect_groups[$target_url])) {
                            $redirect_groups[$target_url] = array(
                                'target_url' => $target_url,
                                'total_hits' => 0,
                                'children'   => array()
                            );
                        }

                        $redirect_groups[$target_url]['total_hits'] += $total_hits;
                        $redirect_groups[$target_url]['children'][] = array(
                            'old_url' => '/' . $old_slug . '/',
                            'slug'    => $old_slug,
                            'post_id' => $post_id,
                            'hits'    => $total_hits
                        );
                    }

                    // Sort groups on the backend by total hit counts (highest first)
                    uasort($redirect_groups, function($a, $b) {
                        return $b['total_hits'] - $a['total_hits'];
                    });
                    ?>

                    <?php if (empty($redirect_groups)) : ?>
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #646970;">No redirects found in your database yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($redirect_groups as $group) : ?>
                            <!-- Group Header Row (Target Page) -->
                            <tr class="target-row-parent" style="background-color: #f6f7f7;">
                                <td style="padding: 12px; font-weight: 600; font-size: 14px;" colspan="3">
                                    <span class="dashicons dashicons-admin-links" style="color: #2271b1; vertical-align: text-bottom; margin-right: 6px; font-size: 18px; width: 18px; height: 18px;"></span>
                                    <a href="<?php echo esc_url($group['target_url']); ?>" target="_blank" style="text-decoration: none; color: #2271b1;"><?php echo esc_html($group['target_url']); ?></a>
                                </td>
                            </tr>
                            
                            <!-- Child Paths pointing to this Target -->
                            <?php foreach ($group['children'] as $child) : ?>
                                <tr class="redirect-child-row">
                                    <td style="padding: 10px 10px 10px 35px; color: #2c3338;">
                                        <span class="dashicons dashicons-arrow-right-alt2" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; color: #a7aaad; margin-right: 4px;"></span>
                                        <code style="background: none; padding: 0;"><?php echo esc_html($child['old_url']); ?></code>
                                    </td>
                                    <td style="padding: 10px; text-align: right; color: #646970; font-size: 13px;">
                                        <?php echo number_format_i18n($child['hits']); ?>
                                    </td>
                                    <td style="padding: 10px; text-align: right; padding-right: 20px;">
                                        <?php 
                                        $delete_url = wp_nonce_url(
                                            add_query_arg(array(
                                                'action'  => 'delete_redirect',
                                                'post_id' => $child['post_id'],
                                                'slug'    => $child['slug']
                                            )),
                                            'bb_delete_redirect_' . $child['post_id'] . '_' . $child['slug']
                                        );
                                        ?>
                                        <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" style="color: #b32d2e; text-decoration: none; font-size: 13px;" onclick="return confirm('Are you sure you want to delete this redirect?');">Delete Redirect</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom" style="margin-top: 10px;">
                <div class="alignleft" style="color: #646970; font-size: 12px;">
                    All redirects are stored natively within post metadata. No custom tables or external router queries are loaded.
                </div>
            </div>
        </div>
    </div>
    <?php
}
