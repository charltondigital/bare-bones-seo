<?php
/**
 * Page-Level SEO Settings
 *
 * Provides a shared render function used by both the meta box
 * and the bulk manager. One UI, two contexts.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the SEO meta box on all public post types.
 *
 * @since 1.0.0
 * @return void
 */
add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    $post_types = get_post_types(array('public' => true));

    foreach ($post_types as $type) {
        add_meta_box(
            'bare-bones-seo-box',
            '☠️ Page-Level SEO Settings',
            'bare_bones_seo_render_meta_box',
            $type,
            'normal',
            'high'
        );
    }
}

/**
 * Meta box wrapper.
 *
 * Outputs nonce then calls the shared render function.
 *
 * @since 1.0.0
 * @param WP_Post $post
 * @return void
 */
function bare_bones_seo_render_meta_box($post) {
    wp_nonce_field(BARE_BONES_SEO_NONCE_PAGE, 'bare_bones_seo_nonce');
    bare_bones_seo_render_fields($post);
}

/**
 * Shared field renderer.
 *
 * Renders the three collapsible sections (Snippet Builder, Indexing,
 * Schema Markup) for a given post. Used by both the meta box and the
 * bulk manager expanded row — same HTML, same JS, same experience.
 *
 * @since 1.0.3
 * @param WP_Post $post         The post object to render fields for
 * @param bool    $in_bulk      True when rendered inside the bulk manager
 * @return void
 */
