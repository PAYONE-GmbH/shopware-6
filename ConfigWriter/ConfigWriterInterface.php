<?php

declare(strict_types=1);

namespace PayonePayment\ConfigWriter;

interface ConfigWriterInterface
{
    public function write(string $key, string $value, string $salesChannelId = ''): void;
}
