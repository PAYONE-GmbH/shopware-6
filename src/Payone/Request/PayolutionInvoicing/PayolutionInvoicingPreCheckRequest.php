<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PayolutionInvoicing;

use DateTime;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayolutionInvoicingPreCheckRequest
{
    public function getRequestParameters(
        Cart $cart,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $currency = $context->getCurrency();

        $parameters = [
            'request'                   => 'genericpayment',
            'add_paydata[action]'       => 'pre_check',
            'add_paydata[payment_type]' => 'Payolution-Invoicing',
            'clearingtype'              => 'fnc',
            'financingtype'             => 'PYV',
            'amount'                    => (int) round(($cart->getPrice()->getTotalPrice() * (10 ** $currency->getDecimalPrecision()))),
            'currency'                  => $currency->getIsoCode(),
        ];

        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return array_filter($parameters);
    }
}
