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

/* ==========================================================================
   PART 3: LIGHTWEIGHT SELF-UPDATER (NO PLUGINS REQUIRED)
   ========================================================================== */
if ( is_admin() ) {
    add_filter( 'site_transient_update_plugins', function( $transient ) {
        $slug = 'bare-bones-seo/bare-bones-seo.php';
        $repo_url = 'https://raw.githubusercontent.com/charltondigital/bare-bones-seo/main/bare-bones-seo.php';
        
        if ( empty( $transient ) ) {
            $transient = new \stdClass();
        }

        // Cache the remote check for 12 hours so it never slows down your admin dashboard
        $remote_source = get_transient( 'bare_bones_seo_update_check' );
        if ( false === $remote_source ) {
            $response = wp_remote_get( $repo_url, [ 'sslverify' => true, 'timeout' => 10 ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $remote_source = wp_remote_retrieve_body( $response );
                set_transient( 'bare_bones_seo_update_check', $remote_source, 12 * HOUR_IN_SECONDS );
            }
        }

        if ( ! empty( $remote_source ) ) {
            // Read the Version header from your GitHub file string
            preg_match( '/Version:\s*([0-9.]+)/i', $remote_source, $matches );
            $remote_version = isset( $matches[1] ) ? $matches[1] : '0.0.0';
            
            // Read the current installed local version
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug );
            $local_version = $plugin_data['Version'];

            // If GitHub version is newer, trigger the native WordPress update notice
            if ( version_compare( $local_version, $remote_version, '<' ) ) {
                $obj = new \stdClass();
                $obj->slug = 'bare-bones-seo';
                $obj->plugin = $slug;
                $obj->new_version = $remote_version;
                $obj->package = 'https://github.com/charltondigital/bare-bones-seo/archive/refs/heads/main.zip';
                $transient->response[ $slug ] = $obj;
            }
        }
        return $transient;
    });

    // Clean up the folder name mismatch during the native WP unzip sequence
    add_filter( 'upgrader_source_selection', function( $source, $remote_source, $upgrader, $hook_extra ) {
        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === 'bare-bones-seo/bare-bones-seo.php' ) {
            $correct_path = dirname( $source ) . '/bare-bones-seo/';
            if ( ! file_exists( $correct_path ) ) {
                rename( $source, $correct_path );
            }
            return $correct_path;
        }
        return $source;
    }, 10, 4 );
}
