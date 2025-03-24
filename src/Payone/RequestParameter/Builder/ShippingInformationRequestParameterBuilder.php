<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayoneAmazonPayPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalV2PaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class ShippingInformationRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    private const COUNTRIES_FOR_WHICH_A_STATE_MUST_BE_SPECIFIED = [
        'US',
        'CA',
        'CN',
        'JP',
        'MX',
        'BR',
        'AR',
        'ID',
        'TH',
        'IN',
    ];

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $salesChannelContext = $arguments->getSalesChannelContext();
        $shippingAddress = $salesChannelContext->getCustomer()?->getActiveShippingAddress();

        $parameters = [];

        if ($shippingAddress !== null) {
            $parameters = array_filter([
                'shipping_firstname' => $shippingAddress->getFirstName(),
                'shipping_lastname' => $shippingAddress->getLastName(),
                'shipping_company' => $shippingAddress->getCompany(),
                'shipping_street' => $shippingAddress->getStreet(),
                'shipping_zip' => $shippingAddress->getZipcode(),
                'shipping_city' => $shippingAddress->getCity(),
                'shipping_country' => $shippingAddress->getCountry()?->getIso(),
            ]);

            if (\in_array($shippingAddress->getCountry()?->getIso(), self::COUNTRIES_FOR_WHICH_A_STATE_MUST_BE_SPECIFIED, true)) {
                $countryStateCode = $shippingAddress->getCountryState()?->getShortCode();
                if ($countryStateCode === null) {
                    throw new \RuntimeException('missing state in shipping address');
                }

                $parts = explode('-', $countryStateCode);
                if (\count($parts) !== 2) {
                    throw new \RuntimeException('invalid country state code');
                }

                $parameters['shipping_state'] = $parts[1];
            }
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        return \in_array(
            $paymentMethod,
            [
                PayonePaydirektPaymentHandler::class,
                PayonePaypalPaymentHandler::class,
                PayonePaypalExpressPaymentHandler::class,
                PayonePaypalV2PaymentHandler::class,
                PayonePaypalV2ExpressPaymentHandler::class,
                PayonePayolutionInvoicingPaymentHandler::class,
                PayonePayolutionDebitPaymentHandler::class,
                PayonePayolutionInstallmentPaymentHandler::class,
                PayoneSecuredInvoicePaymentHandler::class,
                PayoneSecuredInstallmentPaymentHandler::class,
                PayoneSecuredDirectDebitPaymentHandler::class,
                PayoneAmazonPayPaymentHandler::class,
                PayoneAmazonPayExpressPaymentHandler::class,
            ],
            true
        );
    }
}
