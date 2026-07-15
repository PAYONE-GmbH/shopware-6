<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 4);

require_once $projectRoot . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'PayonePayment\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
