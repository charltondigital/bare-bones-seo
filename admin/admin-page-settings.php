<?php
/**
 * Page-Level SEO Settings meta box
 *
 * Three collapsible sections:
 * 1. Snippet Builder (open by default) — title, description, preview
 * 2. Indexing (collapsed) — yes/no/it's complicated
 * 3. Schema Markup (collapsed) — JSON-LD
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
 * Render the Page-Level SEO Settings meta box.
 *
 * Left column: three collapsible sections (snippet builder, indexing, schema).
 * Right column: desktop and mobile search snippet previews.
 *
 * Preview renders on button click, not on keystroke, to keep things fast.
 * Title preview appends site name to match how Google displays titles.
 *
 * @since 1.0.0
 * @param WP_Post $post The current post object
 * @return void
 */
function bare_bones_seo_render_meta_box($post) {
    wp_nonce_field(BARE_BONES_SEO_NONCE_PAGE, 'bare_bones_seo_nonce');

    $meta      = bare_bones_seo_get_page_meta($post->ID);
    $site_name = get_bloginfo('name');

    // Build the title shown in preview: custom title or post title, plus site name
    $preview_title = $meta['title'] ? $meta['title'] : $post->post_title;
    $preview_title_with_site = $preview_title . ' — ' . $site_name;

    // Indexing status
    $index_status = get_post_meta($post->ID, BARE_BONES_SEO_META_INDEX, true);
    if ($index_status === '') {
        $index_status = 'yes'; // Default to indexed
    }
    ?>

    <div style="display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 24px; padding: 8px 0;">

        <!-- LEFT COLUMN: Collapsible Sections -->
        <div>

            <!-- SECTION 1: Snippet Builder (open by default) -->
            <div class="bb-section" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="true"
                        data-target="bb-section-snippet"
                        style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f6f7f7; border: none; cursor: pointer; font-size: 13px; font-weight: 600; color: #1d2327; text-align: left;">
                    Snippet Builder
                    <span class="bb-toggle-icon" style="font-size: 18px; line-height: 1; color: #666;">−</span>
                </button>
                <div id="bb-section-snippet" style="padding: 14px; border-top: 1px solid #ddd;">
                    <div style="margin-bottom: 12px;">
                        <label for="bb_seo_title" style="display: block; font-size: 12px; font-weight: 600; color: #444; margin-bottom: 4px;">SEO Title</label>
                        <input type="text"
                               id="bb_seo_title"
                               name="bb_seo_title"
                               value="<?php echo esc_attr($meta['title']); ?>"
                               placeholder="<?php echo esc_attr($post->post_title); ?>"
                               style="width: 100%; box-sizing: border-box; font-size: 13px;">
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label for="bb_seo_desc" style="display: block; font-size: 12px; font-weight: 600; color: #444; margin-bottom: 4px;">Meta Description</label>
                        <textarea id="bb_seo_desc"
                                  name="bb_seo_desc"
                                  rows="3"
                                  style="width: 100%; box-sizing: border-box; font-size: 13px; resize: vertical;"><?php echo esc_textarea($meta['desc']); ?></textarea>
                    </div>
                    <button type="button"
                            id="bb-trigger-preview"
                            class="button button-secondary"
                            style="font-size: 12px;">
                        Generate Preview
                    </button>
                </div>
            </div>

            <!-- SECTION 2: Indexing (collapsed by default) -->
            <div class="bb-section" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="false"
                        data-target="bb-section-indexing"
                        style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f6f7f7; border: none; cursor: pointer; font-size: 13px; font-weight: 600; color: #1d2327; text-align: left;">
                    Indexing
                    <span class="bb-toggle-icon" style="font-size: 18px; line-height: 1; color: #666;">+</span>
                </button>
                <div id="bb-section-indexing" style="display: none; padding: 14px; border-top: 1px solid #ddd;">
                    <p style="font-size: 12px; font-weight: 600; color: #444; margin: 0 0 10px;">Should this page be indexed by Google?</p>

                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; cursor: pointer;">
                        <input type="radio"
                               name="bb_seo_should_index"
                               value="yes"
                               <?php checked($index_status, 'yes'); ?>>
                        YES
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; cursor: pointer;">
                        <input type="radio"
                               name="bb_seo_should_index"
                               value="no"
                               <?php checked($index_status, 'no'); ?>>
                        NO
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 13px; cursor: pointer;">
                        <input type="radio"
                               name="bb_seo_should_index"
                               value="advanced"
                               <?php checked($index_status, 'advanced'); ?>>
                        It's Complicated <span style="font-size: 11px; color: #888; margin-left: 4px;">(noindex; remove from sitemap)</span>
                    </label>

                    <!-- Warning: shown when NO or It's Complicated is selected -->
                    <div id="bb-index-warning"
                         style="display: <?php echo in_array($index_status, array('no', 'advanced')) ? 'block' : 'none'; ?>; background: #fff5f5; border-left: 3px solid #dc3232; padding: 8px 12px; font-size: 12px; color: #dc3232; border-radius: 2px;">
                        ⚠️ This page is currently hidden from Google.
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Schema Markup (collapsed by default) -->
            <div class="bb-section" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="false"
                        data-target="bb-section-schema"
                        style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f6f7f7; border: none; cursor: pointer; font-size: 13px; font-weight: 600; color: #1d2327; text-align: left;">
                    Schema Markup
                    <span class="bb-toggle-icon" style="font-size: 18px; line-height: 1; color: #666;">+</span>
                </button>
                <div id="bb-section-schema" style="display: none; padding: 14px; border-top: 1px solid #ddd;">
                    <textarea id="bb_seo_schema"
                              name="bb_seo_schema"
                              rows="6"
                              placeholder='<script type="application/ld+json">{"@context": "https://schema.org", ...}</script>'
                              style="width: 100%; box-sizing: border-box; font-size: 12px; font-family: monospace; resize: vertical;"><?php echo esc_textarea($meta['schema']); ?></textarea>
                    <p style="font-size: 11px; color: #888; margin: 6px 0 0;">
                        Paste the complete JSON-LD block including the &lt;script&gt; wrapper tags.
                        <a href="https://technicalseo.com/tools/schema-generator/" target="_blank" rel="noopener noreferrer">Merkle Schema Generator</a>
                    </p>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Preview -->
        <div>

            <!-- Desktop Preview -->
            <div style="font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Desktop</div>
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 14px; margin-bottom: 20px; max-width: 600px; overflow: hidden;">
                <div id="bb-preview-desktop-title"
                     style="font-family: arial, sans-serif; font-size: 20px; color: #1a0dab; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 572px; line-height: 1.3; margin-bottom: 4px;">
                    <?php echo esc_html($preview_title_with_site); ?>
                </div>
                <div id="bb-preview-desktop-desc"
                     style="font-family: arial, sans-serif; font-size: 14px; color: #545454; max-width: 572px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.58;">
                    <?php echo esc_html($meta['desc']); ?>
                </div>
            </div>

            <!-- Mobile Preview -->
            <div style="font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Mobile</div>
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 14px; max-width: 380px; overflow: hidden;">
                <div id="bb-preview-mobile-title"
                     style="font-family: arial, sans-serif; font-size: 20px; color: #1a0dab; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 352px; line-height: 1.3; margin-bottom: 4px;">
                    <?php echo esc_html($preview_title_with_site); ?>
                </div>
                <div id="bb-preview-mobile-desc"
                     style="font-family: arial, sans-serif; font-size: 14px; color: #545454; max-width: 352px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.58;">
                    <?php echo esc_html($meta['desc']); ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Pass site name to JS -->
    <script type="application/json" id="bb-meta-data"><?php echo json_encode(array(
        'site_name'  => $site_name,
        'post_title' => $post->post_title,
    )); ?></script>

    <?php
}

/**
 * Save SEO metadata when post is saved.
 *
 * Security checks: nonce, autosave, capability.
 * Sanitization handled by bare_bones_seo_update_page_meta().
 *
 * @since 1.0.0
 * @param int $post_id The ID of the post being saved
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
        'should_index' => isset($_POST['bb_seo_should_index']) ? $_POST['bb_seo_should_index'] : 'yes',
    );

    bare_bones_seo_update_page_meta($post_id, $data);
}
