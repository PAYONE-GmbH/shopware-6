<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\ResponseHandler;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneStateEnum;
use PayonePayment\ResponseHandler\HandleSynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class InvoiceResponseHandler implements ResponseHandlerInterface
{
    use HandleSynchronousResponseTrait;

    #[\Override]
    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        // It differs depending on the authorization method
        $clearingReference = $response['addpaydata']['clearing_reference'] ?? $response['clearing']['Reference'];

        return [
            'clearingReference' => $clearingReference,
            'captureMode'       => PayoneStateEnum::COMPLETED->value,
            'clearingType'      => PayoneClearingEnum::FINANCING->value,
            'financingType'     => PayoneFinancingEnum::RPV->value,
            'additionalData'    => ['used_ratepay_shop_id' => $request['add_paydata[shop_id]']],
        ];
    }
}
