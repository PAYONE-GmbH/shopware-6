<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PaypalExpress;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaypalExpressSetCheckoutRequestFactory extends AbstractRequestFactory
{
    /** @var PaypalExpressSetCheckoutRequest */
    private $expressCheckoutRequest;

    /** @var SystemRequest */
    private $systemRequest;

    public function __construct(
        PaypalExpressSetCheckoutRequest $expressCheckoutRequest,
        SystemRequest $systemRequest
    ) {
        $this->expressCheckoutRequest = $expressCheckoutRequest;
        $this->systemRequest          = $systemRequest;
    }

    public function getRequestParameters(
        Cart $cart,
        SalesChannelContext $context,
        string $returnUrl
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $context->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYPAL_EXPRESS,
            $context->getContext()
        );

        $this->requests[] = $this->expressCheckoutRequest->getRequestParameters(
            $cart,
            $context->getContext(),
            $returnUrl
        );

        return $this->createRequest();
    }
}
