<?php
/**
 * Page-level SEO settings meta box
 * 
 * Renders the Bare Bones SEO workspace in the post/page editor.
 * Provides per-post controls for title, description, schema, and indexing.
 * 
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the SEO meta box on all public post types
 * 
 * Fires on the 'add_meta_boxes' hook to register our custom meta box
 * for all public post types (pages, posts, custom post types).
 * The meta box appears in the 'normal' context (below the editor)
 * at high priority (renders first).
 * 
 * @since 1.0.0
 * @return void
 */
add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    // Get all publicly visible post types
    $post_types = get_post_types(array('public' => true));

    // Register meta box for each post type
    foreach ($post_types as $type) {
        add_meta_box(
            'bare-bones-seo-box',
            '☠️ Bare Bones SEO — Workspace',
            'bare_bones_seo_render_meta_box',
            $type,
            'normal',
            'high'
        );
    }
}

/**
 * Render the SEO workspace meta box
 * 
 * Outputs the meta box content in the post/page editor:
 * - Left column: Title, description, schema markup, and indexing checkbox
 * - Right column: Live preview of search snippet (desktop & mobile)
 * 
 * The preview updates in real-time as the user types, without page refresh.
 * 
 * @since 1.0.0
 * @param WP_Post $post The current post object
 * @return void
 */
function bare_bones_seo_render_meta_box($post) {
    // Output nonce field for verification on save
    wp_nonce_field(BARE_BONES_SEO_NONCE_PAGE, 'bare_bones_seo_nonce');

    // Fetch existing SEO metadata for this post
    $meta = bare_bones_seo_get_page_meta($post->ID);
    ?>

    <div class="bb-panel-grid">
        <!-- LEFT COLUMN: Input Fields -->
        <div>
            <!-- SEO Title Field -->
            <div class="bb-field-group">
                <label for="bb_seo_title">SEO Title Tag</label>
                <input type="text" 
                       id="bb_seo_title" 
                       name="bb_seo_title" 
                       class="bb-input" 
                       value="<?php echo esc_attr($meta['title']); ?>" 
                       maxlength="70" 
                       placeholder="Defaults to native page title...">
            </div>

            <!-- Meta Description Field -->
            <div class="bb-field-group">
                <label for="bb_seo_desc">Meta Description</label>
                <textarea id="bb_seo_desc" 
                          name="bb_seo_desc" 
                          class="bb-input" 
                          rows="3" 
                          maxlength="160" 
                          placeholder="Summarize your page content..."><?php echo esc_textarea($meta['desc']); ?></textarea>
            </div>

            <!-- Custom Schema Markup Field -->
            <div class="bb-field-group">
                <label for="bb_seo_schema">Custom Schema Markup (JSON-LD)</label>
                <textarea id="bb_seo_schema" 
                          name="bb_seo_schema" 
                          class="bb-input" 
                          rows="4" 
                          style="font-family: monospace;" 
                          placeholder='<script type="application/ld+json">{"@context": "https://schema.org", ...}</script>'><?php echo esc_textarea($meta['schema']); ?></textarea>
                <p class="description">
                    💡 Paste the complete JSON-LD block including the &lt;script&gt; wrapper tags. 
                    Use the <a href="https://schema.org/docs/gs.html" target="_blank" rel="noopener noreferrer">Schema.org guide</a> 
                    or <a href="https://technicalseo.com/tools/schema-generator/" target="_blank" rel="noopener noreferrer">Merkle Schema Generator</a>.
                </p>
            </div>

            <!-- Indexing Checkbox -->
            <div class="bb-field-group">
                <label>
                    <input type="checkbox" 
                           name="bb_seo_should_index" 
                           value="no" 
                           <?php checked(!$meta['should_index']); ?>> 
                    🚨 Hide this individual page from search engine indexes (noindex, follow)
                </label>
            </div>
        </div>

        <!-- RIGHT COLUMN: Live Preview -->
        <div>
            <div style="margin-bottom: 10px; font-weight:600;">Snippet Quality Validation Preview</div>
            
            <!-- Desktop Preview (600px) -->
            <div class="bb-simulator-card" style="width: 600px;">
                <span class="bb-sim-url"><?php echo esc_url(get_permalink($post->ID)); ?></span>
                <span id="bb-preview-desktop-title" class="bb-sim-title">
                    <?php echo $meta['title'] ? esc_html($meta['title']) : esc_html($post->post_title); ?>
                </span>
                <div id="bb-preview-desktop-desc" class="bb-sim-desc">
                    <?php echo $meta['desc'] ? esc_html($meta['desc']) : 'Please enter a description value to accurately populate search snippets...'; ?>
                </div>
            </div>

            <!-- Mobile Preview (360px) -->
            <div class="bb-simulator-card bb-mobile" style="width: 360px;">
                <span class="bb-sim-url"><?php echo esc_url(get_permalink($post->ID)); ?></span>
                <span id="bb-preview-mobile-title" class="bb-sim-title">
                    <?php echo $meta['title'] ? esc_html($meta['title']) : esc_html($post->post_title); ?>
                </span>
                <div id="bb-preview-mobile-desc" class="bb-sim-desc">
                    <?php echo $meta['desc'] ? esc_html($meta['desc']) : 'Please enter a description value to accurately populate search snippets...'; ?>
                </div>
            </div>
            
            <button type="button" id="bb-trigger-preview" class="button button-secondary">Generate Visual Verification</button>
        </div>
    </div>

    <!-- Footer with version info -->
    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 11px; color: #666;">
        ☠️ Running Bare Bones SEO v<?php echo BARE_BONES_SEO_VERSION; ?>. Need deployment tips? 
        <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" style="color: #d63636; text-decoration: none; font-weight: 600;">View Official Guide</a>
    </div>

    <?php
}

/**
 * Save SEO metadata when post is saved
 * 
 * Fires on 'save_post' hook. Validates nonce, checks capabilities,
 * skips autosaves, and saves the submitted SEO metadata.
 * 
 * Security checks in order:
 * 1. Nonce verification (CSRF protection)
 * 2. Autosave check (prevent saving during automatic backups)
 * 3. Capability check (user can edit this post)
 * 
 * @since 1.0.0
 * @param int $post_id The ID of the post being saved
 * @return void
 */
add_action('save_post', 'bare_bones_seo_save_meta_box_data');
function bare_bones_seo_save_meta_box_data($post_id) {
    // SECURITY: Verify nonce
    if (!isset($_POST['bare_bones_seo_nonce']) || 
        !wp_verify_nonce($_POST['bare_bones_seo_nonce'], BARE_BONES_SEO_NONCE_PAGE)) {
        return;
    }

    // SECURITY: Skip autosaves (WordPress auto-saves periodically)
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // SECURITY: Verify user can edit this post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Safely extract POST data with isset checks to prevent notices
    $data = array(
        'title'        => isset($_POST['bb_seo_title']) ? $_POST['bb_seo_title'] : '',
        'desc'         => isset($_POST['bb_seo_desc']) ? $_POST['bb_seo_desc'] : '',
        'schema'       => isset($_POST['bb_seo_schema']) ? $_POST['bb_seo_schema'] : '',
        'should_index' => isset($_POST['bb_seo_should_index']) ? 'no' : 'yes',
    );

    // Save the metadata (sanitization happens in the helper function)
    bare_bones_seo_update_page_meta($post_id, $data);
}
