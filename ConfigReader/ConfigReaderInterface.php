<?php

declare(strict_types=1);

namespace PayonePayment\ConfigReader;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigCollection;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', string $key = '', bool $fallback = true): PayonePaymentConfigCollection;
}