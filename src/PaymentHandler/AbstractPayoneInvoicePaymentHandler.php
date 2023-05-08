<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

abstract class AbstractPayoneInvoicePaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'captureMode' => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,

            // Set clearing type explicitly
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_REC,

            // Store clearing bank account information as custom field of the transaction in order to
            // use this data for payment instructions of an invoice or similar.
            // See: https://docs.payone.com/display/public/PLATFORM/How+to+use+JSON-Responses#HowtouseJSON-Responses-JSON,Clearing-Data
            'clearingBankAccount' => array_merge(array_filter($response['clearing']['BankAccount'] ?? []), [
                // The PAYONE transaction ID acts as intended purpose of the transfer.
                // We add this field explicitly here to make clear that the transaction ID is used
                // as payment reference in context of the prepayment.
                'Reference' => (string) $response['txid'],
            ]),
        ];
    }
}
