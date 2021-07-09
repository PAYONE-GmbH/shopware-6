<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class ShippingInformationRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();
        $shippingAddress     = $salesChannelContext->getCustomer() !== null ? $salesChannelContext->getCustomer()->getActiveShippingAddress() : null;

        $parameters = [];

        if ($shippingAddress !== null) {
            $parameters = array_filter([
                'shipping_firstname' => $shippingAddress->getFirstName(),
                'shipping_lastname'  => $shippingAddress->getLastName(),
                'shipping_company'   => $shippingAddress->getCompany(),
                'shipping_street'    => $shippingAddress->getStreet(),
                'shipping_zip'       => $shippingAddress->getZipcode(),
                'shipping_city'      => $shippingAddress->getCity(),
                'shipping_country'   => $shippingAddress->getCountry() !== null ? $shippingAddress->getCountry()->getIso() : null,
            ]);
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        if ($paymentMethod === PayonePaydirektPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayonePaypalPaymentHandler::class) {
            return true;
        }

        if ($paymentMethod === PayonePaypalExpressPaymentHandler::class) {
            return true;
        }

        return false;
    }
}
