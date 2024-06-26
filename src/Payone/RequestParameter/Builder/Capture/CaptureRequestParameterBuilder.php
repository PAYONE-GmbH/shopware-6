<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use Shopware\Core\System\Currency\CurrencyEntity;

class CaptureRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    final public const CAPTUREMODE_COMPLETED = 'completed';
    final public const CAPTUREMODE_INCOMPLETE = 'notcompleted';
    final public const SETTLEACCOUNT_YES = 'yes';
    final public const SETTLEACCOUNT_AUTO = 'auto';
    final public const SETTLEACCOUNT_NO = 'no';

    /**
     * @param FinancialTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $totalAmount = $arguments->getRequestData()->get('amount');
        $order = $arguments->getPaymentTransaction()->getOrder();

        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $arguments->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if ($totalAmount === null) {
            $totalAmount = $order->getAmountTotal();
        }

        if ($transactionData === null) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($transactionData->getSequenceNumber() === null) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($transactionData->getSequenceNumber() < 0) {
            throw $this->orderNotFoundException($order->getId());
        }

        /** @var CurrencyEntity $currency */
        $currency = $order->getCurrency();

        $parameters = [
            'request' => self::REQUEST_ACTION_CAPTURE,
            'txid' => $transactionData->getTransactionId(),
            'sequencenumber' => $transactionData->getSequenceNumber() + 1,
            'amount' => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount((float) $totalAmount, $currency),
            'currency' => $currency->getIsoCode(),
            'capturemode' => $this->getCaptureMode($arguments),
        ];

        if (!empty($transactionData->getCaptureMode())) {
            $parameters['capturemode'] = $transactionData->getCaptureMode();
        }

        if (!empty($transactionData->getClearingType())) {
            $parameters['clearingtype'] = $transactionData->getClearingType();
        }

        if (\in_array($arguments->getPaymentMethod(), [PayoneBancontactPaymentHandler::class, PayonePrzelewy24PaymentHandler::class, PayoneWeChatPayPaymentHandler::class, PayoneAlipayPaymentHandler::class], true)) {
            $isCompleted = $parameters['capturemode'] === self::CAPTUREMODE_COMPLETED;
            $parameters['settleaccount'] = $isCompleted ? self::SETTLEACCOUNT_YES : self::SETTLEACCOUNT_NO;
        }

        if (\in_array($arguments->getPaymentMethod(), PaymentHandlerGroups::RATEPAY, true)) {
            $parameters['settleaccount'] = self::SETTLEACCOUNT_YES;
            $parameters['add_paydata[shop_id]'] = $transactionData->getAdditionalData()['used_ratepay_shop_id'] ?? null;
        }

        if ($arguments->getPaymentMethod() === PayoneIDealPaymentHandler::class) {
            $parameters['settleaccount'] = self::SETTLEACCOUNT_YES;
        }

        if (\in_array($arguments->getPaymentMethod(), [...PaymentHandlerGroups::BNPL, ...PaymentHandlerGroups::POSTFINANCE], true)) {
            unset($parameters['capturemode']);
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof FinancialTransactionStruct)) {
            return false;
        }

        if ($arguments->getAction() === self::REQUEST_ACTION_CAPTURE) {
            return true;
        }

        return false;
    }

    /**
     * @param FinancialTransactionStruct $arguments
     */
    private function getCaptureMode(AbstractRequestParameterStruct $arguments): ?string
    {
        $isCompleted = $arguments->getRequestData()->get('complete', false);
        $transactionData = $arguments->getPaymentTransaction()->getPayoneTransactionData();

        if ($isCompleted === true
            && \array_key_exists('lastRequest', $transactionData)
            && $transactionData['lastRequest'] === AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE
            && \in_array($arguments->getPaymentMethod(), [PayoneSofortBankingPaymentHandler::class, PayoneTrustlyPaymentHandler::class], true)) {
            return null;
        }

        return $isCompleted ? self::CAPTUREMODE_COMPLETED : self::CAPTUREMODE_INCOMPLETE;
    }
}
