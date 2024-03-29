<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractDeviceFingerprintService
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    /**
     * @return array<class-string<AbstractPayonePaymentHandler>>
     */
    abstract public function getSupportedPaymentHandlerClasses(): array;

    abstract public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string;

    public function getDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionValue = $this->getSession()?->get($this->getSessionVarName());

        if ($sessionValue) {
            $token = $sessionValue;
        } else {
            $token = $this->buildDeviceIdentToken($salesChannelContext);
            $this->getSession()?->set($this->getSessionVarName(), $token);
        }

        return $token;
    }

    public function isDeviceIdentTokenAlreadyGenerated(): bool
    {
        return $this->getSession()?->get($this->getSessionVarName()) !== null;
    }

    public function deleteDeviceIdentToken(): void
    {
        $this->getSession()?->remove($this->getSessionVarName());
    }

    abstract protected function getSessionVarName(): string;

    abstract protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string;

    protected function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}
