<?php

(static function (): void {
    $pluginRoot = dirname(__DIR__, 2);
    $docRoot    = rtrim($_SERVER['SHOPWARE_ROOT'] ?? $pluginRoot, '/');

    include $docRoot . '/vendor/autoload.php';

    $loaders = [
        // For development
        $pluginRoot . '/src/Resources/build/vendor/autoload.php',

        // For production
        $pluginRoot . '/src/Resources/vendor/autoload.php',
    ];

    foreach ($loaders as $loader) {
        if (is_file($loader) && is_readable($loader)) {
            include $loader;

            return;
        }
    }
})();
