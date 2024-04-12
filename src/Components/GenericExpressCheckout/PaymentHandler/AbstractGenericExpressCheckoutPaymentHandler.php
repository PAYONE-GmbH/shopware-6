<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout\PaymentHandler;

use PayonePayment\PaymentHandler\AbstractAsynchronousPayonePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class AbstractGenericExpressCheckoutPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    protected function handleResponse(
        AsyncPaymentTransactionStruct $transaction,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext
    ): void {
        if (empty($response['status']) || $response['status'] === 'ERROR') {
            if (class_exists(PaymentException::class)) {
                throw PaymentException::asyncProcessInterrupted(
                    $transaction->getOrderTransaction()->getId(),
                    $this->translator->trans('PayonePayment.errorMessages.genericError')
                );
            } elseif (class_exists(AsyncPaymentProcessException::class)) {
                // required for shopware version <= 6.5.3
                // @phpstan-ignore-next-line
                throw new AsyncPaymentProcessException(
                    $transaction->getOrderTransaction()->getId(),
                    $this->translator->trans('PayonePayment.errorMessages.genericError')
                );
            }

            // should never occur. Just to be safe.
            throw new \RuntimeException('payment process interrupted.');
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response);

        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
    }

    protected function getRedirectResponse(SalesChannelContext $context, array $request, array $response): RedirectResponse
    {
        if (strtolower((string) $response['status']) === 'redirect') {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }
}
