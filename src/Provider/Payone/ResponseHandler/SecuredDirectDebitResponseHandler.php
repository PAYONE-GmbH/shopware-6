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

class SecuredDirectDebitResponseHandler implements ResponseHandlerInterface
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
            'clearingType'  => PayoneClearingEnum::FINANCING->value,
            'financingType' => PayoneFinancingEnum::PDD->value,
        ];
    }
}
