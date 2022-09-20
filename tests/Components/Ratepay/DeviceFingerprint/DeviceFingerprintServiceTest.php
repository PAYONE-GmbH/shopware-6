<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\DeviceFingerprint;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @covers \PayonePayment\Components\Ratepay\DeviceFingerprint\DeviceFingerprintService
 */
class DeviceFingerprintServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsNewDeviceIdentTokenAndSetsItToSession(): void
    {
        $session                  = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = new DeviceFingerprintService($session);
        $token                    = $deviceFingerprintService->getDeviceIdentToken();

        static::assertSame($token, $session->get(DeviceFingerprintService::SESSION_VAR_NAME));
    }

    public function testItReturnsExistingDeviceIdentTokenFromSession(): void
    {
        $session = $this->getContainer()->get(SessionInterface::class);
        $session->set(DeviceFingerprintService::SESSION_VAR_NAME, 'the-device-ident-token');

        $deviceFingerprintService = new DeviceFingerprintService($session);
        $token                    = $deviceFingerprintService->getDeviceIdentToken();

        static::assertSame('the-device-ident-token', $token);
    }

    public function testItDeletesDeviceIdentTokenFromSession(): void
    {
        $session = $this->getContainer()->get(SessionInterface::class);
        $session->set(DeviceFingerprintService::SESSION_VAR_NAME, 'the-device-ident-token');

        $deviceFingerprintService = new DeviceFingerprintService($session);
        $deviceFingerprintService->deleteDeviceIdentToken();

        static::assertNull($session->get(DeviceFingerprintService::SESSION_VAR_NAME));
    }

    public function testItReturnsTrueIfDeviceIdentTokenIsAlreadyGenerated(): void
    {
        $session                  = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = new DeviceFingerprintService($session);
        $deviceFingerprintService->getDeviceIdentToken();

        static::assertTrue($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsFalseIfDeviceIdentTokenIsNotAlreadyGenerated(): void
    {
        $session                  = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = new DeviceFingerprintService($session);

        static::assertFalse($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsDeviceIdentSnippet(): void
    {
        $session                  = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = new DeviceFingerprintService($session);
        $token                    = $deviceFingerprintService->getDeviceIdentToken();
        $snippet                  = $deviceFingerprintService->getDeviceIdentSnippet('ratepay', $token);

        static::assertSame(
            '<script language="JavaScript">var di = {"v":"ratepay","t":"' . $token . '","l":"Checkout"};</script><script type="text/javascript" src="//d.ratepay.com/ratepay/di.js"></script>
             <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?v=ratepay&t=' . $token . '&l=Checkout" /></noscript>',
            $snippet
        );
    }
}
