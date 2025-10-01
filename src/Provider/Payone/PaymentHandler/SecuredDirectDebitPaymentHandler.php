<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\DeviceFingerprintAwareInterface;
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
use PayonePayment\Provider\Payone\DeviceFingerprint\PayoneBNPLDeviceFingerprintService;
use PayonePayment\Provider\Payone\PaymentMethod\SecuredDirectDebitPaymentMethod;
use PayonePayment\Provider\Payone\ResponseHandler\SecuredDirectDebitResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class SecuredDirectDebitPaymentHandler extends AbstractPaymentHandler implements DeviceFingerprintAwareInterface
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
        SecuredDirectDebitResponseHandler $responseHandler,
        RequestParameterEnricherChain $requestEnricherChain,
        PayoneBNPLDeviceFingerprintService $deviceFingerprintService,
    ) {
        $this->responseHandler          = $responseHandler;
        $this->requestEnricherChain     = $requestEnricherChain;
        $this->deviceFingerprintService = $deviceFingerprintService;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    public function getConfigKeyPrefix(): string
    {
        return SecuredDirectDebitPaymentMethod::getConfigurationPrefix();
    }

    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
    }

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        if (empty($salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getPhoneNumber())) {
            $definitions[RequestConstantsEnum::PHONE->value] = [ new NotBlank() ];
        }

        $definitions['securedDirectDebitIban']              = [ new NotBlank(), new Iban() ];
        $definitions[RequestConstantsEnum::BIRTHDAY->value] = [ new NotBlank(), new Birthday() ];

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (self::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return self::isTransactionAppointedAndCompleted($transactionData)
            || self::matchesIsCapturableDefaults($transactionData)
        ;
    }

    public function getPaymentMethodUuid(): string
    {
        return SecuredDirectDebitPaymentMethod::getId();
    }
}
