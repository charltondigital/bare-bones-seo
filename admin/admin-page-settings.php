<?php
/**
 * Page-Level SEO Settings — Bare Bones SEO
 *
 * Shared render function used by both the meta box and bulk manager.
 * One UI, two contexts, same experience everywhere.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register meta box on all public post types.
 *
 * @since 1.0.0
 */
add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    $post_types = get_post_types(array('public' => true));
    $meta_box_title = 'Page-Level SEO Settings — Bare Bones SEO';

    foreach ($post_types as $type) {
        add_meta_box(
            'bare-bones-seo-box',
            $meta_box_title,
            'bare_bones_seo_render_meta_box',
            $type,
            'normal',
            'high'
        );
    }
}

/**
 * Meta box wrapper — outputs nonce then calls shared render function.
 *
 * @since 1.0.0
 * @param WP_Post $post
 */
function bare_bones_seo_render_meta_box($post) {
    wp_nonce_field(BARE_BONES_SEO_NONCE_PAGE, 'bare_bones_seo_nonce');
    bare_bones_seo_render_fields($post);
}

/**
 * Shared field renderer.
 *
 * Renders three collapsible sections for a given post:
 * 1. Snippet Builder (open by default) — title, description, desktop + mobile preview
 * 2. Indexing (collapsed) — yes / no / it's complicated x2
 * 3. Schema Markup (collapsed) — JSON-LD textarea
 *
 * Used identically in the meta box and bulk manager expanded row.
 * Each element is prefixed with a unique ID (bb-{post_id}) so
 * multiple instances on the bulk manager page don't conflict.
 *
 * Preview fires on button click only — no live updates — to keep
 * the experience fast and clean.
 *
 * Desktop max-width: 600px (Google truncates titles here)
 * Mobile max-width:  920px (Google allows wider titles on mobile)
 * Description: 2-line clamp in both, blank space shown when empty
 *
 * @since 1.0.3
 * @param WP_Post $post     Post object to render fields for
 * @param bool    $in_bulk  True when rendered inside bulk manager
 */
