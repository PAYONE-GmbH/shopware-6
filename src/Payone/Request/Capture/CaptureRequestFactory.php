<?php

declare(strict_types = 1);

namespace PayonePayment\Payone\Request\Capture;

use PayonePayment\Components\DependencyInjection\Factory\PaymentHandlerFactory;
use PayonePayment\Components\Exception\NoPaymentHandlerFoundException;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureRequestFactory extends AbstractRequestFactory
{
    /** @var CaptureRequest */
    private $captureRequest;

    /** @var SystemRequest */
    private $systemRequest;

    /** @var PaymentHandlerFactory */
    private $paymentHandlerFactory;

    public function __construct(
        SystemRequest $systemRequest,
        CaptureRequest $captureRequest,
        PaymentHandlerFactory $paymentHandlerFactory
    ) {
        $this->systemRequest = $systemRequest;
        $this->captureRequest = $captureRequest;
        $this->paymentHandlerFactory = $paymentHandlerFactory;
    }


    public function getFullRequest(PaymentTransaction $transaction, ParameterBag $parameterBag, Context $context): array
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

        try {
            $this->requests[] = $this->paymentHandlerFactory->getPaymentHandler(
                $transaction->getPaymentMethodId(),
                $transaction->getOrder()->getOrderNumber()
            )->getAdditionalRequestParameters($transaction, $context, $parameterBag);
        } catch (NoPaymentHandlerFoundException $e) {
        }

        return $this->createRequest();
    }

    public function getPartialRequest(PaymentTransaction $transaction, ParameterBag $parameterBag, Context $context): array
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
            (float)$parameterBag->get('amount')
        );

        try {
            $this->requests[] = $this->paymentHandlerFactory->getPaymentHandler(
                $transaction->getPaymentMethodId(),
                $transaction->getOrder()->getOrderNumber()
            )->getAdditionalRequestParameters($transaction, $context, $parameterBag);
        } catch (NoPaymentHandlerFoundException $e) {
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
