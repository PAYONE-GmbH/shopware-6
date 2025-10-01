<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\PaymentHandler;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
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
use PayonePayment\Provider\Ratepay\PaymentMethod\InstallmentPaymentMethod;
use PayonePayment\Provider\Ratepay\ResponseHandler\InstallmentResponseHandler;
use PayonePayment\Provider\Ratepay\Service\RatepayDeviceFingerprintService;
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

    use DeviceFingerprintTrait;
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
        RatepayDeviceFingerprintService $deviceFingerprintService,
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
        return InstallmentPaymentMethod::getConfigurationPrefix();
    }

    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::PREAUTHORIZE->value;
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

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        if (empty($salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getPhoneNumber())) {
            $definitions[RequestConstantsEnum::PHONE->value] = [ new NotBlank() ];
        }

        $definitions[RequestConstantsEnum::BIRTHDAY->value] = [ new NotBlank(), new Birthday() ];
        $definitions['ratepayIban']                         = [ new Iban() ];

        $definitions['ratepayInstallmentAmount']     = [ new NotBlank() ];
        $definitions['ratepayInstallmentNumber']     = [ new NotBlank() ];
        $definitions['ratepayLastInstallmentAmount'] = [ new NotBlank() ];
        $definitions['ratepayInterestRate']          = [ new NotBlank() ];
        $definitions['ratepayTotalAmount']           = [ new NotBlank() ];

        return $definitions;
    }

    public function getPaymentMethodUuid(): string
    {
        return InstallmentPaymentMethod::getId();
    }
}
