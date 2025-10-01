<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract readonly class AbstractRequestDto
{
    public function __construct(
        public SalesChannelContext|null $salesChannelContext,
        public PaymentHandlerInterface $paymentHandler,
        public bool $clientApiRequest = false,
    ) {
    }

    public function getSalesChannelId(): string
    {
        if (null === $this->salesChannelContext) {
            throw new \RuntimeException('missing sales channel context');
        }

        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext?->getContext() ?? Context::createCLIContext();
    }
}
