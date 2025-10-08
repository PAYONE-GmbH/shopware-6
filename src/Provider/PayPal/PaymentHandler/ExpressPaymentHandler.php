<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\PaymentHandler;

use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\ExpressCheckout\ExpressCheckoutPaymentHandlerAwareInterface;
use PayonePayment\PaymentHandler\ExpressCheckout\ExpressCheckoutSessionEnricherChainsDto;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\GenericExpressCheckoutTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\PayPal\PaymentMethod\ExpressPaymentMethod;
use PayonePayment\Provider\PayPal\ResponseHandler\ExpressResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;

class ExpressPaymentHandler extends AbstractPaymentHandler implements ExpressCheckoutPaymentHandlerAwareInterface
{
    use FinalizeTrait;
    use GenericExpressCheckoutTrait;
    use ResponseHandlerTrait;
    use RequestDataValidateTrait;
    use RequestEnricherChainTrait;

    private ExpressCheckoutSessionEnricherChainsDto $expressCheckoutSessionEnricherChains;

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        ExpressResponseHandler $responseHandler,
        PaymentStateHandlerService $stateHandler,
        RequestParameterEnricherChain $requestEnricherChain,
        RequestParameterEnricherChain $expressSessionCreaterequestEnricherChain,
        RequestParameterEnricherChain $expressSessionGetRequestEnricherChain,
        RequestParameterEnricherChain $expressSessionUpdateRequestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
        $this->stateHandler         = $stateHandler;

        $this->expressCheckoutSessionEnricherChains = new ExpressCheckoutSessionEnricherChainsDto(
            $expressSessionCreaterequestEnricherChain,
            $expressSessionGetRequestEnricherChain,
            $expressSessionUpdateRequestEnricherChain,
        );
    }

    #[\Override]
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    #[\Override]
    public function getConfigKeyPrefix(): string
    {
        return ExpressPaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return ExpressPaymentMethod::getId();
    }

    #[\Override]
    public function getExpressCheckoutSessionEnricherChains(): ExpressCheckoutSessionEnricherChainsDto
    {
        return $this->expressCheckoutSessionEnricherChains;
    }
}
