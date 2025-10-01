<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;

interface DeviceFingerprintAwareInterface
{
    public function getDeviceFingerprintService(): AbstractDeviceFingerprintService;
}