function bare_bones_seo_render_fields($post, $in_bulk = false) {
    $meta        = bare_bones_seo_get_page_meta($post->ID);
    $site_name   = get_bloginfo('name');
    $index_status = get_post_meta($post->ID, BARE_BONES_SEO_META_INDEX, true);

    if ($index_status === '') {
        $index_status = 'yes';
    }

    // Build preview title: custom or post title + site name
    $preview_title           = $meta['title'] ? $meta['title'] : $post->post_title;
    $preview_title_with_site = $preview_title . ' — ' . $site_name;

    // Unique prefix per post so IDs don't clash in bulk manager
    $uid = 'bb-' . $post->ID;
    ?>

    <div style="display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 24px; padding: <?php echo $in_bulk ? '0' : '8px 0'; ?>;">

        <!-- LEFT: Collapsible Sections -->
        <div>

            <!-- SECTION 1: Snippet Builder (open by default) -->
            <div class="bb-section" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="true"
                        data-target="<?php echo esc_attr($uid); ?>-snippet"
                        style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f6f7f7; border:none; cursor:pointer; font-size:13px; font-weight:600; color:#1d2327; text-align:left;">
                    Snippet Builder
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666;">−</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-snippet" style="padding:14px; border-top:1px solid #ddd;">
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#444; margin-bottom:4px;">SEO Title</label>
                        <input type="text"
                               name="bb_seo_title"
                               class="bb-title-input"
                               data-uid="<?php echo esc_attr($uid); ?>"
                               value="<?php echo esc_attr($meta['title']); ?>"
                               placeholder="<?php echo esc_attr($post->post_title); ?>"
                               style="width:100%; box-sizing:border-box; font-size:13px;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#444; margin-bottom:4px;">Meta Description</label>
                        <textarea name="bb_seo_desc"
                                  class="bb-desc-input"
                                  data-uid="<?php echo esc_attr($uid); ?>"
                                  rows="3"
                                  style="width:100%; box-sizing:border-box; font-size:13px; resize:vertical;"><?php echo esc_textarea($meta['desc']); ?></textarea>
                    </div>
                    <button type="button"
                            class="button button-secondary bb-trigger-preview"
                            data-uid="<?php echo esc_attr($uid); ?>"
                            style="font-size:12px;">
                        Generate Preview
                    </button>
                </div>
            </div>

            <!-- SECTION 2: Indexing (collapsed) -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="false"
                        data-target="<?php echo esc_attr($uid); ?>-indexing"
                        style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f6f7f7; border:none; cursor:pointer; font-size:13px; font-weight:600; color:#1d2327; text-align:left;">
                    Indexing
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666;">+</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-indexing" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <p style="font-size:12px; font-weight:600; color:#444; margin:0 0 10px;">Should this page be indexed by Google?</p>

                    <?php
                    $index_options = array(
                        'yes'                => 'Yes',
                        'no'                 => 'No',
                        'complicated_noindex'  => "It's Complicated: Noindex but leave in sitemap",
                        'complicated_sitemap'  => "It's Complicated: Remove from sitemap but don't noindex",
                    );
                    foreach ($index_options as $value => $label) :
                    ?>
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px; cursor:pointer;">
                            <input type="radio"
                                   name="bb_seo_should_index_<?php echo esc_attr($post->ID); ?>"
                                   class="bb-index-radio"
                                   data-uid="<?php echo esc_attr($uid); ?>"
                                   value="<?php echo esc_attr($value); ?>"
                                   <?php checked($index_status, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>

                    <!-- Warning shown when anything other than YES is selected -->
                    <div class="bb-index-warning"
                         id="<?php echo esc_attr($uid); ?>-index-warning"
                         style="display:<?php echo ($index_status !== 'yes') ? 'block' : 'none'; ?>; background:#fff5f5; border-left:3px solid #dc3232; padding:8px 12px; font-size:12px; color:#dc3232; border-radius:2px; margin-top:10px;">
                        ⚠️ This page is currently hidden from Google.
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Schema Markup (collapsed) -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="false"
                        data-target="<?php echo esc_attr($uid); ?>-schema"
                        style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f6f7f7; border:none; cursor:pointer; font-size:13px; font-weight:600; color:#1d2327; text-align:left;">
                    Schema Markup
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666;">+</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-schema" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <textarea name="bb_seo_schema"
                              rows="6"
                              placeholder='<script type="application/ld+json">{"@context": "https://schema.org", ...}</script>'
                              style="width:100%; box-sizing:border-box; font-size:12px; font-family:monospace; resize:vertical; min-height:80px; max-height:200px; overflow-y:auto;"><?php echo esc_textarea($meta['schema']); ?></textarea>
                    <p style="font-size:11px; color:#888; margin:6px 0 0;">
                        Paste the complete JSON-LD block including the &lt;script&gt; wrapper tags.
                        <a href="https://technicalseo.com/tools/schema-generator/" target="_blank" rel="noopener noreferrer">Merkle Schema Generator</a>
                    </p>
                </div>
            </div>

        </div>

        <!-- RIGHT: Preview -->
        <div>
            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Desktop</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; margin-bottom:20px; max-width:600px; overflow:hidden;">
                <div id="<?php echo esc_attr($uid); ?>-preview-desktop-title"
                     style="font-family:arial,sans-serif; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:572px; line-height:1.3; margin-bottom:4px;">
                    <?php echo esc_html($preview_title_with_site); ?>
                </div>
                <div id="<?php echo esc_attr($uid); ?>-preview-desktop-desc"
                     style="font-family:arial,sans-serif; font-size:14px; color:#545454; max-width:572px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.58;">
                    <?php echo esc_html($meta['desc']); ?>
                </div>
            </div>

            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Mobile</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; max-width:380px; overflow:hidden;">
                <div id="<?php echo esc_attr($uid); ?>-preview-mobile-title"
                     style="font-family:arial,sans-serif; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:352px; line-height:1.3; margin-bottom:4px;">
                    <?php echo esc_html($preview_title_with_site); ?>
                </div>
                <div id="<?php echo esc_attr($uid); ?>-preview-mobile-desc"
                     style="font-family:arial,sans-serif; font-size:14px; color:#545454; max-width:352px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.58;">
                    <?php echo esc_html($meta['desc']); ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Pass data to JS (unique per post) -->
    <script type="application/json" id="<?php echo esc_attr($uid); ?>-meta-data"><?php echo json_encode(array(
        'uid'        => $uid,
        'site_name'  => $site_name,
        'post_title' => $post->post_title,
        'post_id'    => $post->ID,
    )); ?></script>

    <?php
}

/**
 * Save SEO metadata when post is saved via meta box.
 *
 * @since 1.0.0
 * @param int $post_id
 * @return void
 */
add_action('save_post', 'bare_bones_seo_save_meta_box_data');
function bare_bones_seo_save_meta_box_data($post_id) {
    if (!isset($_POST['bare_bones_seo_nonce']) ||
        !wp_verify_nonce($_POST['bare_bones_seo_nonce'], BARE_BONES_SEO_NONCE_PAGE)) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $data = array(
        'title'        => isset($_POST['bb_seo_title']) ? $_POST['bb_seo_title'] : '',
        'desc'         => isset($_POST['bb_seo_desc']) ? $_POST['bb_seo_desc'] : '',
        'schema'       => isset($_POST['bb_seo_schema']) ? $_POST['bb_seo_schema'] : '',
        'should_index' => isset($_POST['bb_seo_should_index_' . $post_id]) ? $_POST['bb_seo_should_index_' . $post_id] : 'yes',
    );

    bare_bones_seo_update_page_meta($post_id, $data);
}
