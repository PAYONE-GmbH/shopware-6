<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\PaymentFilter\Exception\PaymentMethodNotAllowedException;
use PayonePayment\Core\Utils\AddressCompare;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PayoneBNPLPaymentMethodFilter extends DefaultPaymentFilterService
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly string $paymentHandlerClass,
        ?array $allowedCountries = null,
        ?array $allowedB2bCountries = null,
        ?array $allowedCurrencies = null,
        float $allowedMinValue = 0.0,
        ?float $allowedMaxValue = null,
    ) {
        parent::__construct(
            $paymentHandlerClass,
            $allowedCountries,
            $allowedB2bCountries,
            $allowedCurrencies,
            $allowedMinValue,
            $allowedMaxValue
        );
    }

    protected function additionalChecks(PaymentMethodCollection $methodCollection, PaymentFilterContext $filterContext): void
    {
        $differentShippingAddressAllowed = (bool) $this->systemConfigService->get(
            ConfigReader::getConfigKeyByPaymentHandler($this->paymentHandlerClass, 'AllowDifferentShippingAddress'),
            $filterContext->getSalesChannelContext()->getSalesChannelId()
        );

        if ($differentShippingAddressAllowed) {
            return;
        }

        $billingAddress = $filterContext->getBillingAddress();
        $shippingAddress = $filterContext->getShippingAddress();

        if ($billingAddress instanceof OrderAddressEntity
            && $shippingAddress instanceof OrderAddressEntity
            && !AddressCompare::areOrderAddressesIdentical($billingAddress, $shippingAddress)) {
            throw new PaymentMethodNotAllowedException('Different billing and shipping addresses are not allowed for secured invoice');
        } elseif ($billingAddress instanceof CustomerAddressEntity
            && $shippingAddress instanceof CustomerAddressEntity
            && !AddressCompare::areCustomerAddressesIdentical($billingAddress, $shippingAddress)) {
            throw new PaymentMethodNotAllowedException('Different billing and shipping addresses are not allowed for secured invoice');
        }
    }
}
