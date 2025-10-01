<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\ResponseHandler\HandleGenericExpressCheckoutTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpressV2ResponseHandler implements ResponseHandlerInterface
{
    use HandleGenericExpressCheckoutTrait;

    public function __construct(
        TranslatorInterface $translator,
        TransactionDataHandler $transactionDataHandler,
    ) {
        $this->translator             = $translator;
        $this->transactionDataHandler = $transactionDataHandler;
    }
}
