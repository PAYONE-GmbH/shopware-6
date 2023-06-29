<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase\Mock;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Struct\Configuration;

class ConfigReaderMock implements ConfigReaderInterface
{
    public function __construct(private readonly array $configuration)
    {
    }

    public function read(string $salesChannelId = '', bool $fallback = true): Configuration
    {
        return new Configuration($this->configuration);
    }
}
