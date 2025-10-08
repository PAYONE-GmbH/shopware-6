<?php

declare(strict_types=1);

use Frosh\Rector\Set\ShopwareSetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $baseDir = dirname(__DIR__, 2);

    $rectorConfig->paths([ $baseDir ]);

    $rectorConfig->skip([
        $baseDir . '/dev-ops',
        $baseDir . '/local-repository',
        $baseDir . '/manual',
        $baseDir . '/src/Resources',
        $baseDir . '/tests',
        $baseDir . '/vendor',
    ]);

    $rectorConfig->sets([
        ShopwareSetList::SHOPWARE_6_7_0,
    ]);
};
