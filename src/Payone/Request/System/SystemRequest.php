<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SystemRequest
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getRequestParameters(SalesChannelEntity $salesChannel, Context $context): array
    {
        $config = $this->configReader->read($salesChannel->getId());

        return [
            'aid'         => (string) $config->get('aid'),
            'mid'         => (string) $config->get('mid'),
            'portalid'    => (string) $config->get('portalid'),
            'key'         => (string) $config->get('key'),
            'api_version' => '3.10',
            'mode'        => $config->get('mode') ?: 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
