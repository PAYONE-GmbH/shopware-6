<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Components\DependencyInjection\Factory\RequestBuilderFactory;
use PayonePayment\Components\Exception\NoRequestBuilderFoundException;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureRequestFactory extends AbstractRequestFactory
{
    /** @var CaptureRequest */
    private $captureRequest;

    /** @var SystemRequest */
    private $systemRequest;

    /** @var RequestBuilderFactory */
    private $requestBuilderFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        SystemRequest $systemRequest,
        CaptureRequest $captureRequest,
        RequestBuilderFactory $requestBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->systemRequest         = $systemRequest;
        $this->captureRequest        = $captureRequest;
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

        $this->requests[] = $this->captureRequest->getRequestParameters(
            $transaction->getOrder(),
            $context,
            $transaction->getCustomFields(),
            (float) $parameterBag->get('amount'),
            (bool) $parameterBag->get('complete')
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

    protected function getBaseCaptureParameters(string $salesChannelId, string $paymentMethodIdentifier, Context $context): array
    {
        return $this->systemRequest->getRequestParameters(
            $salesChannelId,
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethodIdentifier],
            $context
        );
    }
}
