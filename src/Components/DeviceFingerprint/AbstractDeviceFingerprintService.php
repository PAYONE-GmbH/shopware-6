<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractDeviceFingerprintService
{
    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return array<class-string<AbstractPayonePaymentHandler>>
     */
    abstract public function getSupportedPaymentHandlerClasses(): array;

    abstract public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string;

    public function getDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionValue = $this->session->get($this->getSessionVarName());

        if ($sessionValue) {
            $token = $sessionValue;
        } else {
            $token = $this->buildDeviceIdentToken($salesChannelContext);
            $this->session->set($this->getSessionVarName(), $token);
        }

        return $token;
    }

    public function isDeviceIdentTokenAlreadyGenerated(): bool
    {
        return $this->session->get($this->getSessionVarName()) !== null;
    }

    public function deleteDeviceIdentToken(): void
    {
        $this->session->remove($this->getSessionVarName());
    }

    abstract protected function getSessionVarName(): string;

    abstract protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string;
}
