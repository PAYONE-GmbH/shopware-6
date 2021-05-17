<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Paypal;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

class PaypalPreAuthorizeRequest extends AbstractPaypalAuthorizeRequest
{
    public function getRequestParameters(
        PaymentTransaction $transaction,
        Context $context,
        string $referenceNumber,
        ?CustomerAddressEntity $shippingAddress = null,
        ?string $workOrderId = null
    ): array {
        return array_merge(parent::getRequestParameters($transaction, $context, $referenceNumber, $shippingAddress, $workOrderId), [
            'request' => 'preauthorization',
        ]);
    }
}
