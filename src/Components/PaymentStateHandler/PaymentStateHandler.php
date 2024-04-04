<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentStateHandler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentStateHandler implements PaymentStateHandlerInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function handleStateResponse(AsyncPaymentTransactionStruct $transaction, string|null $state): void
    {
        if (empty($state) || $state === 'error') {
            if (class_exists(PaymentException::class)) {
                throw PaymentException::asyncFinalizeInterrupted($transaction->getOrderTransaction()->getId(), $this->translator->trans('PayonePayment.errorMessages.genericError'));
            } elseif (class_exists(AsyncPaymentFinalizeException::class)) {
                // required for shopware version <= 6.5.3
                throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $this->translator->trans('PayonePayment.errorMessages.genericError'));  // @phpstan-ignore-line
            }

            // should never occur, just to be safe. - this will lead into a fatal error. The user can not keep going with processing the order.
            throw new \RuntimeException('payment finalize was interrupted ' . $transaction->getOrderTransaction()->getId());
        }

        if ($state === 'cancel') {
            if (class_exists(PaymentException::class)) {
                throw PaymentException::customerCanceled($transaction->getOrderTransaction()->getId(), '');
            } elseif (class_exists(CustomerCanceledAsyncPaymentException::class)) {
                // required for shopware version <= 6.5.3
                throw new CustomerCanceledAsyncPaymentException($transaction->getOrderTransaction()->getId(), '');  // @phpstan-ignore-line
            }

            // should never occur, just to be safe. - this will lead into a fatal error. The user can not keep going with processing the order.
            throw new \RuntimeException('payment was canceled by the customer ' . $transaction->getOrderTransaction()->getId());
        }
    }
}
