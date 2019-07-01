<?php

declare(strict_types=1);

namespace PayonePayment\Components\ConfigReader;

use PayonePayment\Payone\Struct\Configuration;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true): Configuration;
}
