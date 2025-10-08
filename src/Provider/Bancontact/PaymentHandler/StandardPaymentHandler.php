<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Bancontact\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\AuthorizationTypeEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicRedirectResponseTrait;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Bancontact\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\Bancontact\ResponseHandler\StandardResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;

class StandardPaymentHandler extends AbstractPaymentHandler
{
    use BasicRedirectResponseTrait;
    use BasicValidationDefinitionTrait;
    use FinalizeTrait;
    use RequestDataValidateTrait;
    use RequestEnricherChainTrait;
    use ResponseHandlerTrait;

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        StandardResponseHandler $responseHandler,
        PaymentStateHandlerService $stateHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
        $this->stateHandler         = $stateHandler;
    }

    #[\Override]
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    #[\Override]
    public function getConfigKeyPrefix(): string
    {
        return StandardPaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::AUTHORIZE->value;
    }

    #[\Override]
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (AuthorizationTypeEnum::PREAUTHORIZATION->value !== $payoneTransActionData['authorizationType']) {
            return false;
        }

        return TransactionActionEnum::PAID->value === \strtolower((string) $transactionData['txaction']);
    }

    #[\Override]
    public static function isRefundable(array $transactionData): bool
    {
        if (
            0.0 !== (float) $transactionData['receivable']
            && TransactionActionEnum::CAPTURE->value === \strtolower((string) $transactionData['txaction'])
        ) {
            return true;
        }

        return TransactionActionEnum::PAID->value === \strtolower((string) $transactionData['txaction']);
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return StandardPaymentMethod::getId();
    }
}
