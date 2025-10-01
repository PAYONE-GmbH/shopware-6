<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\ResponseHandler\HandleSynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecuredInvoiceResponseHandler implements ResponseHandlerInterface
{
    use HandleSynchronousResponseTrait;

    public function __construct(
        TranslatorInterface $translator,
        TransactionDataHandler $transactionDataHandler,
    ) {
        $this->translator             = $translator;
        $this->transactionDataHandler = $transactionDataHandler;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'clearingType'        => PayoneClearingEnum::FINANCING->value,
            'financingType'       => PayoneFinancingEnum::PIV->value,

            // Store clearing bank account information as custom field of the transaction in order to
            // use this data for payment instructions of an invoice or similar.
            // See: https://docs.payone.com/display/public/PLATFORM/How+to+use+JSON-Responses#HowtouseJSON-Responses-JSON,Clearing-Data
            'clearingBankAccount' => \array_merge(\array_filter($response['clearing']['BankAccount'] ?? []), [
                // The PAYONE transaction ID acts as intended purpose of the transfer.
                // We add this field explicitly here to make clear that the transaction ID is used
                // as payment reference in context of the prepayment.
                'Reference' => (string) $response['txid'],
            ]),
        ];
    }
}
