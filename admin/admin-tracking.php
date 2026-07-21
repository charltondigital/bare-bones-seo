<?php
/**
 * Tracking Scripts Tab — Bare Bones SEO
 */

if (!defined('ABSPATH')) exit;

function bare_bones_seo_render_tracking_screen() {
    // Handle Save
    if (isset($_POST['bb_save_tracking']) && check_admin_referer('bb_tracking_nonce')) {
        $scripts = isset($_POST['bb_scripts']) ? $_POST['bb_scripts'] : array();
        update_option(BARE_BONES_SEO_OPTION_TRACKING, bare_bones_seo_sanitize_tracking_scripts($scripts));
        echo '<div class="updated"><p>Tracking scripts updated.</p></div>';
    }

    $scripts = get_option(BARE_BONES_SEO_OPTION_TRACKING, array());
    ?>
    <div style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; margin-top:20px;">
        <div style="border-bottom:1px solid #f0f0f0; padding-bottom:15px; margin-bottom:20px;">
            <h2 style="margin:0; font-size:16px; font-weight:600;">Global Tracking Scripts</h2>
            <p style="margin:5px 0 0; font-size:13px; color:#646970;">Paste your GA4, Meta Pixel, or GSC verification codes below. Only &lt;script&gt;, &lt;noscript&gt;, and &lt;meta&gt; tags are allowed.</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('bb_tracking_nonce'); ?>
            <?php bare_bones_seo_render_tracking_table($scripts, 'bb_scripts', true); ?>
            <p class="submit"><input type="submit" name="bb_save_tracking" class="button button-primary" value="Save Tracking Scripts"></p>
        </form>
    </div>
    <?php
}

/**
 * Reusable table for both Global and Page-level scripts
 */
function bare_bones_seo_render_tracking_table($scripts, $input_name, $is_global = true) {
    ?>
    <table class="wp-list-table widefat fixed striped bb-tracking-table">
        <thead>
            <tr>
                <th style="width:20%;">Label</th>
                <th>Code Snippet</th>
                <th style="width:100px;">Location</th>
                <th style="width:90px;">Status</th>
                <?php if ($is_global): ?><th style="width:110px;">Scope</th><?php endif; ?>
                <th style="width:40px;"></th>
            </tr>
        </thead>
        <tbody class="bb-tracking-rows">
            <?php if (!empty($scripts)): foreach ($scripts as $index => $s): ?>
                <?php bare_bones_seo_render_row($index, $s, $input_name, $is_global); ?>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div style="margin-top:10px;">
        <button type="button" class="button bb-add-script-row" data-input-name="<?php echo $input_name; ?>" data-global="<?php echo $is_global ? '1' : '0'; ?>">+ Add Script</button>
    </div>

    <!-- Hidden Template for JS -->
    <script type="text/template" id="tpl-<?php echo $input_name; ?>">
        <?php bare_bones_seo_render_row('{{INDEX}}', array(), $input_name, $is_global); ?>
    </script>
    <?php
}

function bare_bones_seo_render_row($index, $data, $input_name, $is_global) {
    $label = $data['label'] ?? ''; $code = $data['code'] ?? ''; $loc = $data['loc'] ?? 'head';
    $status = $data['status'] ?? 'active'; $scope = $data['scope'] ?? 'all';
    $name = "{$input_name}[$index]";
    ?>
    <tr>
        <td><input type="text" name="<?php echo $name; ?>[label]" value="<?php echo esc_attr($label); ?>" class="widefat" placeholder="e.g. Google Analytics"></td>
        <td><textarea name="<?php echo $name; ?>[code]" rows="2" class="widefat code" style="font-size:11px;"><?php echo esc_textarea($code); ?></textarea></td>
        <td>
            <select name="<?php echo $name; ?>[loc]">
                <option value="head" <?php selected($loc, 'head');?>>Head</option>
                <option value="footer" <?php selected($loc, 'footer');?>>Footer</option>
            </select>
        </td>
        <td>
            <select name="<?php echo $name; ?>[status]">
                <option value="active" <?php selected($status, 'active');?>>Active</option>
                <option value="paused" <?php selected($status, 'paused');?>>Paused</option>
            </select>
        </td>
        <?php if ($is_global): ?>
        <td>
            <select name="<?php echo $name; ?>[scope]">
                <option value="all" <?php selected($scope, 'all');?>>Site-wide</option>
                <option value="home" <?php selected($scope, 'home');?>>Home Only</option>
            </select>
        </td>
        <?php endif; ?>
        <td><button type="button" class="bb-remove-row" style="background:none; border:none; color:#a00; cursor:pointer; font-size:20px;">&times;</button></td>
    </tr>
    <?php
}

function bare_bones_seo_sanitize_tracking_scripts($input) {
    if (!is_array($input)) return array();
    $clean = array();
    $allowed = array(
        'script'   => array('src' => true, 'type' => true, 'async' => true, 'defer' => true, 'id' => true, 'crossorigin' => true),
        'noscript' => array(),
        'meta'     => array('name' => true, 'content' => true, 'charset' => true, 'property' => true, 'http-equiv' => true)
    );
    foreach ($input as $row) {
        if (empty($row['code'])) continue;
        $clean[] = array(
            'label'  => sanitize_text_field($row['label']),
            'code'   => wp_kses($row['code'], $allowed),
            'loc'    => in_array($row['loc'], array('head', 'footer')) ? $row['loc'] : 'head',
            'status' => in_array($row['status'], array('active', 'paused')) ? $row['status'] : 'active',
            'scope'  => isset($row['scope']) ? sanitize_key($row['scope']) : 'all'
        );
    }
    return $clean;
}
