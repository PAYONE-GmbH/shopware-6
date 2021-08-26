<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use GuzzleHttp\Client;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\StoreApi\Response\MandateResponse;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\ContextTokenRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    public function __construct(Client $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
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
        $validationUrl = $request->get('validationUrl', 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession');

        //TODO: get from config
        $body = [
            'merchantIdentifier' => 'merchant.saltyrocks.payone',
            'displayName' => 'PAYONE Apple Pay Prototyp',
            'initiative' => 'web',
            'initiativeContext' => 'saltmann-payone-kellerkinder-io.eu.ngrok.io',
        ];

        //TODO: add notice in config for certs
        $response = $this->httpClient->request('POST', $validationUrl,
            [
                'json' => $body,
                'cert'  => __DIR__ . '/../../apple-pay-cert/merchant_id.pem',
                'ssl_key'    => __DIR__ . '/../../apple-pay-cert/merchant_id.key',
            ]
        );

        return new JsonResponse($response->getBody(), $response->getStatusCode(), $response->getHeaders());
    }

    /**
     * @Route("/store-api/payone/apple-pay/process", name="store-api.payone.apple-pay.process", methods={"GET"})
     */
    public function process(SalesChannelContext $context): Response
    {

    }
}
