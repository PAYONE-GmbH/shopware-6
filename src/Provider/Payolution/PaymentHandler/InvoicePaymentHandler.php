<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
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
use PayonePayment\Provider\Payolution\PaymentMethod\InvoicePaymentMethod;
use PayonePayment\Provider\Payolution\ResponseHandler\InvoiceResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoicePaymentHandler extends AbstractPaymentHandler
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
        InvoiceResponseHandler $responseHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    public function getConfigKeyPrefix(): string
    {
        return InvoicePaymentMethod::getConfigurationPrefix();
    }

    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
    }

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        $definitions[RequestConstantsEnum::WORK_ORDER_ID->value] = [ new NotBlank() ];

        $definitions['payolutionConsent'] = [ new NotBlank() ];

        // if the customer has a company address, the birthday is not required
        if (null === $salesChannelContext->getCustomer()?->getDefaultBillingAddress()?->getCompany()) {
            $definitions[RequestConstantsEnum::BIRTHDAY->value] = [ new NotBlank(), new Birthday() ];
        }

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData)
            || static::matchesIsCapturableDefaults($transactionData)
        ;
    }

    public function getPaymentMethodUuid(): string
    {
        return InvoicePaymentMethod::getId();
    }
}
