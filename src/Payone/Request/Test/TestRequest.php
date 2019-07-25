<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Test;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;

class TestRequest
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public function getRequestParameters(string $salesChannel, string $configurationPrefix = ''): array
    {
        $configuration = $this->configReader->read($salesChannel);

        $accountId  = $configuration->get(sprintf('%sAccountId', $configurationPrefix), $configuration->get('accountId'));
        $merchantId = $configuration->get(sprintf('%sMerchantId', $configurationPrefix), $configuration->get('merchantId'));
        $portalId   = $configuration->get(sprintf('%sPortalId', $configurationPrefix), $configuration->get('portalId'));
        $portalKey  = $configuration->get(sprintf('%sPortalKey', $configurationPrefix), $configuration->get('portalKey'));

        return [
            'aid'         => $accountId,
            'mid'         => $merchantId,
            'portalid'    => $portalId,
            'key'         => $portalKey,
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
