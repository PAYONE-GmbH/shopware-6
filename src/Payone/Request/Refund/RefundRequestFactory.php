<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Refund;

use PayonePayment\Components\DependencyInjection\Factory\RequestHandlerFactory;
use PayonePayment\Components\Exception\NoPaymentHandlerFoundException;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class RefundRequestFactory extends AbstractRequestFactory
{
    /** @var SystemRequest */
    private $systemRequest;

    /** @var RefundRequest */
    private $refundRequest;

    /** @var RequestHandlerFactory */
    private $requestHandlerFactory;

    public function __construct(
        SystemRequest $systemRequest,
        RefundRequest $refundRequest,
        RequestHandlerFactory $requestHandlerFactory
    ) {
        $this->systemRequest = $systemRequest;
        $this->refundRequest = $refundRequest;
        $this->requestHandlerFactory = $requestHandlerFactory;
    }


    public function getFullRequest(PaymentTransaction $transaction, ParameterBag $parameterBag, Context $context): array
    {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier()],
            $context
        );

        $this->requests[] = $this->refundRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields()
        );

        try {
            $this->requests[] = $this->requestHandlerFactory->getPaymentHandler(
                $transaction->getOrderTransaction()->getPaymentMethodId(),
                $transaction->getOrder()->getOrderNumber()
            )->getAdditionalRequestParameters($transaction, $context, $parameterBag);
        } catch (NoPaymentHandlerFoundException $e) {
        }

        return $this->createRequest();
    }

    public function getPartialRequest(PaymentTransaction $transaction, ParameterBag $parameterBag, Context $context): array
    {
        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier()],
            $context
        );

        $this->requests[] = $this->refundRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields(),
            (float)$parameterBag->get('amount')
        );

        try {
            $this->requests[] = $this->requestHandlerFactory->getPaymentHandler(
                $transaction->getOrderTransaction()->getPaymentMethodId(),
                $transaction->getOrder()->getOrderNumber()
            )->getAdditionalRequestParameters($transaction, $context, $parameterBag);
        } catch (NoPaymentHandlerFoundException $e) {
        }

        return $this->createRequest();
    }
}
