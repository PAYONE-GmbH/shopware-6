<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\System\Currency\CurrencyEntity;

class CaptureRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    private const CAPTUREMODE_COMPLETED  = 'completed';
    private const CAPTUREMODE_INCOMPLETE = 'notcompleted';

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->currencyPrecision = $currencyPrecision;
    }

    /** @param FinancialTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $totalAmount = $arguments->getRequestData()->get('amount');
        $order       = $arguments->getPaymentTransaction()->getOrder();

        /** @var null|PayonePaymentOrderTransactionDataEntity $transactionData */
        $transactionData = $arguments->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);
        $isCompleted     = $arguments->getRequestData()->get('complete', false);

        if ($totalAmount === null) {
            $totalAmount = $order->getAmountTotal();
        }

        if (null === $transactionData) {
            throw new InvalidOrderException($order->getId());
        }

        if (null === $transactionData->getSequenceNumber()) {
            throw new InvalidOrderException($order->getId());
        }

        if ($transactionData->getSequenceNumber() < 0) {
            throw new InvalidOrderException($order->getId());
        }

        /** @var CurrencyEntity $currency */
        $currency = $order->getCurrency();

        $parameters = [
            'request'        => self::REQUEST_ACTION_CAPTURE,
            'txid'           => $transactionData->getTransactionId(),
            'sequencenumber' => $transactionData->getSequenceNumber() + 1,
            'amount'         => $this->currencyPrecision->getRoundedTotalAmount((float) $totalAmount, $currency),
            'currency'       => $currency->getIsoCode(),
            'capturemode'    => $isCompleted ? self::CAPTUREMODE_COMPLETED : self::CAPTUREMODE_INCOMPLETE,
        ];

        if (null !== $transactionData->getWorkOrderId()) {
            $parameters['workorderid'] = $transactionData->getWorkOrderId();
        }

        if (!empty($transactionData->getCaptureMode())) {
            $parameters['capturemode'] = $transactionData->getCaptureMode();
        }

        if (!empty($transactionData->getClearingType())) {
            $parameters['clearingtype'] = $transactionData->getClearingType();
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
}
