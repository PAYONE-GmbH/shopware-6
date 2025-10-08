<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneFinancingEnum;
use PayonePayment\PaymentHandler\Enum\PayoneStateEnum;
use PayonePayment\ResponseHandler\HandleSynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallmentResponseHandler implements ResponseHandlerInterface
{
    use HandleSynchronousResponseTrait;

    public function __construct(
        TranslatorInterface $translator,
        TransactionDataHandler $transactionDataHandler,
    ) {
        $this->translator             = $translator;
        $this->transactionDataHandler = $transactionDataHandler;
    }

    #[\Override]
    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'clearingReference' => $response['clearing']['Reference'],
            'captureMode'       => PayoneStateEnum::COMPLETED->value,
            'clearingType'      => PayoneClearingEnum::FINANCING->value,
            'financingType'     => PayoneFinancingEnum::PYS->value,
        ];
    }
}
