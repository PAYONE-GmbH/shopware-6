<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentStateHandlerService
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function handleStateResponse(PaymentTransactionStruct $transaction, string|null $state): void
    {
        if (empty($state) || 'error' === $state) {
            throw PaymentException::asyncFinalizeInterrupted(
                $transaction->getOrderTransactionId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        if ('cancel' === $state) {
            throw PaymentException::customerCanceled($transaction->getOrderTransactionId(), '');
        }
    }
}
