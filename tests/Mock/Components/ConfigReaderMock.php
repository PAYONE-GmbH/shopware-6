<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Components;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Payone\Struct\Configuration;

class ConfigReaderMock implements ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true): Configuration
    {
        return new Configuration([]);
    }
}
