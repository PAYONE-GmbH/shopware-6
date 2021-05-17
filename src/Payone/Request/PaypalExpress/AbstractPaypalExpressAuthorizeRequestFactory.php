<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\PaypalExpress;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPaypalExpressAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractPaypalExpressAuthorizeRequest */
    private $paypalRequest;

    /** @var CustomerRequest */
    private $customerRequest;

    /** @var SystemRequest */
    private $systemRequest;

    /** @var CartHasherInterface */
    private $cartHasher;

    public function __construct(
        AbstractPaypalExpressAuthorizeRequest $paypalRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest,
        CartHasherInterface $cartHasher
    ) {
        $this->paypalRequest   = $paypalRequest;
        $this->customerRequest = $customerRequest;
        $this->systemRequest   = $systemRequest;
        $this->cartHasher      = $cartHasher;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_PAYPAL_EXPRESS,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $workOrderId = $this->getWorkOrderId($transaction, $dataBag, $context);

        $referenceNumber = $this->systemRequest->getReferenceNumber($transaction, true);

        $shippingAddress = $context->getCustomer() !== null ? $context->getCustomer()->getActiveShippingAddress() : null;

        $this->requests[] = $this->paypalRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $referenceNumber,
            $shippingAddress,
            $workOrderId
        );

        return $this->createRequest();
    }

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): ?string {
        $workOrderId = $dataBag->get('workorder');

        if (null === $workOrderId) {
            return null;
        }

        $cartHash = $dataBag->get('carthash');

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $workOrderId;
    }
}
