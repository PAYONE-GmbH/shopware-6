<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractDeviceFingerprintService
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return array<class-string<AbstractPayonePaymentHandler>>
     */
    abstract public function getSupportedPaymentHandlerClasses(): array;

    abstract public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string;

    public function getDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionValue = $this->requestStack->getSession()->get($this->getSessionVarName());

        if ($sessionValue) {
            $token = $sessionValue;
        } else {
            $token = $this->buildDeviceIdentToken($salesChannelContext);
            $this->requestStack->getSession()->set($this->getSessionVarName(), $token);
        }

        return $token;
    }

    public function isDeviceIdentTokenAlreadyGenerated(): bool
    {
        return $this->requestStack->getSession()->get($this->getSessionVarName()) !== null;
    }

    public function deleteDeviceIdentToken(): void
    {
        $this->requestStack->getSession()->remove($this->getSessionVarName());
    }

    abstract protected function getSessionVarName(): string;

    abstract protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string;
}
