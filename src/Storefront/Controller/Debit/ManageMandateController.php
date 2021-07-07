<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Debit;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\ManageMandateStruct;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class ManageMandateController extends StorefrontController
{
    /** @var RequestParameterFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    public function __construct(
        RequestParameterFactory $mandateRequestFactory,
        PayoneClientInterface $client
    ) {
        $this->requestFactory = $mandateRequestFactory;
        $this->client         = $client;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/debit/manage-mandate", name="frontend.payone.debit.manage-mandate", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): JsonResponse
    {
        $payoneRequest = $this->requestFactory->getRequestParameter(
            new ManageMandateStruct(
                $context,
                (string) $request->get('iban', ''),
                (string) $request->get('bic', ''),
                PayoneDebitPaymentHandler::class
            )
        );

        try {
            $response = $this->client->request($payoneRequest);

            if (!empty($response['mandate']['HtmlText'])) {
                $response['mandate']['HtmlText'] = urldecode($response['mandate']['HtmlText']);

                $content = $this->renderView('@PayonePayment/storefront/payone/mandate/mandate.html.twig', $response);

                $response['modal_content'] = $content;
            }
        } catch (PayoneRequestException $exception) {
            $response = [
                'error' => $exception->getResponse()['error']['CustomerMessage'],
            ];
        } catch (Throwable $exception) {
            $response = [
                'error' => $this->trans('PayonePayment.errorMessages.genericError'),
            ];
        }

        return new JsonResponse($response);
    }
}
