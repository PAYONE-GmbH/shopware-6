<?php

declare(strict_types=1);

namespace PayonePayment\Components\ApplePay\StoreApi\Route;

use GuzzleHttp\Client;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\PayoneApplePayPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\ApplePayTransactionStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ApplePayRoute extends AbstractApplePayRoute
{
    final public const CERT_FOLDER = '/config/apple-pay-cert/';

    public function __construct(
        private readonly Client $httpClient,
        private readonly LoggerInterface $logger,
        private readonly RequestParameterFactory $requestParameterFactory,
        private readonly PayoneClientInterface $client,
        private readonly ConfigReaderInterface $configReader,
        private readonly string $kernelDirectory
    ) {
    }

    public function getDecorated(): AbstractApplePayRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/payone/apple-pay/validate-merchant', name: 'store-api.payone.apple-pay.validate-merchant', defaults: ['_contextTokenRequired' => true], methods: ['POST'])]
    public function validateMerchant(Request $request, SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());
        $validationUrl = $request->get('validationUrl', 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession');
        $merchantIdCertPath = $this->kernelDirectory . self::CERT_FOLDER . 'merchant_id.pem';
        $merchantIdKeyPath = $this->kernelDirectory . self::CERT_FOLDER . 'merchant_id.key';

        $passPhrase = $configuration->get('applePayCertPassphrase');

        $body = [
            'merchantIdentifier' => $configuration->get('applePayMerchantName'),
            'displayName' => $configuration->get('applePayDisplayName'),
            'initiative' => 'web',
            'initiativeContext' => $request->getHttpHost(),
        ];

        if (!file_exists($merchantIdCertPath) || !file_exists($merchantIdKeyPath)) {
            $this->logger->error('ApplePay MerchantValidation Cert files missing.', [$merchantIdCertPath, $merchantIdKeyPath]);

            throw new FileNotFoundException();
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                $validationUrl,
                [
                    'json' => $body,
                    'cert' => (empty($passPhrase)) ? $merchantIdCertPath : [$merchantIdCertPath, $passPhrase],
                    'ssl_key' => $merchantIdKeyPath,
                    'http_errors' => false,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('ApplePay merchant validation failed.', ['body' => $body, 'message' => $e->getMessage(), 'url' => $validationUrl]);

            return new Response(null, 500);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->logger->error('ApplePay merchant validation failed.', ['body' => $body, 'statusCode' => $statusCode, 'url' => $validationUrl]);
        }

        return new JsonResponse(
            json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR),
            $statusCode,
            $response->getHeaders()
        );
    }

    #[Route(path: '/store-api/payone/apple-pay/process', name: 'store-api.payone.apple-pay.process', defaults: ['_contextTokenRequired' => true], methods: ['POST'])]
    public function process(Request $request, SalesChannelContext $context): Response
    {
        $salesChannelId = $context->getSalesChannel()->getId();
        $configuration = $this->configReader->read($salesChannelId);
        $token = $request->get('token');

        $authorizationMethod = $configuration->getString('applePayAuthorizationMethod', 'preauthorization');

        $payoneRequest = $this->requestParameterFactory->getRequestParameter(
            new ApplePayTransactionStruct(
                new RequestDataBag($token),
                $context,
                PayoneApplePayPaymentHandler::class,
                $authorizationMethod,
                $request->get('orderId', null)
            )
        );

        try {
            $response = $this->client->request($payoneRequest);
        } catch (\Throwable) {
            return new JsonResponse([], 402);
        }

        return new JsonResponse($response);
    }
}
