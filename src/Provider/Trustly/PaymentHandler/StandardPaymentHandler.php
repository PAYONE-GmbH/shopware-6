<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Trustly\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\AuthorizationTypeEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicRedirectResponseTrait;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Trustly\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\Trustly\ResponseHandler\StandardResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @deprecated No longer supported
 */
class StandardPaymentHandler extends AbstractPaymentHandler
{
    use BasicValidationDefinitionTrait {
        BasicValidationDefinitionTrait::getValidationDefinitions as getBasicValidationDefinitions;
    }

    use BasicRedirectResponseTrait;
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
        return RequestActionEnum::PREAUTHORIZE->value;
    }

    #[\Override]
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        $definitions['iban'] = [ new NotBlank(), new Iban() ];

        return $definitions;
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
