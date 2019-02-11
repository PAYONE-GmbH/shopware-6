<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Payone\Request\RequestInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
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
        $config = $this->configReader->read($transaction->getOrder()->getSalesChannelId());

        return [
            'aid'         => $config->get('aid') ? $config->get('aid')->getValue() : getenv('PAYONE_AID'),
            'mid'         => $config->get('mid') ? $config->get('mid')->getValue() : getenv('PAYONE_MID'),
            'portalid'    => $config->get('portalid') ? $config->get('portalid')->getValue() : getenv('PAYONE_PORTALID'),
            'key'         => hash('md5', $config->get('key') ? $config->get('key')->getValue() : getenv('PAYONE_KEY')),
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
