<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Przelewy24\ResponseHandler;

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

    #[\Override]
    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'transactionState' => $response['status'],
            'allowCapture'     => false,
            'allowRefund'      => false,
        ];
    }
}
