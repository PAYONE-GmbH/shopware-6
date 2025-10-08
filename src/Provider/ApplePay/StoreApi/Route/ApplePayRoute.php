<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\StoreApi\Route;

use GuzzleHttp\Client;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\ApplePay\PaymentHandler\StandardPaymentHandler;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\OrderLoaderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'store-api' ] ])]
class ApplePayRoute extends AbstractApplePayRoute
{
    final public const CERT_FOLDER = '/config/apple-pay-cert/';

    final public const EMPTY_ORDER_ID = '--empty-order-id--';

    public function __construct(
        private readonly PaymentRequestEnricher $paymentRequestEnricher,
        private readonly RequestParameterEnricherChain $requestEnricherChain,
        private readonly StandardPaymentHandler $paymentHandler,
        private readonly CartService $cartService,
        private readonly OrderLoaderService $orderLoaderService,
        private readonly Client $httpClient,
        private readonly LoggerInterface $logger,
        private readonly PayoneClientInterface $client,
        private readonly ConfigReaderInterface $configReader,
        private readonly string $kernelDirectory,
    ) {
    }

    #[\Override]
    public function getDecorated(): AbstractApplePayRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[\Override]
    #[Route(
        path: '/store-api/payone/apple-pay/validate-merchant',
        name: 'store-api.payone.apple-pay.validate-merchant',
        defaults: [ '_contextTokenRequired' => true ],
        methods: [ 'POST' ],
    )]
    public function validateMerchant(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $defaultValidationUrl = 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession';
        $pathToCertFolder     = Path::join($this->kernelDirectory, self::CERT_FOLDER);
        $configuration        = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());
        $validationUrl        = $request->get('validationUrl', $defaultValidationUrl);
        $merchantIdCertPath   = Path::join($pathToCertFolder, 'merchant_id.pem');
        $merchantIdKeyPath    = Path::join($pathToCertFolder, 'merchant_id.key');
        $passPhrase           = $configuration->get('applePayCertPassphrase');

        $body = [
            'merchantIdentifier' => $configuration->get('applePayMerchantName'),
            'displayName'        => $configuration->get('applePayDisplayName'),
            'initiative'         => 'web',
            'initiativeContext'  => $request->getHttpHost(),
        ];

        if (!\file_exists($merchantIdCertPath) || !\file_exists($merchantIdKeyPath)) {
            $this->logger->error(
                'ApplePay MerchantValidation Cert files missing.',
                [ $merchantIdCertPath, $merchantIdKeyPath ],
            );

            throw new FileNotFoundException();
        }

        try {
            $response = $this->httpClient->request('POST', $validationUrl, [
                'json'        => $body,
                'cert'        => (empty($passPhrase)) ? $merchantIdCertPath : [ $merchantIdCertPath, $passPhrase ],
                'ssl_key'     => $merchantIdKeyPath,
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('ApplePay merchant validation failed.', [
                'body'    => $body,
                'message' => $e->getMessage(),
                'url'     => $validationUrl,
            ]);

            return new Response(null, 500);
        }

        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            $this->logger->error('ApplePay merchant validation failed.', [
                'body'       => $body,
                'statusCode' => $statusCode,
                'url'        => $validationUrl,
            ]);
        }

        return new JsonResponse(
            $response->getBody()->getContents(),
            $statusCode,
            $response->getHeaders(),
            true,
        );
    }

    #[\Override]
    #[Route(
        path: '/store-api/payone/apple-pay/process',
        name: 'store-api.payone.apple-pay.process',
        defaults: [ '_contextTokenRequired' => true ],
        methods: [ 'POST' ],
    )]
    public function process(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $salesChannelId      = $salesChannelContext->getSalesChannel()->getId();
        $configuration       = $this->configReader->read($salesChannelId);
        $token               = $request->get('token');
        $authorizationMethod = $configuration->getString('applePayAuthorizationMethod', 'preauthorization');
        $cart                = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $orderId             = $request->get('orderId');

        $orderEntity = match ($orderId) {
            null    => $this->createFakeOrderEntity(),

            default => $this->orderLoaderService->getOrderById(
                (string) $orderId,
                $salesChannelContext->getContext(),
            ),
        };

        $payoneRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), $orderEntity, []),
                new RequestDataBag($token),
                $salesChannelContext,
                $cart,
                $this->paymentHandler,
                $authorizationMethod,
            ),

            $this->requestEnricherChain,
        );

        try {
            $response = $this->client->request($payoneRequest->all());
        } catch (\Throwable) {
            return new JsonResponse([], 402);
        }

        return new JsonResponse($response);
    }

    private function createFakeOrderEntity(): OrderEntity
    {
        $orderEntity = new OrderEntity();

        $orderEntity->setId(self::EMPTY_ORDER_ID);

        return $orderEntity;
    }
}
