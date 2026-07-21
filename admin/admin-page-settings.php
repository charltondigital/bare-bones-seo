<?php
/**
 * Page-Level SEO Settings UI — Bare Bones SEO
 */

if (!defined('ABSPATH')) { exit; }

add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    $post_types = get_post_types(array('public' => true));
    foreach ($post_types as $type) {
        add_meta_box('bare-bones-seo-box', 'Page-Level SEO Settings — Bare Bones SEO', 'bare_bones_seo_render_meta_box', $type, 'normal', 'high');
    }
}

function bare_bones_seo_render_meta_box($post) {
    wp_nonce_field(BARE_BONES_SEO_NONCE_PAGE, 'bare_bones_seo_nonce');
    bare_bones_seo_render_fields($post);
}

function bare_bones_seo_render_fields($post, $in_bulk = false) {
    $meta = bare_bones_seo_get_page_meta($post->ID);
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
    $uid = 'bb-' . $post->ID;
    $site_state = bare_bones_seo_get_site_state($post->post_type);
    $effective_state = bare_bones_seo_get_effective_post_state($post->ID);
    $preview_title = $meta['title'] ? $meta['title'] : $post->post_title;
    ?>
    <div style="display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:24px; padding:10px 0;">
        <div>
            <!-- Section 1: Snippet Builder -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button" class="bb-section-toggle" aria-expanded="true" data-target="<?php echo $uid; ?>-snippet" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Snippet Builder <span class="bb-toggle-icon">−</span></button>
                <div id="<?php echo $uid; ?>-snippet" style="padding:14px; border-top:1px solid #ddd;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">SEO Title</label>
                    <input type="text" name="bb_seo_title_<?php echo $post->ID; ?>" class="bb-title-input" data-uid="<?php echo $uid; ?>" value="<?php echo esc_attr($meta['title']); ?>" style="width:100%; margin-bottom:12px;">
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Meta Description</label>
                    <textarea name="bb_seo_desc_<?php echo $post->ID; ?>" class="bb-desc-input" data-uid="<?php echo $uid; ?>" rows="3" style="width:100%;"><?php echo esc_textarea($meta['desc']); ?></textarea>
                    <button type="button" class="button bb-trigger-preview" data-uid="<?php echo $uid; ?>" style="margin-top:10px;">Generate Preview</button>
                </div>
            </div>

            <!-- Section 2: Indexing -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button" class="bb-section-toggle" aria-expanded="false" data-target="<?php echo $uid; ?>-indexing" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Indexing <span class="bb-toggle-icon">+</span></button>
                <div id="<?php echo $uid; ?>-indexing" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <?php 
                    $opts = array('yes' => 'Yes', 'no' => 'No', 'complicated_sitemap' => 'Remove from Sitemap Only');
                    foreach ($opts as $v => $l) : 
                        if (bare_bones_seo_more_restrictive($site_state, $v) === $v) : ?>
                            <label style="display:block; margin-bottom:8px;"><input type="radio" name="bb_seo_should_index_<?php echo $post->ID; ?>" value="<?php echo $v; ?>" <?php checked($effective_state, $v); ?>> <?php echo $l; ?></label>
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <!-- Section 3: Schema Markup -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button" class="bb-section-toggle" aria-expanded="false" data-target="<?php echo $uid; ?>-schema" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Schema Markup <span class="bb-toggle-icon">+</span></button>
                <div id="<?php echo $uid; ?>-schema" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <textarea name="bb_seo_schema_<?php echo $post->ID; ?>" class="bb-schema-input" rows="4" style="width:100%; font-family:monospace;"><?php echo esc_textarea($meta['schema']); ?></textarea>
                </div>
            </div>

            <!-- Section 4: Tracking Scripts (NEW) -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden;">
                <button type="button" class="bb-section-toggle" aria-expanded="false" data-target="<?php echo $uid; ?>-tracking" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Tracking Scripts (Page Only) <span class="bb-toggle-icon">+</span></button>
                <div id="<?php echo $uid; ?>-tracking" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <?php 
                    $p_scripts = get_post_meta($post->ID, BARE_BONES_SEO_META_TRACKING, true) ?: array();
                    bare_bones_seo_render_tracking_table($p_scripts, 'bb_page_scripts_' . $post->ID, false); 
                    ?>
                </div>
            </div>
        </div>

        <!-- RIGHT Column: Preview -->
        <div>
            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; margin-bottom:8px;">Live Search Preview</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; max-width:600px;">
                <div id="<?php echo $uid; ?>-preview-desktop-title" style="font-family:arial; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($preview_title . ' — ' . $site_name); ?></div>
                <div id="<?php echo $uid; ?>-preview-desktop-desc" style="font-family:arial; font-size:14px; color:#545454; line-height:1.58; min-height:2.5em;"><?php echo esc_html($meta['desc']); ?></div>
            </div>
        </div>
    </div>
    <script type="application/json" id="<?php echo $uid; ?>-meta-data"><?php echo wp_json_encode(array('uid' => $uid, 'site_name' => $site_name, 'post_title' => $post->post_title)); ?></script>
    <?php
}

add_action('save_post', 'bare_bones_seo_save_meta_box_data');
function bare_bones_seo_save_meta_box_data($post_id) {
    if (!isset($_POST['bare_bones_seo_nonce']) || !wp_verify_nonce($_POST['bare_bones_seo_nonce'], BARE_BONES_SEO_NONCE_PAGE)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $data = array(
        'title' => $_POST['bb_seo_title_' . $post_id] ?? '',
        'desc'  => $_POST['bb_seo_desc_' . $post_id] ?? '',
        'should_index' => $_POST['bb_seo_should_index_' . $post_id] ?? 'yes',
    );
    bare_bones_seo_update_page_meta($post_id, $data);

    if (isset($_POST['bb_page_scripts_' . $post_id])) {
        update_post_meta($post_id, BARE_BONES_SEO_META_TRACKING, bare_bones_seo_sanitize_tracking_scripts($_POST['bb_page_scripts_' . $post_id]));
    }
}
