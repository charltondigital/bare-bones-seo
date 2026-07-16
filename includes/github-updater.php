<?php
/**
 * Safe, Single-File GitHub Update Engine for Bare Bones SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('BBSEO_GitHub_Updater')) {
    class BBSEO_GitHub_Updater {
        private $file;
        private $slug;
        private $repo;
        private $version;

        public function __construct($file, $repo) {
            $this->file = $file;
            $this->slug = dirname(plugin_basename($file));
            $this->repo = $repo;

            if (is_admin()) {
                add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
                add_filter('plugins_api', array($this, 'inject_plugin_info'), 20, 3);
                
                // Native hook to catch folder renaming during plugin updates/installations
                add_filter('upgrader_source_selection', array($this, 'normalize_github_zip_folder'), 10, 4);
            }
        }

        /**
         * Intercepts the zip extraction and renames "bare-bones-seo-main" 
         * back to "bare-bones-seo" seamlessly for beta-testers.
         */
        public function normalize_github_zip_folder($source, $remote_source, $upgrader, $hook_extra = array()) {
            global $wp_filesystem;

            // Target only this repository's extraction process
            if (empty($source) || strpos($source, basename($this->repo)) === false) {
                return $source;
            }

            $corrected_folder_name = 'bare-bones-seo';
            $corrected_source = trailingslashit($remote_source) . $corrected_folder_name;

            // If the directory name is already correct, bypass renaming
            if (rtrim($source, '/') === rtrim($corrected_source, '/')) {
                return $source;
            }

            // Silently rename the directory in place
            if ($wp_filesystem->move($source, $corrected_source)) {
                return trailingslashit($corrected_source);
            }

            return $source;
        }

        private function get_local_version() {
            if (!file_exists($this->file)) {
                return '0.0.0';
            }
            // Super-fast header lookup bypassing heavy core functions
            $data = file_get_contents($this->file, false, null, 0, 4096);
            if (preg_match('/^[ \t\/*#]*Version\s*:\s*(.*)$/mi', $data, $match)) {
                return trim($match[1]);
            }
            return '0.0.0';
        }

        public function check_for_updates($transient) {
            if (empty($transient)) {
                $transient = new \stdClass();
            }

            // Fixed Warning 2: Safely parse repo path using hash boundaries
            $repo_path = trim($this->repo, '/');
            $api_url = 'https://api.github.com/repos/' . $repo_path . '/releases/latest';
            
            $response = wp_remote_get($api_url, array(
                'headers' => array('User-Agent' => 'Bare-Bones-SEO-Updater-v1'),
                'timeout' => 10
            ));

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return $transient;
            }

            $release = json_decode(wp_remote_retrieve_body($response));
            if (empty($release) || empty($release->tag_name)) {
                return $transient;
            }

            $remote_version = ltrim($release->tag_name, 'vV');
            $local_version = $this->get_local_version();

            if (version_compare($remote_version, $local_version, '>')) {
                $update = new \stdClass();
                $update->slug = $this->slug;
                $update->plugin = plugin_basename($this->file);
                $update->new_version = $remote_version;
                $update->url = 'https://github.com/' . $this->repo;
                $update->package = $release->zipball_url;

                $transient->response[$update->plugin] = $update;
            }

            return $transient;
        }

        public function inject_plugin_info($result, $action, $args) {
            if ($action !== 'plugin_information' || (isset($args->slug) && $args->slug !== $this->slug)) {
                return $result;
            }

            $info = new \stdClass();
            $info->name = 'Bare Bones SEO';
            $info->slug = $this->slug;
            $info->version = $this->get_local_version();
            $info->sections = array(
                'description' => 'A lightweight, performance-first SEO utility providing absolute indexing control without background bloat.'
            );

            return $info;
        }
    }
}
