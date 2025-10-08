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
use PayonePayment\Provider\Payone\Enum\CreditCardRequestParamEnum;
use PayonePayment\Provider\Payone\PaymentMethod\CreditCardPaymentMethod;
use PayonePayment\Provider\Payone\ResponseHandler\CreditCardResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreditCardPaymentHandler extends AbstractPaymentHandler
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
        CreditCardResponseHandler $responseHandler,
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
        return CreditCardPaymentMethod::getConfigurationPrefix();
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

        // Please note: this is field is only required, for that case, that the card has been already saved, but no
        // card-holder has been saved (because this field was added in a later version)
        // with that we want to make sure, that a card-holder is always present.
        // if a card holder as been already saved, the submitted value will be ignored.
        // if no card holder has been saved, and no values has been submitted, the next line will cause a validation-error
        // TODO in the far future: move this into the if-block for the case, if the card has not been saved.
        // search for the following to-do reference, to adjust the related code: TODO-card-holder-requirement
        $definitions[CreditCardRequestParamEnum::CARD_HOLDER->value] = [ new NotBlank() ];

        if (empty($dataBag->get(CreditCardRequestParamEnum::SAVED_PSEUDO_CARD_PAN->value))) {
            $definitions[CreditCardRequestParamEnum::PSEUDO_CARD_PAN->value]    = [ new NotBlank() ];
            $definitions[CreditCardRequestParamEnum::TRUNCATED_CARD_PAN->value] = [ new NotBlank() ];
            $definitions[CreditCardRequestParamEnum::CARD_EXPIRE_DATE->value]   = [ new NotBlank() ];
            $definitions[CreditCardRequestParamEnum::CARD_TYPE->value]          = [ new NotBlank() ];
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
        return CreditCardPaymentMethod::getId();
    }
}
