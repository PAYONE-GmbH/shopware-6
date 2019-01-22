<?php

declare(strict_types=1);

namespace PayonePayment\ConfigReader;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', string $paymentMethodId = '', string $key = '');
}