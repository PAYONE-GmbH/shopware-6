<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\ManageMandate\ManageMandateRequestFactory;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class CheckoutManageMandateController extends StorefrontController
{
    /** @var ManageMandateRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ManageMandateRequestFactory $mandateRequestFactory,
        PayoneClientInterface $client,
        TranslatorInterface $translator
    ) {
        $this->requestFactory = $mandateRequestFactory;
        $this->client         = $client;
        $this->translator = $translator;
    }

    /**
     * @Route("/payone/request/manage-mandate", name="frontend.payone.manage-mandate", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     *
     * @throws CustomerNotLoggedInException
     */
    public function mandateOverview(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $iban = (string) $request->get('iban');
        $bic  = (string) $request->get('bic');

        $payoneRequest = $this->requestFactory->getRequestParameters(
            $context,
            $iban,
            $bic
        );

        try {
            $response = $this->client->request($payoneRequest);

            if (!empty($response['mandate']['HtmlText'])) {
                $response['mandate']['HtmlText'] = urldecode($response['mandate']['HtmlText']);
            }

            if ($response['mandate']['Status'] === 'active') {
                // return and proceed with normal workflow
            }
        } catch (PayoneRequestException $exception) {
            $response = [
                'error'   => true,
                'message' => $exception->getResponse()['error']['CustomerMessage'],
            ];
        } catch (Throwable $exception) {
            $response = [
                'error'   => true,
                'message' => $this->translator->trans('PayonePayment.errorMessages.genericError'),
            ];
        }

        return $this->renderStorefront('@Storefront/payone/mandate/mandate.html.twig', $response);
    }
}
