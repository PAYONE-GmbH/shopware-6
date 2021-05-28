<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalAuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ): array {
        $currency        = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());
        $shippingAddress = $salesChannelContext->getCustomer() !== null ? $salesChannelContext->getCustomer()->getActiveShippingAddress() : null;

        $parameters = [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => $this->getConvertedAmount($paymentTransaction->getOrder()->getAmountTotal(), $currency->getDecimalPrecision()),
            'currency'     => $currency->getIsoCode(),
            'reference'    => $this->getReferenceNumber($paymentTransaction, true),
            'successurl'   => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=success'),
            'errorurl'     => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=error'),
            'backurl'      => $this->encodeUrl($paymentTransaction->getReturnUrl() . '&state=cancel'),
            'workorderid'  => 'TODO: set workorderid',
        ];

        if ($shippingAddress !== null) {
            $parameters = $this->applyShippingParameters($parameters, $shippingAddress);
        }

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES_BY_METHOD[$paymentMethod],
            (string) $paymentTransaction->getOrder()->getOrderNumber()
        );

        //TODO: set workorderid

        return $parameters;
    }

    public function supports(string $paymentMethod, string $action = ''): bool
    {
        return $paymentMethod === PayonePaypal::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    private function applyShippingParameters(array $parameters, CustomerAddressEntity $shippingAddress): array
    {
        $shippingParameters = array_filter([
            'shipping_firstname' => $shippingAddress->getFirstName(),
            'shipping_lastname'  => $shippingAddress->getLastName(),
            'shipping_company'   => $shippingAddress->getCompany(),
            'shipping_street'    => $shippingAddress->getStreet(),
            'shipping_zip'       => $shippingAddress->getZipcode(),
            'shipping_city'      => $shippingAddress->getCity(),
            'shipping_country'   => $shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null,
        ]);

        return array_merge($parameters, $shippingParameters);
    }
}
