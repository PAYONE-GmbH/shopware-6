<?php

declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

if (is_readable(__DIR__ . '/../../../project/vendor/shopware/platform/src/Core/TestBootstrapper.php')) {
    require __DIR__ . '/../../../project/vendor/shopware/platform/src/Core/TestBootstrapper.php';
} elseif (is_readable(__DIR__ . '/../../../project/vendor/shopware/core/TestBootstrapper.php')) {
    require __DIR__ . '/../../../project/vendor/shopware/core/TestBootstrapper.php';
} elseif (is_readable(__DIR__ . '/../../../vendor/shopware/platform/src/Core/TestBootstrapper.php')) {
    require __DIR__ . '/../../../vendor/shopware/platform/src/Core/TestBootstrapper.php';
} elseif (is_readable(__DIR__ . '/../../../vendor/shopware/core/TestBootstrapper.php')) {
    require __DIR__ . '/../../../vendor/shopware/core/TestBootstrapper.php';
} else {
    // vendored from platform, only use local TestBootstrapper if not already defined in platform
    require __DIR__ . '/TestBootstrapper.php';
}

// project-root
$projectRoot = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4);

// get classloader
$expectedClassLoaderFiles = [
    __DIR__ . '/../vendor/autoload.php',
    $projectRoot . '/vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];
$classLoaderFile = null;
foreach ($expectedClassLoaderFiles as $_classLoaderFile) {
    if (file_exists($_classLoaderFile)) {
        $classLoaderFile = $_classLoaderFile;

        break;
    }
}

return (new TestBootstrapper())
    ->setProjectDir($projectRoot)
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addCallingPlugin()
    ->setDatabaseUrl($_SERVER['TEST_DATABASE_URL'] ?? null)
    ->bootstrap()
    ->setClassLoader(require $classLoaderFile)
    ->getClassLoader();
