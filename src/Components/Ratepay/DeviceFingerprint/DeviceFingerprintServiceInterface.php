<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\DeviceFingerprint;

interface DeviceFingerprintServiceInterface
{
    public const SESSION_VAR_NAME = 'payone_ratepay_device_ident_token';

    public function isDeviceIdentTokenAlreadyGenerated(): bool;

    public function getDeviceIdentToken(): ?string;

    public function deleteDeviceIdentToken(): void;

    public function getDeviceIdentSnippet(string $snippetId, string $deviceIdentToken): string;
}
