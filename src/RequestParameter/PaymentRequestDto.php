<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class PaymentRequestDto extends AbstractRequestDto
{
    public function __construct(
        public PaymentTransactionDto $paymentTransaction,
        public RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        public Cart $cart,
        PaymentHandlerInterface $paymentHandler,
        public string $action = '',
        bool $clientApiRequest = false,
        public bool $isTest = false,
    ) {
        parent::__construct($salesChannelContext, $paymentHandler, $clientApiRequest);
    }
}
