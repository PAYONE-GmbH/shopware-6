<?php

declare(strict_types=1);

namespace PayonePayment\ConfigWriter;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigCollection;

interface ConfigWriterInterface
{
    public function write(string $key, string $value, string $salesChannelId = ''): void;
}