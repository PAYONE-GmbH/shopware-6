<?php

declare(strict_types=1);

namespace PayonePayment\Provider\GooglePay;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\Provider\GooglePay\PaymentMethod\StandardPaymentMethod;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\Country\CountryEntity;
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

        return new ArrayStruct([
            'environment'                  => 'test' === $config->get('transactionMode') ? 'TEST' : 'PRODUCTION',
            'merchantId'                   => $merchantId,
            'googlePayMerchantId'          => $config->get('googlePayGoogleMerchantId'), // @TODO: is never used - do we need that?
            'googlePayMerchantName'        => $config->get('googlePayGoogleMerchantName'),
            'googlePayAllowedCardNetworks' => \array_map(
                static fn(CardNetwork $enum): string => $enum->value,
                CardNetwork::cases(),
            ),
            'countryCode'                  => $country->getIso(),
            'currencyCode'                 => $salesChannelContext->getCurrency()->getShortName(),
            'totalPrice'                   => $cart->getPrice()->getTotalPrice(),
        ]);
    }
}
