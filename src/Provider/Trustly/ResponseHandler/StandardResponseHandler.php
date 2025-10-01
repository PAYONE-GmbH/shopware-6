<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Trustly\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\ResponseHandler\HandleAsynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class StandardResponseHandler implements ResponseHandlerInterface
{
    use HandleAsynchronousResponseTrait;

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
            'transactionState' => $response['status'],
            'allowCapture'     => false,
            'allowRefund'      => false,
        ];
    }
}
