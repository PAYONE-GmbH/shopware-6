<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

interface PayonePaymentHandlerInterface
{
    public function supports(string $paymentMethodId): bool;
    
    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array; 
    
    /**
     * Called from the administration controllers to verify if a transaction can be captured.
     */
    public static function isCapturable(array $transactionData, array $customFields): bool;

    /**
     * Called from the administration controllers to verify if a transaction can be refunded.
     */
    public static function isRefundable(array $transactionData, array $customFields): bool;
}
