<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Amazon;

use PayonePayment\PaymentHandler\PayoneAmazonPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

abstract class AbstractAmazonParameterBuilder extends AbstractRequestParameterBuilder
{
    final public const CLEARING_TYPE = parent::CLEARING_TYPE_WALLET;
    final public const WALLET_TYPE = 'AMP';

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return is_subclass_of($arguments->getPaymentMethod(), PayoneAmazonPayPaymentHandler::class);
    }
}
