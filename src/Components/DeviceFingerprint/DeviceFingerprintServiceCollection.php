<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;

class DeviceFingerprintServiceCollection implements DeviceFingerprintServiceCollectionInterface
{
    /**
     * @var array<class-string<AbstractPayonePaymentHandler>, AbstractDeviceFingerprintService>
     */
    protected array $services = [];

    /**
     * @param iterable<AbstractDeviceFingerprintService> $services
     */
    public function __construct(iterable $services)
    {
        foreach ($services as $service) {
            foreach ($service->getSupportedPaymentHandlerClasses() as $paymentHandlerClass) {
                $this->services[$paymentHandlerClass] = $service;
            }
        }
    }

    public function getForPaymentHandler(string $paymentHandlerClass): ?AbstractDeviceFingerprintService
    {
        return $this->services[$paymentHandlerClass] ?? null;
    }
}
