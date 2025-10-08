<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\PaymentHandler;

use Doctrine\DBAL\Connection;
use PayonePayment\Components\TransactionStatus\Enum\AuthorizationTypeEnum;
use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestDataValidateTrait;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Provider\AmazonPay\ButtonConfiguration;
use PayonePayment\Provider\AmazonPay\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\AmazonPay\ResponseHandler\StandardResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\NotBlank;

class StandardPaymentHandler extends AbstractPaymentHandler
{
    use BasicValidationDefinitionTrait {
        BasicValidationDefinitionTrait::getValidationDefinitions as getBasicValidationDefinitions;
    }

    use FinalizeTrait;
    use RequestDataValidateTrait;
    use RequestEnricherChainTrait;
    use ResponseHandlerTrait;

    private Serializer $serializer;

    public function __construct(
        private readonly ButtonConfiguration $buttonConfiguration,
        private readonly Connection $connection,
        private readonly RouterInterface $router,
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        StandardResponseHandler $responseHandler,
        PaymentStateHandlerService $stateHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
        $this->stateHandler         = $stateHandler;
        $this->serializer           = new Serializer(encoders: [ new JsonEncoder() ]);
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
    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = $this->getBasicValidationDefinitions($dataBag, $salesChannelContext);

        if (empty($salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getPhoneNumber())) {
            $definitions[RequestConstantsEnum::PHONE->value] = [ new NotBlank() ];
        }

        return $definitions;
    }

    #[\Override]
    public function getRedirectResponse(
        SalesChannelContext $context,
        array $request,
        array $response,
    ): RedirectResponse {
        $struct = $this->buttonConfiguration->getButtonConfiguration(
            $context,
            'Cart',
            $response['addpaydata'],
            false,
        );

        $uuid = Uuid::randomHex();

        $this->connection->executeQuery('INSERT INTO payone_amazon_redirect VALUES(?,?)', [
            Uuid::fromHexToBytes($uuid),
            $this->serializer->encode($struct->all(), JsonEncoder::FORMAT),
        ]);

        return new RedirectResponse($this->router->generate('payment.payone_redirect.amazon', [
            'uuid' => $uuid,
        ]));
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return StandardPaymentMethod::getId();
    }
}
