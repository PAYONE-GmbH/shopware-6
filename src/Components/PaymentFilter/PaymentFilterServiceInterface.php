<?php declare(strict_types=1);

namespace PayonePayment\Components\PaymentFilter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;

interface PaymentFilterServiceInterface
{
    /**
     * @param CustomerAddressEntity|OrderAddressEntity|null $billingAddress
     * @param CustomerAddressEntity|OrderAddressEntity|null $shippingAddress
     */
    public function filterPaymentMethods(PaymentMethodCollection $methodCollection, string $currencyIso, $billingAddress = null, $shippingAddress = null): PaymentMethodCollection;

    /**
     * @param AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function filterPaymentMethodsAdditionalCheck(PaymentMethodCollection $methodCollection, PageLoadedEvent $event): PaymentMethodCollection;
}
