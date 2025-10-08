<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Provider;
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
    #[\Override]
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $totalAmount = $arguments->getRequestData()->get('amount');
        $order       = $arguments->getPaymentTransaction()->getOrder();

        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $arguments->getPaymentTransaction()->getOrderTransaction()->getExtension(
            PayonePaymentOrderTransactionExtension::NAME,
        );

        if (null === $totalAmount) {
            $totalAmount = $order->getAmountTotal();
        }

        if (null === $transactionData) {
            throw $this->orderNotFoundException($order->getId());
        }

        if (null === $transactionData->getSequenceNumber()) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($transactionData->getSequenceNumber() < 0) {
            throw $this->orderNotFoundException($order->getId());
        }

        /** @var CurrencyEntity $currency */
        $currency = $order->getCurrency();
        $amount   = $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount((float) $totalAmount, $currency);

        $parameters = [
            'request'        => RequestActionEnum::CAPTURE->value,
            'txid'           => $transactionData->getTransactionId(),
            'sequencenumber' => $transactionData->getSequenceNumber() + 1,
            'amount'         => $amount,
            'currency'       => $currency->getIsoCode(),
            'capturemode'    => $this->getCaptureMode($arguments),
        ];

        if (!empty($transactionData->getCaptureMode())) {
            $parameters['capturemode'] = $transactionData->getCaptureMode();
        }

        if (!empty($transactionData->getClearingType())) {
            $parameters['clearingtype'] = $transactionData->getClearingType();
        }

        if (
            \in_array($arguments->getPaymentMethod(), [
                Provider\Bancontact\PaymentHandler\StandardPaymentHandler::class,
                Provider\Przelewy24\PaymentHandler\StandardPaymentHandler::class,
                Provider\WeChatPay\PaymentHandler\StandardPaymentHandler::class,
                Provider\Alipay\PaymentHandler\StandardPaymentHandler::class,
            ], true)
        ) {
            $isCompleted                 = self::CAPTUREMODE_COMPLETED === $parameters['capturemode'];
            $parameters['settleaccount'] = $isCompleted ? self::SETTLEACCOUNT_YES : self::SETTLEACCOUNT_NO;
        }

        if (\in_array($arguments->getPaymentMethod(), PaymentHandlerGroups::RATEPAY, true)) {
            $parameters['settleaccount']        = self::SETTLEACCOUNT_YES;
            $parameters['add_paydata[shop_id]'] = $transactionData->getAdditionalData()['used_ratepay_shop_id'] ?? null;
        }

        if (Provider\IDeal\PaymentHandler\StandardPaymentHandler::class === $arguments->getPaymentMethod()) {
            $parameters['settleaccount'] = self::SETTLEACCOUNT_YES;
        }

        if (
            \in_array(
                $arguments->getPaymentMethod(),
                [
                    ...PaymentHandlerGroups::BNPL,
                    ...PaymentHandlerGroups::POSTFINANCE,
                ], true)
        ) {
            unset($parameters['capturemode']);
        }

        return $parameters;
    }

    #[\Override]
    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof FinancialTransactionStruct)) {
            return false;
        }

        if (RequestActionEnum::CAPTURE->value === $arguments->getAction()) {
            return true;
        }

        return false;
    }

    /**
     * @param FinancialTransactionStruct $arguments
     */
    private function getCaptureMode(AbstractRequestParameterStruct $arguments): ?string
    {
        $isCompleted     = $arguments->getRequestData()->get('complete', false);
        $transactionData = $arguments->getPaymentTransaction()->getPayoneTransactionData();

        if (
            true === $isCompleted
            && \array_key_exists('lastRequest', $transactionData)
            && RequestActionEnum::PREAUTHORIZE->value === $transactionData['lastRequest']
            && \in_array(
                $arguments->getPaymentMethod(),
                [
                    Provider\SofortBanking\PaymentHandler\StandardPaymentHandler::class,
                    Provider\Trustly\PaymentHandler\StandardPaymentHandler::class,
                ],
                true,
            )
        ) {
            return null;
        }

        return $isCompleted ? self::CAPTUREMODE_COMPLETED : self::CAPTUREMODE_INCOMPLETE;
    }
}
