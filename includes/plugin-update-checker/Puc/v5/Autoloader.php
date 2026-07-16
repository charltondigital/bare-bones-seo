<?php
namespace YahnisElsts\PluginUpdateChecker\v5;

if ( !class_exists(Autoloader::class, false) ) {
    class Autoloader {
        private $prefix;
        private $baseDir;

        public function __construct($baseDir) {
            $this->prefix = __NAMESPACE__ . '\\';
            $this->baseDir = rtrim($baseDir, '/\\') . '/';
            spl_autoload_register(array($this, 'autoload'));
        }

        public function autoload($class) {
            if (strpos($class, $this->prefix) !== 0) {
                return;
            }
            $relativeClass = substr($class, strlen($this->prefix));
            $file = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
}
