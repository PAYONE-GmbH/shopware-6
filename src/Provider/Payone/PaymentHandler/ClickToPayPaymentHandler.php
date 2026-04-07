<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\IsCapturableTrait;
use PayonePayment\PaymentHandler\IsRefundableTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\PaymentHandler\StatusBasedRedirectResponseTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Payone\Enum\ClickToPayRequestParamEnum;
use PayonePayment\Provider\Payone\PaymentMethod\ClickToPayPaymentMethod;
use PayonePayment\Provider\Payone\ResponseHandler\ClickToPayResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClickToPayPaymentHandler extends AbstractPaymentHandler
{
    use BasicValidationDefinitionTrait {
        BasicValidationDefinitionTrait::getValidationDefinitions as getBasicValidationDefinitions;
    }

    use FinalizeTrait;
    use IsCapturableTrait;
    use IsRefundableTrait;
    use ResponseHandlerTrait;
    use RequestEnricherChainTrait;
    use RequestDataValidateTrait;
    use StatusBasedRedirectResponseTrait;

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        ClickToPayResponseHandler $responseHandler,
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
        return ClickToPayPaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
    }

    #[\Override]
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $cardInputMode = (string) $dataBag->get(ClickToPayRequestParamEnum::CARD_INPUT_MODE->value);
        $definitions   = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        $definitions[ClickToPayRequestParamEnum::CARD_HOLDER->value] = [ new NotBlank() ];

        if (empty($dataBag->get(ClickToPayRequestParamEnum::SAVED_PSEUDO_CARD_PAN->value))) {
            if ($cardInputMode === 'clickToPay' || $cardInputMode === 'register') {
                $definitions[ClickToPayRequestParamEnum::PAYMENT_CHECKOUT_DATA->value] = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::CARD_TYPE->value]             = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::TRUNCATED_CARD_PAN->value]    = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::CARD_EXPIRE_DATE->value]      = [ new NotBlank() ];
            } else {
                $definitions[ClickToPayRequestParamEnum::PSEUDO_CARD_PAN->value]    = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::TRUNCATED_CARD_PAN->value] = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::CARD_EXPIRE_DATE->value]   = [ new NotBlank() ];
                $definitions[ClickToPayRequestParamEnum::CARD_TYPE->value]          = [ new NotBlank() ];
            }
        }

        return $definitions;
    }

    #[\Override]
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (self::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? \strtolower((string) $transactionData['txaction']) : null;

        if (TransactionActionEnum::APPOINTED->value === $txAction) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData);
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return ClickToPayPaymentMethod::getId();
    }
}
