<?php
namespace YahnisElsts\PluginUpdateChecker\v5;

if ( !class_exists(PucFactory::class, false) ) {
    class PucFactory {
        public static function buildUpdateChecker($url, $fullPluginFile, $slug = '', $checkPeriod = 12, $optionName = '') {
            // Simplified factory directly targeting GitHub/VCS infrastructure
            if ( strpos($url, 'github.com') !== false ) {
                return new \YahnisElsts\PluginUpdateChecker\v5\Plugin\UpdateChecker(
                    $url,
                    $fullPluginFile,
                    $slug,
                    $checkPeriod,
                    $optionName
                );
            }
            return null;
        }
    }
}
