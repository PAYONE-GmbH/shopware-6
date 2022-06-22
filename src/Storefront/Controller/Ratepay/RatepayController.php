<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Ratepay;

use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayCalculationStruct;
use RuntimeException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class RatepayController extends StorefrontController
{
    /** @var CartService */
    private $cartService;

    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    public function __construct(
        CartService $cartService,
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory
    ) {
        $this->cartService             = $cartService;
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/ratepay/installment/calculation", name="frontend.payone.ratepay.installment.calculation", options={"seo": "false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    public function calculation(RequestDataBag $dataBag, SalesChannelContext $context): Response
    {
        try {
            $cart = $this->cartService->getCart($context->getToken(), $context);

            $calculationRequest = $this->requestParameterFactory->getRequestParameter(
                new RatepayCalculationStruct(
                    $cart,
                    $dataBag,
                    $context,
                    PayoneRatepayInstallmentPaymentHandler::class,
                    AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_CALCULATION
                )
            );

            try {
                $calculationResponse = $this->client->request($calculationRequest);
            } catch (PayoneRequestException $exception) {
                throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
            }
        } catch (Throwable $exception) {
            throw new RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
        }

        return $this->renderStorefront('@Storefront/storefront/payone/ratepay/ratepay-installment-plan.html.twig', [
            'ratepay' => [
                'installment' => $calculationResponse['addpaydata'],
            ],
        ]);
    }
}
