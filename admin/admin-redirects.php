<?php
/**
 * "301 Redirects" tab.
 *
 * Two systems, one screen:
 *   - Automatic: WordPress's native _wp_old_slug rename-redirects (free, no work
 *     on our part; shown with 90-day hit counts).
 *   - Custom: a [ source path => target ] map for moved pages, legacy paths, and
 *     external URLs — applied by includes/redirect-engine.php, only on 404s.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'bb_handle_redirect_actions');

/**
 * Handle add/delete actions before any HTML output.
 */
function bb_handle_redirect_actions() {
    if (
        !isset($_GET['page']) || $_GET['page'] !== 'bare-bones-seo' ||
        !isset($_GET['tab']) || $_GET['tab'] !== 'redirects'
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $notices = get_transient('bb_redirect_notices');
    if (!is_array($notices)) {
        $notices = array();
    }

    // --- DELETE: native rename redirect ---
    if (
        isset($_GET['action']) && $_GET['action'] === 'delete_redirect' &&
        isset($_GET['post_id']) && isset($_GET['slug'])
    ) {
        check_admin_referer('bb_delete_redirect_' . $_GET['post_id'] . '_' . $_GET['slug']);

        $post_id  = intval($_GET['post_id']);
        $old_slug = sanitize_title(wp_unslash($_GET['slug']));

        delete_post_meta($post_id, '_wp_old_slug', $old_slug);
        delete_post_meta($post_id, '_wp_old_slug_hits_' . sanitize_key($old_slug));

        $notices[] = "Deleted redirect for <code>/" . esc_html($old_slug) . "/</code>.";
        if (count($notices) > 5) {
            array_shift($notices);
        }
        set_transient('bb_redirect_notices', $notices, 300);
        wp_redirect(remove_query_arg(array('action', 'post_id', 'slug', '_wpnonce')));
        exit;
    }

    // --- DELETE: custom redirect ---
    if (
        isset($_GET['action']) && $_GET['action'] === 'delete_custom' && isset($_GET['source'])
    ) {
        $source = trim(parse_url(wp_unslash($_GET['source']), PHP_URL_PATH), '/');
        check_admin_referer('bb_delete_custom_' . $source);

        $redirects = get_option('bare_bones_seo_redirects', array());
        if (is_array($redirects) && isset($redirects[$source])) {
            unset($redirects[$source]);
            update_option('bare_bones_seo_redirects', $redirects);
        }

        $notices[] = "Deleted custom redirect for <code>/" . esc_html($source) . "/</code>.";
        if (count($notices) > 5) {
            array_shift($notices);
        }
        set_transient('bb_redirect_notices', $notices, 300);
        wp_redirect(remove_query_arg(array('action', 'source', '_wpnonce')));
        exit;
    }

    // --- ADD: custom redirect ---
    if (
        isset($_POST['add_redirect']) &&
        isset($_POST['bb_add_redirect_nonce']) &&
        wp_verify_nonce($_POST['bb_add_redirect_nonce'], 'bb_add_redirect')
    ) {
        $source = trim(parse_url(wp_unslash($_POST['redirect_source']), PHP_URL_PATH), '/');
        $target = esc_url_raw(trim(wp_unslash($_POST['redirect_target'])));

        $target_host = parse_url($target, PHP_URL_HOST);
        $target_path = trim((string) parse_url($target, PHP_URL_PATH), '/');

        if ('' === $source || '' === $target) {
            $notices[] = "ERROR: Both a source path and a target are required.";
        } elseif (!$target_host && $source === $target_path) {
            $notices[] = "ERROR: The source and target are the same — that would loop.";
        } else {
            $redirects = get_option('bare_bones_seo_redirects', array());
            if (!is_array($redirects)) {
                $redirects = array();
            }
            $redirects[$source] = $target;
            update_option('bare_bones_seo_redirects', $redirects);

            $notices[] = "Redirect active: <code>/" . esc_html($source) . "/</code> now points to <code>" . esc_html($target) . "</code>.";
            if (count($notices) > 5) {
                array_shift($notices);
            }
            set_transient('bb_redirect_notices', $notices, 300);
            wp_redirect(remove_query_arg(array('bb_add_redirect_nonce', 'add_redirect')));
            exit;
        }
        set_transient('bb_redirect_notices', $notices, 300);
    }
}

/**
 * Render the admin screen.
 */
function render_bare_bones_redirects_tab() {
    global $wpdb;

    $notices = get_transient('bb_redirect_notices');
    ?>
    <div class="wrap bare-bones-seo-wrap" style="padding: 0; margin-top: 10px;">

        <?php if (is_array($notices) && !empty($notices)) : ?>
            <?php foreach ($notices as $notice) :
                $class = (strpos($notice, 'ERROR:') === 0) ? 'notice-error' : 'notice-success';
                $clean_notice = str_replace('ERROR: ', '', $notice);
                ?>
                <div class="notice <?php echo $class; ?> is-dismissible" style="margin: 0 0 15px 0;">
                    <p style="margin: 0; padding: 2px 0; font-size: 13px;"><?php echo $clean_notice; ?></p>
                </div>
            <?php endforeach; ?>
            <?php delete_transient('bb_redirect_notices'); ?>
        <?php endif; ?>

        <div class="bb-redirects-container" style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; max-width: 1200px;">

            <div style="border-bottom:1px solid #f0f0f0; padding-bottom:15px; margin-bottom:20px;">
                <h2 style="margin:0; font-size:16px; font-weight:600; color:#1d2327;">301 Redirect Manager</h2>
                <p style="margin:5px 0 0; font-size:13px; color:#646970; line-height: 1.5;">
                    WordPress redirects renamed pages automatically (listed under <strong>Automatic</strong> below). Add <strong>custom</strong> redirects for pages you've moved to a new folder, old/legacy paths, or external URLs. Custom redirects run only when a URL 404s, so there's no added cost to normal page loads&mdash;and no custom database tables.
                </p>
            </div>

            <!-- Add form -->
            <div style="max-width: 100%; margin-bottom: 25px; padding: 16px; border: 1px solid #dcdcde; background: #fafafa; border-radius: 4px;">
                <h3 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color:#1d2327;">Add Custom Redirect</h3>
                <form method="post" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <?php wp_nonce_field('bb_add_redirect', 'bb_add_redirect_nonce'); ?>

                    <div class="bb-field-group" style="margin: 0; flex: 1; min-width: 250px;">
                        <label for="redirect_source" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px; color:#1d2327;">Old Path (on this site)</label>
                        <input type="text" id="redirect_source" name="redirect_source" class="regular-text bb-input" placeholder="/blog/old-post/" required style="height: 32px; width: 100%;">
                    </div>

                    <div class="bb-field-group" style="margin: 0; flex: 1; min-width: 250px;">
                        <label for="redirect_target" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px; color:#1d2327;">Target URL (internal or external)</label>
                        <input type="text" id="redirect_target" name="redirect_target" class="regular-text bb-input" placeholder="/new-page/ or https://example.com/" required style="height: 32px; width: 100%;">
                    </div>

                    <div>
                        <input type="submit" name="add_redirect" id="add_redirect" class="button button-primary" value="Add Redirect" style="height: 32px; line-height: 30px;">
                    </div>
                </form>
            </div>

            <!-- Custom redirects -->
            <h3 style="margin: 0 0 4px; font-size: 13px; font-weight: 600; color:#1d2327;">Custom Redirects</h3>
            <p style="margin: 0 0 10px; font-size: 12px; color: #646970; font-style: italic;">Moved pages, legacy paths, and external URLs.</p>
            <table class="wp-list-table widefat striped table-view-list" style="border: 1px solid #c3c4c7; box-shadow: none; margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 45%; padding: 10px 12px; font-weight: 600; color:#1d2327;">Old Path</th>
                        <th scope="col" style="width: 40%; padding: 10px 12px; font-weight: 600; color:#1d2327;">Target</th>
                        <th scope="col" style="width: 15%; padding: 10px 12px; text-align: right; padding-right: 20px; font-weight: 600; color:#1d2327;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $custom = get_option('bare_bones_seo_redirects', array());
                    if (!is_array($custom) || empty($custom)) :
                        ?>
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: #646970;">No custom redirects yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ($custom as $src => $tgt) :
                            $delete_url = wp_nonce_url(
                                add_query_arg(array('action' => 'delete_custom', 'source' => $src)),
                                'bb_delete_custom_' . $src
                            );
                            ?>
                            <tr>
                                <td style="padding: 10px 12px;">
                                    <code style="background: none; padding: 0;">/<?php echo esc_html($src); ?>/</code>
                                </td>
                                <td style="padding: 10px 12px; word-wrap: break-word; color: #2c3338;">
                                    <?php echo esc_html($tgt); ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; padding-right: 20px;">
                                    <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" style="color: #b32d2e; text-decoration: none; font-size: 13px;" onclick="return confirm('Delete this redirect?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Automatic (native rename) redirects -->
            <h3 style="margin: 0 0 4px; font-size: 13px; font-weight: 600; color:#1d2327;">Automatic Redirects</h3>
            <p style="margin: 0 0 10px; font-size: 12px; color: #646970; font-style: italic;">Created automatically when you rename a page, sorted by 90-day hit volume.</p>
            <table class="wp-list-table widefat striped table-view-list" style="border: 1px solid #c3c4c7; box-shadow: none;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 60%; padding: 10px 12px; font-weight: 600; color:#1d2327;">Target URL / Original Path</th>
                        <th scope="col" style="width: 20%; padding: 10px 12px; text-align: right; font-weight: 600; color:#1d2327;">90-Day Hits</th>
                        <th scope="col" style="width: 20%; padding: 10px 12px; text-align: right; padding-right: 20px; font-weight: 600; color:#1d2327;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // --- RETRIEVE & AGGREGATE NATIVE REDIRECTS ---
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

                    uasort($redirect_groups, function($a, $b) {
                        return $b['total_hits'] - $a['total_hits'];
                    });
                    ?>

                    <?php if (empty($redirect_groups)) : ?>
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #646970;">No automatic redirects yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($redirect_groups as $group) : ?>
                            <tr class="target-row-parent" style="background-color: #f6f7f7;">
                                <td style="padding: 12px; font-weight: 600; font-size: 14px;" colspan="3">
                                    <span class="dashicons dashicons-admin-links" style="color: #2271b1; vertical-align: text-bottom; margin-right: 6px; font-size: 18px; width: 18px; height: 18px;"></span>
                                    <a href="<?php echo esc_url($group['target_url']); ?>" target="_blank" style="text-decoration: none; color: #2271b1;"><?php echo esc_html($group['target_url']); ?></a>
                                </td>
                            </tr>

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
                                        <a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" style="color: #b32d2e; text-decoration: none; font-size: 13px;" onclick="return confirm('Are you sure you want to delete this redirect?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
