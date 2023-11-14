<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayoneApplePayPaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        $response = json_decode((string) $dataBag->get('response', '{}'), true, 512, \JSON_THROW_ON_ERROR);

        if ($response === null || !\array_key_exists('status', $response) || !\array_key_exists('txid', $response)) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        if ($response['status'] !== 'APPROVED' && $response['status'] !== 'PENDING') {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $paymentTransaction = PaymentTransaction::fromSyncPaymentTransactionStruct($transaction, $transaction->getOrder());

        $authorizationMethod = $this->getAuthorizationMethod(
            $transaction->getOrder()->getSalesChannelId(),
            $this->getConfigKeyPrefix() . 'AuthorizationMethod',
            $this->getDefaultAuthorizationMethod()
        );

        $request = [
            'request' => $authorizationMethod,
        ];

        $data = $this->preparePayoneOrderTransactionData($request, $response);
        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);
        $this->orderActionLogDataHandler->createOrderActionLog(
            $transaction->getOrder(),
            $request,
            $response,
            $salesChannelContext->getContext()
        );
    }

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
}
