<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Installer\CustomFieldInstaller;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class CaptureRequest
{
    private const CAPTUREMODE_COMPLETED  = 'completed';
    private const CAPTUREMODE_INCOMPLETE = 'notcompleted';

    /** @var EntityRepositoryInterface */
    private $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function getRequestParameters(OrderEntity $order, Context $context, array $customFields, float $totalAmount = null, bool $completed = false): array
    {
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

        $currency = $this->getOrderCurrency($order, $context);

        $parameters = [
            'request'        => 'capture',
            'txid'           => $customFields[CustomFieldInstaller::TRANSACTION_ID],
            'sequencenumber' => $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] + 1,
            'amount'         => (int) round($totalAmount * (10 ** $currency->getDecimalPrecision())),
            'currency'       => $currency->getIsoCode(),
            'capturemode'    => $completed ? self::CAPTUREMODE_COMPLETED : self::CAPTUREMODE_INCOMPLETE,
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

    private function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }
}
