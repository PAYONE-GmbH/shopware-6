<?php

declare(strict_types=1);

namespace PayonePayment\Core\Utils;

use Composer\Autoload\ClassLoader;

final class PayoneClassLoader extends ClassLoader
{
    private const VENDOR_DEPENDENCIES_PSR4 = [
        'Sinergi\\BrowserDetector\\' => 'sinergi/browser-detector/src',
    ];

    public function __construct()
    {
        $this->addPsr4Dependencies();
    }

    /**
     * Iterates over self::VENDOR_DEPENDENCIES_PSR4 to register available namespaces.
     */
    private function addPsr4Dependencies(): void
    {
        $vendorDir = __DIR__ . '/../../../vendor/';

        foreach (self::VENDOR_DEPENDENCIES_PSR4 as $prefix => $relativePath) {
            $path = $vendorDir . $relativePath;

            if (!file_exists($path)) {
                continue;
            }

            $this->addPsr4($prefix, $path);
        }
    }
}
