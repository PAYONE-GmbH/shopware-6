<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Provider\GooglePay\PaymentMethod\StandardPaymentMethod;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

enum CardNetwork: string
{
    case MASTERCARD = 'MASTERCARD';
    case VISA       = 'VISA';
}

readonly class ButtonConfiguration
{
    public function __construct(
        private ConfigReader $configReader,
    ) {
    }

    public function getButtonConfiguration(
        SalesChannelContext $salesChannelContext,
    ): ArrayStruct {
        $config = $this->configReader->read($salesChannelContext->getSalesChannelId());

        $merchantId = $config->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_MERCHANT_ID,
            StandardPaymentMethod::getConfigurationPrefix(),
            $config->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID),
        );

        return new ArrayStruct([
            'environment'                  => 'test' === $config->get('transactionMode') ? 'TEST' : 'PRODUCTION',
            'merchantId'                   => $merchantId,
            'googlePayMerchantId'          => $config->get('googlePayMerchantId'),
            'googlePayMerchantName'        => $config->get('googlePayMerchantName'),
            'googlePayAllowedCardNetworks' => \array_map(
                static fn(CardNetwork $enum): string => $enum->value,
                CardNetwork::cases(),
            ),
        ]);
    }
}