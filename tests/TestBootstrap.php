<?php

declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$projectRoot = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4);

$moduleAutoloader = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($moduleAutoloader)) {
    throw new \RuntimeException('Please run `composer dump-autoload` for the directory ' . dirname(__DIR__));
}

require_once $moduleAutoloader;

$shopwareBootstrapLookup = [
    $projectRoot . '/vendor/shopware/core/TestBootstrapper.php', // shopware/production
    $projectRoot . '/src/Core/TestBootstrapper.php',             // shopware/platform
];

foreach ($shopwareBootstrapLookup as $item) {
    if (is_readable($item)) {
        require_once $item;

        break;
    }
}

if (!class_exists(TestBootstrapper::class)) {
    throw new \RuntimeException("Shopware bootstrapper was not found. Tried locations: \n" . implode("\n", $shopwareBootstrapLookup));
}

$classLoser = (new TestBootstrapper())
    ->setProjectDir($projectRoot)
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addCallingPlugin()
    ->setDatabaseUrl($_SERVER['TEST_DATABASE_URL'] ?? null)
    ->bootstrap();
