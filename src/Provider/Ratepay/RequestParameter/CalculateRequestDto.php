<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\RequestParameter;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Provider\Ratepay\Struct\Profile;
use PayonePayment\RequestParameter\AbstractRequestDto;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class CalculateRequestDto extends AbstractRequestDto
{
    public function __construct(
        SalesChannelContext $salesChannelContext,
        PaymentHandlerInterface $paymentHandler,
        bool $clientApiRequest = false,
        public RequestDataBag $requestData,
        public Cart $cart,
        public Profile $profile,
    ) {
        parent::__construct($salesChannelContext, $paymentHandler, $clientApiRequest);
    }
}
