<?php
/**
 * Plugin Update Checker Library Loader
 * Version: 5.4
 */

if ( !class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory', false) ) {
    require_once __DIR__ . '/Puc/v5/PucFactory.php';
    require_once __DIR__ . '/Puc/v5/Autoloader.php';
    
    // Register the autoloader for the library classes
    new YahnisElsts\PluginUpdateChecker\v5\Autoloader(__DIR__ . '/Puc/v5');
}
