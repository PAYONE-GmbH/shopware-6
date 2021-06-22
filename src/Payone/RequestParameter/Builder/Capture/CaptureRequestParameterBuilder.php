<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Capture;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\CaptureStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyEntity;

class CaptureRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    private const CAPTUREMODE_COMPLETED  = 'completed';
    private const CAPTUREMODE_INCOMPLETE = 'notcompleted';

    /** @param FinancialTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $totalAmount  = $arguments->getRequestData()->get('amount');
        $order        = $arguments->getPaymentTransaction()->getOrder();
        $customFields = $arguments->getPaymentTransaction()->getCustomFields();
        $isCompleted = $arguments->getRequestData()->get('complete', false);

        if ($totalAmount === null) {
            $totalAmount = $order->getAmountTotal();
        }

        if (empty($customFields[CustomFieldInstaller::TRANSACTION_ID])) {
            throw new InvalidOrderException($order->getId());
        }

        if ($customFields[CustomFieldInstaller::SEQUENCE_NUMBER] === null || $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] === '') {
            throw new InvalidOrderException($order->getId());
        }

        if ($customFields[CustomFieldInstaller::SEQUENCE_NUMBER] < 0) {
            throw new InvalidOrderException($order->getId());
        }

        /** @var CurrencyEntity $currency */
        $currency = $order->getCurrency();

        $parameters = [
            'request'        => 'capture',
            'txid'           => $customFields[CustomFieldInstaller::TRANSACTION_ID],
            'sequencenumber' => $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] + 1,
            'amount'         => $this->getConvertedAmount((float) $totalAmount, $currency->getDecimalPrecision()),
            'currency'       => $currency->getIsoCode(),
            'capturemode'    => $isCompleted ? self::CAPTUREMODE_COMPLETED : self::CAPTUREMODE_INCOMPLETE,
        ];

        if (!empty($customFields[CustomFieldInstaller::WORK_ORDER_ID])) {
            $parameters['workorderid'] = $customFields[CustomFieldInstaller::WORK_ORDER_ID];
        }

        if (!empty($customFields[CustomFieldInstaller::CAPTURE_MODE])) {
            $parameters['capturemode'] = $customFields[CustomFieldInstaller::CAPTURE_MODE];
        }

        if (!empty($customFields[CustomFieldInstaller::CLEARING_TYPE])) {
            $parameters['clearingtype'] = $customFields[CustomFieldInstaller::CLEARING_TYPE];
        }

        return $parameters;
    }

    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof FinancialTransactionStruct)) {
            return false;
        }

        if($arguments->getAction() === self::REQUEST_ACTION_CAPTURE) {
            return true;
        }

        return false;
    }
}
