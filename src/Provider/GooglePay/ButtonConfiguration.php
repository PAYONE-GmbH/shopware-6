<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Provider\GooglePay\Enum\CardNetworkEnum;
use PayonePayment\Provider\GooglePay\PaymentMethod\StandardPaymentMethod;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class ButtonConfiguration
{
    public function __construct(
        private ConfigReader $configReader,
    ) {
    }

    public function getButtonConfiguration(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
    ): ArrayStruct {
        $config = $this->configReader->read($salesChannelContext->getSalesChannelId());

        $merchantId = $config->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_MERCHANT_ID,
            StandardPaymentMethod::getConfigurationPrefix(),
            $config->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID),
        );

        $customer = $salesChannelContext->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new \RuntimeException('Customer not available');
        }

        $billingAddress = $customer->getActiveBillingAddress();
        if (!$billingAddress instanceof CustomerAddressEntity) {
            throw new \RuntimeException('No billing address available');
        }

        $country = $billingAddress->getCountry();
        if (!$country instanceof CountryEntity) {
            throw new \RuntimeException('No billing country available');
        }

        $isTest = 'test' === $config->get('transactionMode');

        return new ArrayStruct([
            'environment'                  => $isTest ? 'TEST' : 'PRODUCTION',
            'merchantId'                   => $merchantId,
            'googlePayMerchantId'          => $isTest ? null : $config->getString('googlePayGoogleMerchantId'),
            'googlePayMerchantName'        => $config->get('googlePayGoogleMerchantName'),
            'googlePayAllowedCardNetworks' => \array_map(
                static fn(CardNetworkEnum $enum): string => $enum->value,
                CardNetworkEnum::cases(),
            ),
            'countryCode'                  => $country->getIso(),
            'currencyCode'                 => $salesChannelContext->getCurrency()->getShortName(),
            'totalPrice'                   => $cart->getPrice()->getTotalPrice(),
        ]);
    }
}
