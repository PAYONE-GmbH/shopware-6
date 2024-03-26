<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Amazon;

use PayonePayment\PaymentHandler\PayoneAmazonPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

abstract class AbstractAmazonRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    final public const CLEARING_TYPE = parent::CLEARING_TYPE_WALLET;
    final public const WALLET_TYPE = 'AMP';
    final public const PLATFORM_ID = 'A1JKLSC6LUW5EW';

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments->getPaymentMethod() === PayoneAmazonPayPaymentHandler::class;
    }
}
