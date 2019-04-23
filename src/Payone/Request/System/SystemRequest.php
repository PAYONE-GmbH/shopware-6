<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Payone\Request\RequestInterface;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class SystemRequest implements RequestInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getParentRequest(): string
    {
        return '';
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        $order = $transaction->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrderTransaction()->getOrderId());
        }

        $config = $this->configReader->read($order->getSalesChannelId());

        return [
            'aid'         => $config->get('aid'),
            'mid'         => $config->get('mid'),
            'portalid'    => $config->get('portalid'),
            'key'         => hash('md5', $config->get('key')),
            'api_version' => '3.10',
            'mode'        => $config->get('mode') ?: 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