function bare_bones_seo_render_fields($post, $in_bulk = false) {
    $meta         = bare_bones_seo_get_page_meta($post->ID);
    $site_name    = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
    $index_status = get_post_meta($post->ID, BARE_BONES_SEO_META_INDEX, true);

    if ($index_status === '') {
        $index_status = 'yes';
    }

    // Title shown in preview: custom title or post title, always with site name appended
    $preview_title           = $meta['title']
        ? html_entity_decode($meta['title'], ENT_QUOTES, 'UTF-8')
        : html_entity_decode($post->post_title, ENT_QUOTES, 'UTF-8');
    $preview_title_with_site = $preview_title . ' \u2014 ' . $site_name;

    // Unique prefix per post so IDs don't clash when multiple rows are open
    $uid = 'bb-' . $post->ID;

    // Indexation options
    $index_options = array(
        'yes'                 => 'Yes',
        'no'                  => 'No',
        'complicated_sitemap' => 'Remove from Sitemap Only',
    );

    // Ceiling rule: the site-level setting for this post type caps how visible
    // this page can be. Offer only states at or below it, and show the effective
    // (clamped) state as selected.
    $site_state      = bare_bones_seo_get_site_state($post->post_type);
    $effective_state = bare_bones_seo_get_effective_post_state($post->ID);

    $allowed_options = array();
    foreach ($index_options as $value => $label) {
        if (bare_bones_seo_more_restrictive($site_state, $value) === $value) {
            $allowed_options[$value] = $label;
        }
    }

    // Description placeholder: 2 blank lines when empty so preview box doesn't collapse
    $desc_placeholder_style = 'min-height: 2.5em;';
    ?>

    <div style="display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:24px; padding:<?php echo $in_bulk ? '0' : '8px 0'; ?>;">

        <!-- LEFT: Collapsible Sections -->
        <div>

            <!-- SECTION 1: Snippet Builder (open by default) -->
            <div class="bb-section" style="border:1px solid #ddd; border-radius:4px; overflow:hidden; margin-bottom:10px;">
                <button type="button"
                        class="bb-section-toggle"
                        aria-expanded="true"
                        data-target="<?php echo esc_attr($uid); ?>-snippet"
                        style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f6f7f7; border:none; cursor:pointer; font-size:13px; font-weight:600; color:#1d2327; text-align:left;">
                    Snippet Builder
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666; pointer-events:none;">−</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-snippet" style="padding:14px; border-top:1px solid #ddd;">
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#444; margin-bottom:4px;">SEO Title</label>
                        <input type="text"
                               name="bb_seo_title_<?php echo esc_attr($post->ID); ?>"
                               class="bb-title-input"
                               data-uid="<?php echo esc_attr($uid); ?>"
                               value="<?php echo esc_attr($meta['title']); ?>"
                               placeholder="<?php echo esc_attr($post->post_title); ?>"
                               style="width:100%; box-sizing:border-box; font-size:13px;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#444; margin-bottom:4px;">Meta Description</label>
                        <textarea name="bb_seo_desc_<?php echo esc_attr($post->ID); ?>"
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
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666; pointer-events:none;">+</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-indexing" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <p style="font-size:12px; font-weight:600; color:#444; margin:0 0 10px;">Should this page be indexed by Google?</p>
                    <?php if ($site_state === 'no') : ?>
                        <div style="background:#f0f6fc; border-left:3px solid #72aee6; padding:8px 12px; font-size:12px; color:#1d2327; border-radius:2px; margin-bottom:10px;">
                            This section is set to <strong>Noindex</strong> at the site level, so this page is noindexed regardless. Change it on the Indexation tab.
                        </div>
                    <?php elseif ($site_state === 'complicated_sitemap') : ?>
                        <div style="background:#f0f6fc; border-left:3px solid #72aee6; padding:8px 12px; font-size:12px; color:#1d2327; border-radius:2px; margin-bottom:10px;">
                            This section is <strong>removed from the sitemap</strong> at the site level. Page settings can only add noindex.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($allowed_options as $value => $label) : ?>
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px; cursor:pointer;">
                            <input type="radio"
                                   name="bb_seo_should_index_<?php echo esc_attr($post->ID); ?>"
                                   class="bb-index-radio"
                                   data-uid="<?php echo esc_attr($uid); ?>"
                                   value="<?php echo esc_attr($value); ?>"
                                   <?php checked($effective_state, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                    <div id="<?php echo esc_attr($uid); ?>-index-warning"
                         style="display:<?php echo ($effective_state === 'no') ? 'block' : 'none'; ?>; background:#fff5f5; border-left:3px solid #dc3232; padding:8px 12px; font-size:12px; color:#dc3232; border-radius:2px; margin-top:10px;">
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
                    <span class="bb-toggle-icon" style="font-size:18px; line-height:1; color:#666; pointer-events:none;">+</span>
                </button>
                <div id="<?php echo esc_attr($uid); ?>-schema" style="display:none; padding:14px; border-top:1px solid #ddd;">
                    <textarea name="bb_seo_schema_<?php echo esc_attr($post->ID); ?>"
                              rows="6"
                              placeholder='<script type="application/ld+json">{"@context": "https://schema.org", ...}</script>'
                              style="width:100%; box-sizing:border-box; font-size:12px; font-family:monospace; resize:vertical; min-height:80px; max-height:200px; overflow-y:auto;"><?php echo esc_textarea($meta['schema']); ?></textarea>
                    <p style="font-size:11px; color:#888; margin:6px 0 0;">
                        Paste the complete JSON-LD block including the &lt;script&gt; wrapper tags.
                        Browse types at <a href="https://schema.org" target="_blank" rel="noopener noreferrer">Schema.org</a> or validate with <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer">Google Rich Results Test</a>.
                    </p>
                </div>
            </div>

        </div>

        <!-- RIGHT: Preview -->
        <div>
            <!-- Desktop: 600px max (Google truncates titles here) -->
            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Desktop</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; margin-bottom:20px; max-width:600px; overflow:hidden;">
                <div id="<?php echo esc_attr($uid); ?>-preview-desktop-title"
                     style="font-family:arial,sans-serif; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:572px; line-height:1.3; margin-bottom:4px;">
                    <?php echo esc_html($preview_title . ' — ' . $site_name); ?>
                </div>
                <div id="<?php echo esc_attr($uid); ?>-preview-desktop-desc"
                     style="font-family:arial,sans-serif; font-size:14px; color:#545454; max-width:572px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.58; <?php echo $desc_placeholder_style; ?>">
                    <?php echo esc_html(html_entity_decode($meta['desc'], ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>

            <!-- Mobile: 920px max (Google allows wider titles on mobile) -->
            <div style="font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Mobile</div>
            <div style="background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px; max-width:920px; overflow:hidden;">
                <div id="<?php echo esc_attr($uid); ?>-preview-mobile-title"
                     style="font-family:arial,sans-serif; font-size:20px; color:#1a0dab; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:892px; line-height:1.3; margin-bottom:4px;">
                    <?php echo esc_html($preview_title . ' — ' . $site_name); ?>
                </div>
                <div id="<?php echo esc_attr($uid); ?>-preview-mobile-desc"
                     style="font-family:arial,sans-serif; font-size:14px; color:#545454; max-width:892px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.58; <?php echo $desc_placeholder_style; ?>">
                    <?php echo esc_html(html_entity_decode($meta['desc'], ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Pass data to JS (unique per post) -->
    <script type="application/json" id="<?php echo esc_attr($uid); ?>-meta-data"><?php echo wp_json_encode(array(
        'uid'        => $uid,
        'site_name'  => $site_name,
        'post_title' => html_entity_decode($post->post_title, ENT_QUOTES, 'UTF-8'),
        'post_id'    => $post->ID,
    )); ?></script>

    <?php
}

/**
 * Save SEO metadata when post is saved via meta box.
 *
 * Checks for both fallback global inputs (from single edit post actions)
 * and ID-suffixed inputs (from single-edit updates and bulk contexts).
 *
 * @since 1.0.0
 * @param int $post_id
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

    // Resolve form fields, falling back to non-prefixed fields if necessary
    $title  = isset($_POST['bb_seo_title_' . $post_id]) ? $_POST['bb_seo_title_' . $post_id] : (isset($_POST['bb_seo_title']) ? $_POST['bb_seo_title'] : '');
    $desc   = isset($_POST['bb_seo_desc_' . $post_id]) ? $_POST['bb_seo_desc_' . $post_id] : (isset($_POST['bb_seo_desc']) ? $_POST['bb_seo_desc'] : '');
    $schema = isset($_POST['bb_seo_schema_' . $post_id]) ? $_POST['bb_seo_schema_' . $post_id] : (isset($_POST['bb_seo_schema']) ? $_POST['bb_seo_schema'] : '');
    
    $should_index = 'yes';
    if (isset($_POST['bb_seo_should_index_' . $post_id])) {
        $should_index = $_POST['bb_seo_should_index_' . $post_id];
    }

    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => $title,
        'desc'         => $desc,
        'schema'       => $schema,
        'should_index' => $should_index,
    ));
}
