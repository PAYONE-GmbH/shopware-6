<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use Doctrine\DBAL\Connection;
use PayonePayment\Components\AmazonPay\ButtonConfiguration;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneAmazonPayPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $transactionDataHandler,
        OrderActionLogDataHandlerInterface $orderActionLogDataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestParameterFactory $requestParameterFactory,
        private readonly RouterInterface $router,
        private readonly Connection $connection,
        private readonly ButtonConfiguration $buttonConfiguration,
        private readonly EncoderInterface $encoder,
        ?AbstractDeviceFingerprintService $deviceFingerprintService = null
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack, $client, $translator, $transactionDataHandler, $orderActionLogDataHandler, $stateHandler, $requestParameterFactory, $deviceFingerprintService);
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if ($payoneTransActionData['authorizationType'] !== TransactionStatusService::AUTHORIZATION_TYPE_PREAUTHORIZATION) {
            return false;
        }

        return strtolower((string)$transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        $definitions['payonePhone'] = [new NotBlank()];

        return $definitions;
    }

    public static function isRefundable(array $transactionData): bool
    {
        if ((float)$transactionData['receivable'] !== 0.0 && strtolower((string)$transactionData['txaction']) === TransactionStatusService::ACTION_CAPTURE) {
            return true;
        }

        return strtolower((string)$transactionData['txaction']) === TransactionStatusService::ACTION_PAID;
    }

    protected function getRedirectResponse(SalesChannelContext $context, array $request, array $response): RedirectResponse
    {
        $struct = $this->buttonConfiguration->getButtonConfiguration(
            $context,
            'Cart',
            $response['addpaydata'],
            false
        );

        $uuid = Uuid::randomHex();

        $this->connection->executeQuery('INSERT INTO payone_amazon_redirect VALUES(?,?)', [
            Uuid::fromHexToBytes($uuid),
            $this->encoder->encode($struct->all(), JsonEncoder::FORMAT),
        ]);

        return new RedirectResponse($this->router->generate('payment.payone_redirect.amazon', ['uuid' => $uuid]));
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'transactionState' => $response['status'],
            'allowCapture' => false,
            'allowRefund' => false,
        ];
    }
}
