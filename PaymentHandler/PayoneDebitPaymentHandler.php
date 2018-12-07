<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayoneDebitPaymentHandler implements PaymentHandlerInterface
{
    /** @var RepositoryInterface */
    private $transactionRepository;

    public function __construct(RepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function pay(PaymentTransactionStruct $transaction, Context $context): ?RedirectResponse
    {
        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        $data = [
            'id'                      => $transactionId,
            'orderTransactionStateId' => Defaults::ORDER_TRANSACTION_COMPLETED,
        ];

        $this->transactionRepository->update([$data], $context);
    }
}
