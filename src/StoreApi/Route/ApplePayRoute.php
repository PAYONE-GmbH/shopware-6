<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use GuzzleHttp\Client;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\PayoneApplePayPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\ApplePayTransactionStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\ContextTokenRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @RouteScope(scopes={"store-api"})
 * @ContextTokenRequired
 */
class ApplePayRoute extends AbstractApplePayRoute
{
    /** @var Client */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        Client $httpClient,
        LoggerInterface $logger,
        RequestParameterFactory $requestParameterFactory,
        PayoneClientInterface $client,
        ConfigReaderInterface $configReader
    ) {
        $this->httpClient              = $httpClient;
        $this->logger                  = $logger;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->client                  = $client;
        $this->configReader            = $configReader;
    }

    public function getDecorated(): AbstractCardRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Route("/store-api/payone/apple-pay/validate-merchant", name="store-api.payone.apple-pay.validate-merchant", methods={"POST"})
     */
    public function validateMerchant(Request $request, SalesChannelContext $context): Response
    {
        $configuration      = $this->configReader->read($context->getSalesChannel()->getId());
        $validationUrl      = $request->get('validationUrl', 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession');
        $merchantIdCertPath = __DIR__ . '/../../apple-pay-cert/merchant_id.pem';
        $merchantIdKeyPath  = __DIR__ . '/../../apple-pay-cert/merchant_id.key';

        $passPhrase = $configuration->get('applePayCertPassphrase');

        $body = [
            'merchantIdentifier' => $configuration->get('applePayMerchantName'),
            'displayName'        => $configuration->get('applePayDisplayName'),
            'initiative'         => 'web',
            'initiativeContext'  => $request->getHttpHost(),
        ];

        if (!file_exists($merchantIdCertPath) || !file_exists($merchantIdKeyPath)) {
            $this->logger->error('ApplePay MerchantValidation Cert files missing.', [$merchantIdCertPath, $merchantIdKeyPath]);
            throw new FileNotFoundException();
        }

        try {
            $response = $this->httpClient->request('POST', $validationUrl,
                [
                    'json'        => $body,
                    'cert'        => (empty($passPhrase)) ? $merchantIdCertPath : [$merchantIdCertPath, $passPhrase],
                    'ssl_key'     => $merchantIdKeyPath,
                    'http_errors' => false,
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error('ApplePay merchant validation failed.', ['body' => $body, 'message' => $e->getMessage(), 'url' => $validationUrl]);

            return new Response(null, 500);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->logger->error('ApplePay merchant validation failed.', ['body' => $body, 'statusCode' => $statusCode, 'url' => $validationUrl]);
        }

        return new JsonResponse(
            json_decode($response->getBody()->getContents(), true),
            $statusCode,
            $response->getHeaders()
        );
    }

    /**
     * @Route("/store-api/payone/apple-pay/process", name="store-api.payone.apple-pay.process", methods={"POST"})
     */
    public function process(Request $request, SalesChannelContext $context): Response
    {
        $salesChannelId = $context->getSalesChannel()->getId();
        $configuration  = $this->configReader->read($salesChannelId);
        $token          = $request->get('token');

        $authorizationMethod = $configuration->getString('applePayAuthorizationMethod', 'preauthorization');

        $request = $this->requestParameterFactory->getRequestParameter(
            new ApplePayTransactionStruct(
                new RequestDataBag($token),
                $context,
                PayoneApplePayPaymentHandler::class,
                $authorizationMethod
            )
        );

        try {
            $response = $this->client->request($request);
        } catch (Throwable $exception) {
            return new JsonResponse([], 402);
        }

        return new JsonResponse($response);
    }
}
