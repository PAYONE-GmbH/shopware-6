<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use Shopware\Core\Framework\Struct\Struct;

class TestCredentialsRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param TestCredentialsStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $configuration       = $this->configReader->read($arguments->getSalesChannelId());
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$arguments->getPaymentMethod()];

        $accountId  = $configuration->getString(sprintf('%sAccountId', $configurationPrefix), $configuration->getString('accountId'));
        $merchantId = $configuration->getString(sprintf('%sMerchantId', $configurationPrefix), $configuration->getString('merchantId'));
        $portalId   = $configuration->getString(sprintf('%sPortalId', $configurationPrefix), $configuration->getString('portalId'));
        $portalKey  = $configuration->getString(sprintf('%sPortalKey', $configurationPrefix), $configuration->getString('portalKey'));

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

    /** @param TestCredentialsStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof TestCredentialsStruct)) {
            return false;
        }

        $action = $arguments->getAction();

        if ($action === self::REQUEST_ACTION_TEST) {
            return true;
        }

        return false;
    }
}
