<?php
/**
 * Tracking Scripts Tab — Bare Bones SEO
 */
if (!defined('ABSPATH')) exit;

function bare_bones_seo_render_tracking_screen() {
    if (isset($_POST['bb_save_tracking']) && check_admin_referer('bb_tracking_nonce') && current_user_can('manage_options')) {
        $scripts = isset($_POST['bb_scripts']) ? $_POST['bb_scripts'] : array();
        update_option(BARE_BONES_SEO_OPTION_TRACKING, bare_bones_seo_sanitize_tracking_scripts($scripts));
        echo '<div class="updated"><p>Tracking scripts updated.</p></div>';
    }
    $scripts = get_option(BARE_BONES_SEO_OPTION_TRACKING, array());
    ?>
    <div style="background:#fff; border:1px solid #c3c4c7; padding:20px; border-radius:4px; margin-top:20px;">
        <h2 style="margin:0 0 8px 0; font-size:16px; font-weight:600;">Global Tracking Scripts</h2>
        <p style="margin:0 0 20px 0; color:#646970; max-width:820px;">
            Paste analytics, verification, and pixel snippets exactly as the provider gives them to you &mdash; Google Analytics, Search Console, Meta Pixel and the like. These load on every page, so you only add them once instead of editing your theme. For a snippet that should run on one page only, use the Tracking Scripts panel in that page's editor.
        </p>
        <form method="post" action="">
            <?php wp_nonce_field('bb_tracking_nonce'); ?>
            <?php bare_bones_seo_render_tracking_table($scripts, 'bb_scripts', true); ?>
            <p class="submit"><input type="submit" name="bb_save_tracking" class="button button-primary" value="Save Tracking Scripts"></p>
        </form>
    </div>
    <?php
}

function bare_bones_seo_render_tracking_table($scripts, $input_name, $is_global = true) {
    ?>
    <div class="bbs-tracking-manager">
        <table class="wp-list-table widefat fixed striped">
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
                <?php if (!empty($scripts) && is_array($scripts)): foreach ($scripts as $index => $s): ?>
                    <?php bare_bones_seo_render_row($index, $s, $input_name, $is_global); ?>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <div style="margin-top:10px;">
            <button type="button" class="button bb-add-script-row" data-input-name="<?php echo esc_attr($input_name); ?>">+ Add Script</button>
        </div>
        <script type="text/template" id="tpl-<?php echo esc_attr($input_name); ?>">
            <?php bare_bones_seo_render_row('{{INDEX}}', array(), $input_name, $is_global); ?>
        </script>
    </div>
    <?php
}

/**
 * The page-level tracking panel. Shared by the post editor meta box and the
 * Page Meta screen's lazy loader so both render identical markup and names.
 *
 * The hidden marker tells the save handlers this panel was actually on screen.
 * Without it, an empty table is indistinguishable from a panel that never
 * rendered, and deleting every script would silently not save.
 */
function bare_bones_seo_render_page_tracking_panel($post_id) {
    $post_id = (int) $post_id;

    if (!current_user_can('unfiltered_html')) {
        echo '<p style="margin:0; color:#646970;">Adding tracking scripts to individual pages requires permission to post unfiltered HTML, which some hosts and security plugins disable. Global tracking scripts are unaffected &mdash; add them under Bare Bones SEO &rarr; Tracking.</p>';
        return;
    }

    $scripts = get_post_meta($post_id, BARE_BONES_SEO_META_TRACKING, true) ?: array();
    echo '<input type="hidden" name="bb_page_scripts_loaded_' . esc_attr($post_id) . '" value="1">';
    bare_bones_seo_render_tracking_table($scripts, 'bb_page_scripts_' . $post_id, false);
}

function bare_bones_seo_render_row($index, $data, $input_name, $is_global) {
    $label = $data['label'] ?? ''; $code = $data['code'] ?? ''; $loc = $data['loc'] ?? 'head';
    $status = $data['status'] ?? 'active'; $scope = $data['scope'] ?? 'all';
    $name = "{$input_name}[$index]";
    ?>
    <tr>
        <td><input type="text" name="<?php echo $name; ?>[label]" value="<?php echo esc_attr($label); ?>" class="widefat"></td>
        <td><textarea name="<?php echo $name; ?>[code]" rows="2" class="widefat code" style="font-size:11px;"><?php echo esc_textarea($code); ?></textarea></td>
        <td><select name="<?php echo $name; ?>[loc]"><option value="head" <?php selected($loc, 'head');?>>Head</option><option value="footer" <?php selected($loc, 'footer');?>>Footer</option></select></td>
        <td><select name="<?php echo $name; ?>[status]"><option value="active" <?php selected($status, 'active');?>>Active</option><option value="paused" <?php selected($status, 'paused');?>>Paused</option></select></td>
        <?php if ($is_global): ?><td><select name="<?php echo $name; ?>[scope]"><option value="all" <?php selected($scope, 'all');?>>Entire Site</option><option value="home" <?php selected($scope, 'home');?>>Home Only</option></select></td><?php endif; ?>
        <td><button type="button" class="bb-remove-row" style="color:#a00; border:none; background:none; cursor:pointer; font-size:20px;">&times;</button></td>
    </tr>
    <?php
}

/**
 * Code is stored raw (not run through wp_kses) because wp_kses corrupts inline
 * JS — it entity-encodes & and eats < in comparisons, breaking GA/GTM/Pixel
 * snippets. Access is gated by capability at the call sites instead: global
 * scripts are manage_options only, page scripts require unfiltered_html.
 */
function bare_bones_seo_sanitize_tracking_scripts($input) {
    if (!is_array($input)) return array();
    $input = wp_unslash($input);

    $locs   = array('head', 'footer');
    $stats  = array('active', 'paused');
    $scopes = array('all', 'home');

    $clean = array();
    foreach ($input as $row) {
        if (!is_array($row) || empty($row['code'])) continue;
        $clean[] = array(
            'label'  => sanitize_text_field($row['label'] ?? ''),
            'code'   => trim($row['code']),
            'loc'    => in_array($row['loc'] ?? '', $locs, true) ? $row['loc'] : 'head',
            'status' => in_array($row['status'] ?? '', $stats, true) ? $row['status'] : 'active',
            'scope'  => in_array($row['scope'] ?? '', $scopes, true) ? $row['scope'] : 'all',
        );
    }
    return $clean;
}
