<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInstallment;

use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInstallmentCalculationRequest
{
    public function getRequestParameters(
        Cart $cart,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $currency = $context->getCurrency();

        $parameters = [
            'request'             => 'genericpayment',
            'add_paydata[action]' => 'calculation',
            'clearingtype'        => 'fnc',
            'financingtype'       => 'PYS',
            'amount'              => (int) round(($cart->getPrice()->getTotalPrice() * (10 ** $currency->getDecimalPrecision()))),
            'currency'            => $currency->getIsoCode(),
        ];

        if (!empty($dataBag->get('workorder'))) {
            $parameters['workorderid'] = $dataBag->get('workorder');
        }

        $customer = $context->getCustomer();

        if (null === $customer || null === $customer->getActiveBillingAddress()) {
            throw new RuntimeException('missing order customer billing address');
        }

        return $parameters;
    }
}
