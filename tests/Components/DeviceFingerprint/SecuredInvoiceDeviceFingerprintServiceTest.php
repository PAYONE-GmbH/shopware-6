<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\Struct\Configuration;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @covers \PayonePayment\Components\DeviceFingerprint\SecuredInvoiceDeviceFingerprintService
 */
class SecuredInvoiceDeviceFingerprintServiceTest extends AbstractDeviceFingerprintServiceTest
{
    protected function getDeviceFingerprintServiceClass(): string
    {
        return SecuredInvoiceDeviceFingerprintService::class;
    }

    protected function getSupportedPaymentHandlerClass(): string
    {
        return PayoneSecuredInvoicePaymentHandler::class;
    }

    protected function getDeviceFingerprintService(SessionInterface $session): AbstractDeviceFingerprintService
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getByPrefix')->willReturn('the-merchant-id');
        $configuration->method('get')->willReturn('test');

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->method('read')->willReturn($configuration);

        return new SecuredInvoiceDeviceFingerprintService($session, $configReader);
    }

    protected function getExpectedSnippet(string $token): string
    {
        return '<script id="paylaDcs" type="text/javascript" src="<https://d.payla.io/dcs/e7yeryF2of8X/the-merchant-id/dcs.js"></script>
             <script>
                var paylaDcsT = paylaDcs.init("t", "' . $token . '");
             </script>
            
             <link id="paylaDcsCss" type="text/css" rel="stylesheet" href="<https://d.payla.io/dcs/dcs.css?st=' . $token . '&pi=e7yeryF2of8X&psi=the-merchant-id&e=t">';
    }
}
