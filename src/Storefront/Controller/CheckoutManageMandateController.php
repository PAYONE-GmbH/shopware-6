<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\ManageMandate\ManageMandateRequestFactory;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class CheckoutManageMandateController extends StorefrontController
{
    /** @var ManageMandateRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    public function __construct(
        ManageMandateRequestFactory $mandateRequestFactory,
        PayoneClientInterface $client
    ) {
        $this->requestFactory = $mandateRequestFactory;
        $this->client         = $client;
    }

    /**
     * @Route("/payone/request/manage-mandate", name="frontend.payone.manage-mandate", options={"seo": "false"}, methods={"POST"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();

        $iban = $request->get('iban');
        $bic = $request->get('bic');

        $request = $this->requestFactory->getRequestParameters(
            $iban,
            $bic,
            $context
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            return new JsonResponse([
                'error' => $exception->getResponse()['error']['CustomerMessage'],
            ]);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'error' => $this->translator->trans('PayonePayment.errorMessages.genericError'),
            ]);
        }

        return new JsonResponse([]);
    }
}
