<?php

use NetInventors\EcsSetList\SetList;
use Reinfi\EasyCodingStandard\JUnitOutputFormatter;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Contract\Console\Output\OutputFormatterInterface;

return static function (ECSConfig $config): void {
    $config->sets([ SetList::NET_INVENTORS ]);

    $baseDir = dirname(__DIR__, 2);

    $config->paths([
        $baseDir . '/src',
    ]);

    $config->skip([
        $baseDir . '/dev-ops',
        $baseDir . '/local-repository',
        $baseDir . '/src/Resources',
        $baseDir . '/vendor',
    ]);

    $config->lineEnding(PHP_EOL);
};
