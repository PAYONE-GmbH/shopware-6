<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractDeviceFingerprintServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsNewDeviceIdentTokenAndSetsItToSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();
        $salesChannelContext = $this->getSalesChannelContext();
        $session = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = $this->getDeviceFingerprintService($session);
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertSame($token, $session->get($serviceClass::SESSION_VAR_NAME));
    }

    public function testItReturnsExistingDeviceIdentTokenFromSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();
        $salesChannelContext = $this->getSalesChannelContext();

        $session = $this->getContainer()->get(SessionInterface::class);
        $session->set($serviceClass::SESSION_VAR_NAME, 'the-device-ident-token');

        $deviceFingerprintService = $this->getDeviceFingerprintService($session);
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertSame('the-device-ident-token', $token);
    }

    public function testItDeletesDeviceIdentTokenFromSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();
        $session = $this->getContainer()->get(SessionInterface::class);
        $session->set($serviceClass::SESSION_VAR_NAME, 'the-device-ident-token');

        $deviceFingerprintService = $this->getDeviceFingerprintService($session);
        $deviceFingerprintService->deleteDeviceIdentToken();

        static::assertNull($session->get($serviceClass::SESSION_VAR_NAME));
    }

    public function testItReturnsTrueIfDeviceIdentTokenIsAlreadyGenerated(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $session = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = $this->getDeviceFingerprintService($session);
        $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertTrue($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsFalseIfDeviceIdentTokenIsNotAlreadyGenerated(): void
    {
        $session = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = $this->getDeviceFingerprintService($session);

        static::assertFalse($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsDeviceIdentSnippet(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $session = $this->getContainer()->get(SessionInterface::class);
        $deviceFingerprintService = $this->getDeviceFingerprintService($session);
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);
        $snippet = $deviceFingerprintService->getDeviceIdentSnippet($token, $salesChannelContext);

        static::assertSame(
            $this->getExpectedSnippet($token),
            $snippet
        );
    }

    abstract protected function getDeviceFingerprintServiceClass(): string;

    abstract protected function getSupportedPaymentHandlerClass(): string;

    abstract protected function getDeviceFingerprintService(SessionInterface $session): AbstractDeviceFingerprintService;

    abstract protected function getExpectedSnippet(string $token): string;

    protected function getSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier($this->getSupportedPaymentHandlerClass());

        return $salesChannelContext;
    }
}
