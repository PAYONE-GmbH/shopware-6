<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\DeviceFingerprintTrait;
use PayonePayment\PaymentHandler\IsCapturableTrait;
use PayonePayment\PaymentHandler\IsRefundableTrait;
use PayonePayment\PaymentHandler\NonRedirectResponseTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Provider\Ratepay\PaymentMethod\InvoicePaymentMethod;
use PayonePayment\Provider\Ratepay\ResponseHandler\InvoiceResponseHandler;
use PayonePayment\Provider\Ratepay\Service\RatepayDeviceFingerprintService;
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

    use DeviceFingerprintTrait;
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
        RatepayDeviceFingerprintService $deviceFingerprintService,
    ) {
        $this->responseHandler          = $responseHandler;
        $this->requestEnricherChain     = $requestEnricherChain;
        $this->deviceFingerprintService = $deviceFingerprintService;
    }

    #[\Override]
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    #[\Override]
    public function getConfigKeyPrefix(): string
    {
        return InvoicePaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
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
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        $definitions[RequestConstantsEnum::BIRTHDAY->value] = [ new NotBlank(), new Birthday() ];

        return $definitions;
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return InvoicePaymentMethod::getId();
    }
}
