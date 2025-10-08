<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\RequestParameter\AbstractRequestDto;

readonly class ProfileRequestDto extends AbstractRequestDto
{
    public function __construct(
        PaymentHandlerInterface $paymentHandler,
        bool $clientApiRequest = false,
        public string|null $salesChannelId,
        public string $shopId,
        public string $currency,
    ) {
        parent::__construct(null, $paymentHandler, $clientApiRequest);
    }

    #[\Override]
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }
}
