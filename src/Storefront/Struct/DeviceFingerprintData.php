<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Struct;

use Shopware\Core\Framework\Struct\Struct;

class DeviceFingerprintData extends Struct
{
    public const EXTENSION_NAME = 'payoneDeviceFingerprint';

    protected string $snippet;

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function setSnippet(string $snippet): void
    {
        $this->snippet = $snippet;
    }
}
