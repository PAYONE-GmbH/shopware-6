<?php declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

$projectRoot = $_SERVER['PROJECT_ROOT'];
if (!$projectRoot) {
    die('Server variable PROJECT_ROOT is missing');
}

require_once $projectRoot . '/vendor/autoload.php';

if (file_exists($projectRoot . '/.env')) {
    (new Dotenv())->usePutEnv()->load($projectRoot . '/.env');
}
