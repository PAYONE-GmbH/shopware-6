<?php

declare(strict_types = 1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;

class CaptureRequestFactory extends AbstractRequestFactory
{
    public const FULL_CAPTURE   = 0;
    public const AMOUNT_CAPTURE = 1;
    public const LINE_CAPTURE   = 2;

    /** @var CaptureRequest */
    private $captureRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CaptureRequest $captureRequest, SystemRequest $systemRequest)
    {
        $this->captureRequest = $captureRequest;
        $this->systemRequest  = $systemRequest;
    }

    public function getFullRequest(PaymentTransaction $transaction, Context $context): array
    {
        $this->requests[] = $this->getBaseCaptureParameters(
            $transaction->getOrder()->getSalesChannelId(),
            $transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier(),
            $context
        );

        $this->requests[] = $this->captureRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields()
        );

        return $this->createRequest();
    }

    public function getPartialRequest(float $totalAmount, PaymentTransaction $transaction, Context $context): array
    {
        $this->requests[] = $this->getBaseCaptureParameters(
            $transaction->getOrder()->getSalesChannelId(),
            $transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier(),
            $context
        );

        $this->requests[] = $this->captureRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields(),
            $totalAmount
        );

        return $this->createRequest();
    }

    protected function getBaseCaptureParameters(string $salesChannelId, string $paymentMethodIdentifier, Context $context): array
    {
        return $this->systemRequest->getRequestParameters(
            $salesChannelId,
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethodIdentifier],
            $context
        );
    }
}
