<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class CaptureRequest
{
    public function getRequestParameters(OrderEntity $order, array $customFields): array
    {
        if (empty($customFields[CustomFieldInstaller::TRANSACTION_ID])) {
            throw new InvalidOrderException($order->getId());
        }

        if (empty($customFields[CustomFieldInstaller::SEQUENCE_NUMBER])) {
            throw new InvalidOrderException($order->getId());
        }

        if ($customFields[CustomFieldInstaller::SEQUENCE_NUMBER] < 1) {
            throw new InvalidOrderException($order->getId());
        }

        return [
            'request'        => 'capture',
            'txid'           => $customFields[CustomFieldInstaller::TRANSACTION_ID],
            'sequencenumber' => $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] + 1,
            'amount'         => (int) ($order->getAmountTotal() * 100),
            'currency'       => $order->getCurrency()->getShortName(),
        ];
    }
}
