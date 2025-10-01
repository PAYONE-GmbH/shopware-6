<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Mandate;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\ManageMandateStruct;

class ManageMandateRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /**
     * @param ManageMandateStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'request'      => RequestActionEnum::MANAGE_MANDATE->value,
            'clearingtype' => PayoneClearingEnum::DEBIT->value,
            'iban'         => $arguments->getIban(),
            'bic'          => $arguments->getBic(),
            'currency'     => $arguments->getSalesChannelContext()->getCurrency()->getIsoCode(),
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof ManageMandateStruct;
    }
}
