<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use GuzzleHttp\Client;
use PayonePayment\Components\MandateService\MandateServiceInterface;
use PayonePayment\StoreApi\Response\MandateResponse;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\ContextTokenRequired;
use Shopware\Core\Framework\Routing\Annotation\Entity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\HeaderUtils;
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

    public function __construct(Client $httpClient)
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
        $validationUrl = $request->get('validationUrl', 'https://apple-pay-gateway.apple.com');

        $header = [
            'verify_peer' => false,
            'cert'  => file_get_contents(__DIR__ . '/../../cert/merchant_id.pem'),
            'key'    => file_get_contents(__DIR__ . '/../../cert/merchant_id.key'),
            'content-type' => 'application/json'
        ];


        $body = [
            'merchantIdentifier' => 'merchant.saltyrocks.payone',
            'displayName' => 'PAYONE Apple Pay Prototyp',
            'initiative' => 'web',
            'initiativeContext' => 'saltmann-payone-kellerkinder-io.eu.ngrok.io',
        ];

        $response = $this->httpClient->request('POST', $validationUrl,
            [
                'headers' => $header,
                'body' => json_encode($body),
                'verify' => false
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
