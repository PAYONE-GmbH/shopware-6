<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Struct\Configuration;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @covers \PayonePayment\Components\DeviceFingerprint\RatepayDeviceFingerprintService
 */
class RatepayDeviceFingerprintServiceTest extends AbstractDeviceFingerprintServiceTest
{
    protected function getDeviceFingerprintServiceClass(): string
    {
        return RatepayDeviceFingerprintService::class;
    }

    protected function getSupportedPaymentHandlerClass(): string
    {
        return PayoneRatepayDebitPaymentHandler::class;
    }

    protected function getDeviceFingerprintService(SessionInterface $session): AbstractDeviceFingerprintService
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getByPrefix')->willReturn('ratepay');

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->method('read')->willReturn($configuration);

        return new RatepayDeviceFingerprintService($session, $configReader);
    }

    protected function getExpectedSnippet(string $token): string
    {
        return '<script language="JavaScript">var di = {"v":"ratepay","t":"' . $token . '","l":"Checkout"};</script><script type="text/javascript" src="//d.ratepay.com/ratepay/di.js"></script>
             <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?v=ratepay&t=' . $token . '&l=Checkout" /></noscript>';
    }
}
