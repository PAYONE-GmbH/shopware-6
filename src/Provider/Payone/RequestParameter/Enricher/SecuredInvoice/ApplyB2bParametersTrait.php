<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher\SecuredInvoice;

use Shopware\Core\Checkout\Order\OrderEntity;

trait ApplyB2bParametersTrait
{
    protected function applyB2bParameters(OrderEntity $order, array &$parameters): void
    {
        $billingAddress = $order->getAddresses()?->get($order->getBillingAddressId());

        if (null === $billingAddress) {
            return;
        }

        $company = $billingAddress->getCompany();

        if (null === $company) {
            return;
        }

        $parameters['businessrelation'] = 'b2b';
        $parameters['company']          = $company;

        if ($billingAddress->getVatId()) {
            $parameters['vatid'] = $billingAddress->getVatId();

            return;
        }

        $vatIds = $order->getOrderCustomer()?->getVatIds();

        if (\is_array($vatIds) && isset($vatIds[0])) {
            $parameters['vatid'] = $vatIds[0];
        }
    }
}
