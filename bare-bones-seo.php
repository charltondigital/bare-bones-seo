<?php
/**
 * Plugin Name:       Bare Bones SEO
 * Plugin URI:        https://charltondigital.com
 * Description:       Lightweight, no-bloat custom SEO overrides for Title tags, Meta Descriptions, and Archive controls.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourdomain.com
 * License:           GPL2
 * GitHub Plugin URI: https://github.com/charltondigital/bare-bones-seo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   PART 1: BACKEND DASHBOARD UI & METABOXES
   ========================================================================== */
if ( is_admin() ) {
    add_action( 'add_meta_boxes', function() {
        foreach ( [ 'post', 'page' ] as $s ) {
            add_meta_box( 'custom_seo_box', 'SEO Settings (No Plugin)', 'draw_seo_fields', $s, 'normal', 'high' );
        }
    });

    function draw_seo_fields( $post ) {
        wp_nonce_field( 'save_seo', 'seo_nonce' );
        $t = get_post_meta( $post->ID, '_seo_title', true );
        $d = get_post_meta( $post->ID, '_seo_desc', true );
        
        // SEO Title Field + Counter
        echo '<p><label style="display:block;font-weight:bold;margin-bottom:5px;">SEO Title Overwrite:</label>';
        echo '<input type="text" id="seo_title_input" name="seo_t" value="'.esc_attr($t).'" style="width:100%; border-radius:4px;" placeholder="Defaults to WP logic..." />';
        echo '<span id="seo_title_counter" style="font-size:11px;color:#666;display:block;margin-top:3px;">0 characters</span></p>';
        
        // SEO Meta Description Field + Counter
        echo '<p><label style="display:block;font-weight:bold;margin-bottom:5px;">SEO Meta Description:</label>';
        echo '<textarea id="seo_desc_input" name="seo_d" rows="3" style="width:100%; border-radius:4px;" placeholder="Defaults to blank...">'.esc_textarea($d).'</textarea>';
        echo '<span id="seo_desc_counter" style="font-size:11px;color:#666;display:block;margin-top:3px;">0 characters</span></p>';

        // Real-time Warning Zone JS Counter Script
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupCounter(inputId, counterId, visualLimit, maxLimit) {
                var input = document.getElementById(inputId);
                var counter = document.getElementById(counterId);
                if (!input || !counter) return;

                function update() {
                    var len = input.value.length;
                    counter.textContent = len + ' characters';
                    
                    if (len > maxLimit) {
                        counter.style.color = '#d94f4f'; // Red
                        counter.textContent += ' (Exceeds max optimization limit)';
                    } else if (len > visualLimit) {
                        counter.style.color = '#e6a23c'; // Orange
                        counter.textContent += ' (Truncated visually, still indexing)';
                    } else {
                        counter.style.color = '#666'; // Gray
                    }
                }
                input.addEventListener('input', update);
                update();
            }
            setupCounter('seo_title_input', 'seo_title_counter', 60, 100);
            setupCounter('seo_desc_input', 'seo_desc_counter', 160, 230);
        });
        </script>
        <?php
    }

    add_action( 'save_post', function( $id ) {
        if ( !isset($_POST['seo_nonce']) || !wp_verify_nonce($_POST['seo_nonce'], 'save_seo') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $id) ) return;
        if ( isset($_POST['seo_t']) ) update_post_meta( $id, '_seo_title', sanitize_text_field($_POST['seo_t']) );
        if ( isset($_POST['seo_d']) ) update_post_meta( $id, '_seo_desc', sanitize_text_field($_POST['seo_d']) );
    });
}

/* ==========================================================================
   PART 2: PUBLIC FRONT-END CODE INJECTION & ARCHIVE FILTERS
   ========================================================================== */

// Inject Meta Description into public <head>
add_action( 'wp_head', function() {
    if ( is_singular() ) {
        $d = get_post_meta( get_the_ID(), '_seo_desc', true );
        if ( !empty($d) ) {
            echo '<meta name="description" content="' . esc_attr($d) . '" />' . "\n";
        }
    }
}, 2 );

// Override Browser Title Tag if field is filled
add_filter( 'document_title_parts', function( $parts ) {
    if ( is_singular() || is_front_page() ) {
        $t = get_post_meta( get_the_ID(), '_seo_title', true );
        if ( !empty($t) ) {
            $parts['title'] = $t;
            unset( $parts['tagline'] );
            unset( $parts['site'] );
        }
    }
    return $parts;
}, 10, 1 );

// Automatically noindex low-value archive pages but allow link crawling
add_action( 'wp_head', function() {
    if ( is_tag() || is_date() || is_author() || is_search() ) {
        echo '<meta name="robots" content="noindex, follow" />' . "\n";
    }
}, 1 );
