<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractDeviceFingerprintServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsNewDeviceIdentTokenAndSetsItToSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();
        $salesChannelContext = $this->getSalesChannelContext();

        $request = $this->getRequestWithSession([]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertSame($token, $request->getSession()->get($serviceClass::SESSION_VAR_NAME));
    }

    public function testItReturnsExistingDeviceIdentTokenFromSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();
        $salesChannelContext = $this->getSalesChannelContext();

        $request = $this->getRequestWithSession([
            $serviceClass::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertSame('the-device-ident-token', $token);
    }

    public function testItDeletesDeviceIdentTokenFromSession(): void
    {
        $serviceClass = $this->getDeviceFingerprintServiceClass();

        $request = $this->getRequestWithSession([
            $serviceClass::SESSION_VAR_NAME => 'the-device-ident-token',
        ]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));
        $deviceFingerprintService->deleteDeviceIdentToken();

        static::assertNull($request->getSession()->get($serviceClass::SESSION_VAR_NAME));
    }

    public function testItReturnsTrueIfDeviceIdentTokenIsAlreadyGenerated(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $request = $this->getRequestWithSession([]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));
        $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);

        static::assertTrue($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsFalseIfDeviceIdentTokenIsNotAlreadyGenerated(): void
    {
        $request = $this->getRequestWithSession([]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));

        static::assertFalse($deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated());
    }

    public function testItReturnsDeviceIdentSnippet(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $request = $this->getRequestWithSession([]);
        $this->getContainer()->get(RequestStack::class)->push($request);

        $deviceFingerprintService = $this->getDeviceFingerprintService($this->getContainer()->get(RequestStack::class));
        $token = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);
        $snippet = $deviceFingerprintService->getDeviceIdentSnippet($token, $salesChannelContext);

        static::assertSame(
            $this->getExpectedSnippet($token),
            $snippet
        );
    }

    abstract protected function getDeviceFingerprintServiceClass(): string;

    abstract protected function getSupportedPaymentHandlerClass(): string;

    abstract protected function getDeviceFingerprintService(RequestStack $requestStack): AbstractDeviceFingerprintService;

    abstract protected function getExpectedSnippet(string $token): string;

    protected function getSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier($this->getSupportedPaymentHandlerClass());

        return $salesChannelContext;
    }
}
