<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Eps\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\ResponseHandler\EmptyAdditionalTransactionDataTrait;
use PayonePayment\ResponseHandler\HandleAsynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StandardResponseHandler implements ResponseHandlerInterface
{
    use EmptyAdditionalTransactionDataTrait;
    use HandleAsynchronousResponseTrait;

    public function __construct(
        TranslatorInterface $translator,
        TransactionDataHandler $transactionDataHandler,
    ) {
        $this->translator             = $translator;
        $this->transactionDataHandler = $transactionDataHandler;
    }
}
