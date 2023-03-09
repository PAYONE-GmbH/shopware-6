<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PayonePayment\Components\DeviceFingerprint\DeviceFingerprintServiceCollection
 */
class DeviceFingerprintServiceCollectionTest extends TestCase
{
    public function testItInitializesCollectionAndReturnsServiceForPaymentHandler(): void
    {
        $service = $this->createMock(AbstractDeviceFingerprintService::class);
        $service->expects(static::once())->method('getSupportedPaymentHandlerClasses')->willReturn([
            PayoneDebitPaymentHandler::class,
        ]);

        $collection = new DeviceFingerprintServiceCollection([$service]);

        static::assertSame($service, $collection->getForPaymentHandler(PayoneDebitPaymentHandler::class));
    }
}
