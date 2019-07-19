<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;

class CaptureRequestFactory extends AbstractRequestFactory
{
    /** @var CaptureRequest */
    private $captureRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(CaptureRequest $captureRequest, SystemRequest $systemRequest)
    {
        $this->captureRequest = $captureRequest;
        $this->systemRequest  = $systemRequest;
    }

    public function getRequestParameters(PaymentTransaction $transaction, Context $context): array
    {
        $this->requests[] = $this->captureRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields()
        );

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier()]
        );

        return $this->createRequest();
    }
}
