<?php

declare(strict_types=1);

namespace PayonePayment\ResponseHandler;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

trait EmptyAdditionalTransactionDataTrait
{
    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [];
    }
}
