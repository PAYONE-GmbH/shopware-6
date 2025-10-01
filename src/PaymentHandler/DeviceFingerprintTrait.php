<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;

trait DeviceFingerprintTrait
{
    protected AbstractDeviceFingerprintService $deviceFingerprintService;

    public function getDeviceFingerprintService(): AbstractDeviceFingerprintService
    {
        return $this->deviceFingerprintService;
    }
}
