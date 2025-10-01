<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\PaymentHandler;

use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicRedirectResponseTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\IsCapturableTrait;
use PayonePayment\PaymentHandler\IsRefundableTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Provider\Klarna\Enum\FinancingTypeEnum;
use PayonePayment\Provider\Klarna\PaymentMethod\InvoicePaymentMethod;
use PayonePayment\Provider\Klarna\ResponseHandler\FinancingTypeAwareInterface;
use PayonePayment\Provider\Klarna\ResponseHandler\InvoiceResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoicePaymentHandler extends AbstractPaymentHandler implements FinancingTypeAwareInterface
{
    use BasicRedirectResponseTrait;
    use FinalizeTrait;
    use IsCapturableTrait;
    use IsRefundableTrait;
    use RequestDataValidateTrait;
    use RequestEnricherChainTrait;
    use ResponseHandlerTrait;

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        InvoiceResponseHandler $responseHandler,
        PaymentStateHandlerService $stateHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
        $this->stateHandler         = $stateHandler;
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

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (self::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return self::isTransactionAppointedAndCompleted($transactionData)
            || self::matchesIsCapturableDefaults($transactionData)
        ;
    }

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        return [
            RequestConstantsEnum::WORK_ORDER_ID->value => [ new NotBlank() ],
            RequestConstantsEnum::CART_HASH->value     => [ new NotBlank() ],
        ];
    }

    public function getPaymentMethodUuid(): string
    {
        return InvoicePaymentMethod::getId();
    }

    public function getFinancingType(): FinancingTypeEnum
    {
        return FinancingTypeEnum::INVOICE;
    }
}
