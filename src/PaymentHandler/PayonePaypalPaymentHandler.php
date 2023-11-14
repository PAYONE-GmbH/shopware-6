<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PayonePaypalPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
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

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
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
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response);
        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->orderActionLogDataHandler->createOrderActionLog(
            $transaction->getOrder(),
            $request,
            $response,
            $salesChannelContext->getContext()
        );
    }

    protected function getRedirectResponse(array $request, array $response): RedirectResponse
    {
        if (strtolower((string) $response['status']) === 'redirect') {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }
}
