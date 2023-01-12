<?php declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Postfinance;

use PayonePayment\PaymentHandler\AbstractPostfinancePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;

abstract class AbstractRequestParameterBuilder extends \PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder
{
    public const ONLINEBANK_TRANSFER_TYPE_CARD = 'PFC';
    public const ONLINEBANK_TRANSFER_TYPE_WALLET = 'PFF';

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return is_subclass_of($arguments->getPaymentMethod(), AbstractPostfinancePaymentHandler::class);
    }
}
