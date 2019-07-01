<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\System;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;

class SystemRequest
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getRequestParameters(string $salesChannel): array
    {
        $configuration = $this->configReader->read($salesChannel);

        return [
            'aid'         => $configuration->get('accountId'),
            'mid'         => $configuration->get('merchantId'),
            'portalid'    => $configuration->get('portalId'),
            'key'         => $configuration->get('portalKey'),
            'api_version' => '3.10',
            'mode'        => $configuration->get('transactionMode'),
            'encoding'    => 'UTF-8',
        ];
    }
}
