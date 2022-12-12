<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

interface DeviceFingerprintServiceCollectionInterface
{
    public function getForPaymentHandler(string $paymentHandlerClass): ?AbstractDeviceFingerprintService;
}
