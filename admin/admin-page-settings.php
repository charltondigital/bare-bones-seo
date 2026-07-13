<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'bare_bones_seo_register_meta_box');
function bare_bones_seo_register_meta_box() {
    $post_types = get_post_types(array('public' => true));
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

function bare_bones_seo_render_meta_box($post) {
    wp_nonce_field('bare_bones_seo_save_nonce', 'bare_bones_seo_nonce');
    $meta = bare_bones_seo_get_page_meta($post->ID);
    ?>
    <style>
        .bb-panel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 10px 0; }
        .bb-field-group { margin-bottom: 18px; }
        .bb-field-group label { display: block; font-weight: 600; margin-bottom: 6px; }
        .bb-input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif; }
        .bb-simulator-card { border: 1px solid #dadce0; border-radius: 8px; padding: 15px; background: #fff; margin-bottom: 15px; font-family: Arial, sans-serif; }
        .bb-sim-title { color: #1a0dab; font-size: 20px; text-decoration: none; display: block; margin-bottom: 4px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .bb-sim-url { color: #202124; font-size: 14px; margin-bottom: 4px; display: block; }
        .bb-sim-desc { color: #4d5156; font-size: 14px; line-height: 1.58; word-wrap: break-word; }
        .bb-mobile .bb-sim-title { font-size: 16px; color: #15c; }
    </style>

    <div class="bb-panel-grid">
        <div>
            <div class="bb-field-group">
                <label for="bb_seo_title">SEO Title Tag</label>
                <input type="text" id="bb_seo_title" name="bb_seo_title" class="bb-input" value="<?php echo esc_attr($meta['title']); ?>" maxlength="70" placeholder="Defaults to native page title...">
            </div>
            <div class="bb-field-group">
                <label for="bb_seo_desc">Meta Description</label>
                <textarea id="bb_seo_desc" name="bb_seo_desc" class="bb-input" rows="3" maxlength="160" placeholder="Summarize your page content..."><?php echo esc_textarea($meta['desc']); ?></textarea>
            </div>
            <div class="bb-field-group">
                <label for="bb_seo_schema">Custom Schema Markup (JSON-LD)</label>
                <textarea id="bb_seo_schema" name="bb_seo_schema" class="bb-input" rows="4" style="font-family: monospace;" placeholder='<script type="application/ld+json">...</script>'><?php echo esc_textarea($meta['schema']); ?></textarea>
                <p class="description"><a href="https://technicalseo.com/tools/schema-generator/" target="_blank" rel="noopener noreferrer">Generate Schema with Merkle</a></p>
            </div>
            <div class="bb-field-group">
                <label><input type="checkbox" name="bb_seo_should_index" value="no" <?php checked(!$meta['should_index']); ?>> 🚨 Hide this individual page from search engine indexes (noindex, follow)</label>
            </div>
        </div>

        <div>
            <div style="margin-bottom: 10px; font-weight:600;">Snippet Quality Validation Preview</div>
            
            <div class="bb-simulator-card" style="width: 600px;">
                <span class="bb-sim-url"><?php echo esc_url(get_permalink($post->ID)); ?></span>
                <span id="bb-preview-desktop-title" class="bb-sim-title"><?php echo $meta['title'] ? esc_html($meta['title']) : esc_html($post->post_title); ?></span>
                <div id="bb-preview-desktop-desc" class="bb-sim-desc"><?php echo $meta['desc'] ? esc_html($meta['desc']) : 'Please enter a description value to accurately populate search snippets...'; ?></div>
            </div>

            <div class="bb-simulator-card bb-mobile" style="width: 360px;">
                <span class="bb-sim-url"><?php echo esc_url(get_permalink($post->ID)); ?></span>
                <span id="bb-preview-mobile-title" class="bb-sim-title"><?php echo $meta['title'] ? esc_html($meta['title']) : esc_html($post->post_title); ?></span>
                <div id="bb-preview-mobile-desc" class="bb-sim-desc"><?php echo $meta['desc'] ? esc_html($meta['desc']) : 'Please enter a description value to accurately populate search snippets...'; ?></div>
            </div>
            
            <button type="button" id="bb-trigger-preview" class="button button-secondary">Generate Visual Verification</button>
        </div>
    </div>

    <script>
        document.getElementById('bb-trigger-preview').addEventListener('click', function() {
            var rawTitle = document.getElementById('bb_seo_title').value || '<?php echo esc_js($post->post_title); ?>';
            var rawDesc = document.getElementById('bb_seo_desc').value || 'Please enter a description value to accurately populate search snippets...';
            
            document.getElementById('bb-preview-desktop-title').innerText = rawTitle;
            document.getElementById('bb-preview-mobile-title').innerText = rawTitle;
            document.getElementById('bb-preview-desktop-desc').innerText = rawDesc;
            document.getElementById('bb-preview-mobile-desc').innerText = rawDesc;
        });
    </script>

    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 11px; color: #666;">
        ☠️ Running Bare Bones SEO v1.0.2. Need deployment tips? 
        <a href="https://charltondigital.com/tools/bare-bones-seo-wordpress-plugin/" target="_blank" rel="noopener noreferrer" style="color: #d63636; text-decoration: none; font-weight: 600;">View Official Guide</a>
    </div>
    <?php
}

add_action('save_post', 'bare_bones_seo_save_meta_box_data');
function bare_bones_seo_save_meta_box_data($post_id) {
    if (!isset($_POST['bare_bones_seo_nonce']) || !wp_verify_nonce($_POST['bare_bones_seo_nonce'], 'bare_bones_seo_save_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    bare_bones_seo_update_page_meta($post_id, array(
        'title'        => $_POST['bb_seo_title'],
        'desc'         => $_POST['bb_seo_desc'],
        'schema'       => $_POST['bb_seo_schema'],
        'should_index' => isset($_POST['bb_seo_should_index']) ? 'no' : 'yes'
    ));
}
