<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\IsCapturableTrait;
use PayonePayment\PaymentHandler\IsRefundableTrait;
use PayonePayment\PaymentHandler\NonRedirectResponseTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Provider\Payolution\PaymentMethod\InstallmentPaymentMethod;
use PayonePayment\Provider\Payolution\ResponseHandler\InstallmentResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class InstallmentPaymentHandler extends AbstractPaymentHandler
{
    use BasicValidationDefinitionTrait {
        BasicValidationDefinitionTrait::getValidationDefinitions as getBasicValidationDefinitions;
    }

    use IsCapturableTrait;
    use IsRefundableTrait;
    use NonRedirectResponseTrait;
    use ResponseHandlerTrait;
    use RequestDataValidateTrait;
    use RequestEnricherChainTrait;

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        InstallmentResponseHandler $responseHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
    }

    #[\Override]
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    #[\Override]
    public function getConfigKeyPrefix(): string
    {
        return InstallmentPaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::AUTHORIZE->value;
    }

    #[\Override]
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        $definitions[RequestConstantsEnum::WORK_ORDER_ID->value] = [ new NotBlank() ];
        $definitions[RequestConstantsEnum::CART_HASH->value]     = [ new NotBlank() ];

        $definitions['payolutionConsent']                   = [ new NotBlank() ];
        $definitions[RequestConstantsEnum::BIRTHDAY->value] = [ new NotBlank(), new Birthday() ];

        $definitions['payolutionInstallmentDuration'] = [ new NotBlank() ];
        $definitions['payolutionAccountOwner']        = [ new NotBlank() ];
        $definitions['payolutionIban']                = [ new NotBlank(), new Iban() ];
        $definitions['payolutionBic']                 = [ new NotBlank() ];

        return $definitions;
    }

    #[\Override]
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData)
            || static::matchesIsCapturableDefaults($transactionData)
        ;
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return InstallmentPaymentMethod::getId();
    }
}
