<?php
/**
 * Page-Level SEO Settings — Bare Bones SEO
 */
if (!defined('ABSPATH')) { exit; }

add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    $post_types = get_post_types(array('public' => true));
    foreach ($post_types as $type) {
        // Meta box titles are echoed unescaped by core, so the inline SVG renders.
        $title = bare_bones_seo_skull_icon(20) . ' Bare Bones SEO';
        add_meta_box('bare-bones-seo-box', $title, 'bare_bones_seo_render_meta_box', $type, 'normal', 'high');
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
                <button type="button" class="bb-section-toggle" data-target="<?php echo esc_attr($uid); ?>-snippet" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Snippet Builder <span class="bb-toggle-icon">−</span></button>
                <div id="<?php echo esc_attr($uid); ?>-snippet" style="padding:14px; border-top:1px solid #ddd;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:4px;">SEO TITLE</label>
                    <input type="text" name="bb_seo_title_<?php echo esc_attr($post->ID); ?>" value="<?php echo esc_attr($meta['title']); ?>" style="width:100%; margin-bottom:12px;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:4px;">META DESCRIPTION</label>
                    <textarea name="bb_seo_desc_<?php echo esc_attr($post->ID); ?>" rows="3" style="width:100%;"><?php echo esc_textarea($meta['desc']); ?></textarea>
                </div>
            </div>
            <!-- Section 2: Indexing -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button" class="bb-section-toggle" data-target="<?php echo esc_attr($uid); ?>-indexing" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Indexing <span class="bb-toggle-icon">+</span></button>
                <div id="<?php echo esc_attr($uid); ?>-indexing" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <?php 
                    $opts = array('yes' => 'Yes', 'no' => 'No', 'complicated_sitemap' => 'Remove from Sitemap Only');
                    foreach ($opts as $v => $l) : 
                        if (bare_bones_seo_more_restrictive($site_state, $v) === $v) : ?>
                            <label style="display:block; margin-bottom:8px;"><input type="radio" name="bb_seo_should_index_<?php echo esc_attr($post->ID); ?>" value="<?php echo esc_attr($v); ?>" <?php checked($effective_state, $v); ?>> <?php echo esc_html($l); ?></label>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <!-- Section 3: Schema -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button" class="bb-section-toggle" data-target="<?php echo esc_attr($uid); ?>-schema" style="width:100%; display:flex; justify-content:space-between; padding:10px; background:#f6f7f7; border:none; cursor:pointer; font-weight:600;">Schema Markup <span class="bb-toggle-icon">+</span></button>
                <div id="<?php echo esc_attr($uid); ?>-schema" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <label style="display:block; font-size:11px; font-weight:600; margin-bottom:4px;" for="<?php echo esc_attr($uid); ?>-schema-input">SCHEMA MARKUP (JSON-LD)</label>
                    <textarea id="<?php echo esc_attr($uid); ?>-schema-input" name="bb_seo_schema_<?php echo esc_attr($post->ID); ?>" rows="4" style="width:100%; font-family:monospace;" placeholder='{"@context": "https://schema.org", "@type": "Person"}'><?php echo esc_textarea($meta['schema']); ?></textarea>
                    <p style="margin:6px 0 0; font-size:12px; color:#646970;">
                        Paste the raw JSON only &mdash; no <code>&lt;script&gt;</code> tags and no <code>```</code> code fences. Invalid JSON is not added to the page.
                        <a href="https://validator.schema.org/" target="_blank" rel="noopener noreferrer">Check your schema</a>
                    </p>
                    <?php // Always rendered so the save handler can reveal it without injecting markup. ?>
                    <p id="<?php echo esc_attr($uid); ?>-schema-error"
                       style="margin:6px 0 0; color:#b32d2e;<?php echo bare_bones_seo_schema_is_invalid($meta['schema']) ? '' : ' display:none;'; ?>">
                        <strong>This JSON is not valid</strong> and will not be added to the page. Check for a missing comma, bracket, or quote.
                    </p>
                    <details style="margin-top:10px;">
                        <summary style="cursor:pointer; font-size:12px; color:#2271b1;">Need help writing this?</summary>
                        <p style="margin:8px 0 4px; font-size:12px; color:#646970;">Paste this into an AI assistant and fill in the blanks:</p>
                        <p style="margin:0; padding:10px; background:#f6f7f7; border:1px solid #ddd; border-radius:3px; font-family:monospace; font-size:11px; line-height:1.5; color:#333;">
                            Write JSON-LD schema markup for a web page. Output only the raw JSON object &mdash; no &lt;script&gt; tags, no markdown code fences, no explanation before or after. Page title: [title]. Page URL: [url]. What the page is about: [description]. Schema type: [Person / Organization / LocalBusiness / Article / Product].
                        </p>
                    </details>
                </div>
            </div>
        </div>
        <div>
            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; margin-bottom:8px;">Live Preview</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; max-width:600px;">
                <div style="font-family:arial; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($preview_title . ' — ' . $site_name); ?></div>
                <div style="font-family:arial; font-size:14px; color:#545454; line-height:1.58; min-height:2.5em;"><?php echo esc_html($meta['desc']); ?></div>
            </div>
        </div>
    </div>
    <?php
}

add_action('save_post', 'bare_bones_seo_save_meta_box_data');
function bare_bones_seo_save_meta_box_data($post_id) {
    if (!isset($_POST['bare_bones_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bare_bones_seo_nonce'])), BARE_BONES_SEO_NONCE_PAGE)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $data = array(
        'title'        => isset($_POST['bb_seo_title_' . $post_id]) ? sanitize_text_field(wp_unslash($_POST['bb_seo_title_' . $post_id])) : '',
        'desc'         => isset($_POST['bb_seo_desc_' . $post_id]) ? sanitize_text_field(wp_unslash($_POST['bb_seo_desc_' . $post_id])) : '',
        // Raw JSON by design — validated and re-encoded on output. See bare_bones_seo_update_page_meta().
        'schema'       => isset($_POST['bb_seo_schema_' . $post_id]) ? wp_unslash($_POST['bb_seo_schema_' . $post_id]) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        'should_index' => isset($_POST['bb_seo_should_index_' . $post_id]) ? sanitize_key(wp_unslash($_POST['bb_seo_should_index_' . $post_id])) : 'yes',
    );
    bare_bones_seo_update_page_meta($post_id, $data);
}
