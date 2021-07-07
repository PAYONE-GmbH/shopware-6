<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Mandate;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\GetFileStruct;

class GetFileRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param GetFileStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        return [
            'request'        => 'getfile',
            'file_reference' => $arguments->getIdentification(),
            'file_type'      => 'SEPA_MANDATE',
            'file_format'    => 'PDF',
        ];
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if ($arguments instanceof GetFileStruct) {
            return true;
        }

        return false;
    }
}
