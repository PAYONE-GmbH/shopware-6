<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Refund;

use PayonePayment\Components\DependencyInjection\Factory\RequestBuilderFactory;
use PayonePayment\Components\Exception\NoRequestBuilderFoundException;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class RefundRequestFactory extends AbstractRequestFactory
{
    /** @var SystemRequest */
    private $systemRequest;

    /** @var RefundRequest */
    private $refundRequest;

    /** @var RequestBuilderFactory */
    private $requestBuilderFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        SystemRequest $systemRequest,
        RefundRequest $refundRequest,
        RequestBuilderFactory $requestBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->systemRequest         = $systemRequest;
        $this->refundRequest         = $refundRequest;
        $this->requestBuilderFactory = $requestBuilderFactory;
        $this->logger                = $logger;
    }

    public function getRequest(PaymentTransaction $transaction, ParameterBag $parameterBag, Context $context): array
    {
        if (null !== $transaction->getOrderTransaction()->getPaymentMethod()) {
            $this->requests[] = $this->systemRequest->getRequestParameters(
                $transaction->getOrder()->getSalesChannelId(),
                ConfigurationPrefixes::CONFIGURATION_PREFIXES[$transaction->getOrderTransaction()->getPaymentMethod()->getHandlerIdentifier()],
                $context
            );
        }

        $this->requests[] = $this->refundRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields(),
            (float) $parameterBag->get('amount')
        );

        try {
            $requestHandler = $this->requestBuilderFactory->getRequestBuilder(
                $transaction->getOrderTransaction()->getPaymentMethodId(),
                $transaction->getOrder()->getOrderNumber()
            );

            $this->requests[] = $requestHandler->getAdditionalRequestParameters($transaction, $context, $parameterBag);
        } catch (NoRequestBuilderFoundException $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $this->createRequest();
    }
}
