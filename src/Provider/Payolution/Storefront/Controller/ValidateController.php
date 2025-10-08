<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\Storefront\Controller;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\Payolution\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class ValidateController extends StorefrontController
{
    public function __construct(
        private readonly InvoicePaymentHandler $paymentHandler,
        private readonly PaymentRequestEnricher $paymentRequestEnricher,
        private readonly RequestParameterEnricherChain $requestEnricherChain,
        private readonly CartService $cartService,
        private readonly PayoneClientInterface $client,
    ) {
    }

    #[Route(
        path: '/payone/invoicing/validate',
        name: 'frontend.payone.payolution.invoicing.validate',
        options: [
            'seo'         => false,
            'deprecation' => [
                'since'   => '6.4',
                'message' => 'The route "frontend.payone.payolution.invoicing.validate" is deprecated and will be removed in Shopware 7.0. Use "payone.payolution.frontend.invoice.validate" instead.',
            ],
        ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'POST' ],
    )]
    #[Route(
        path: '/payone/payolution/invoice/validate',
        name: 'payone.payolution.frontend.invoice.validate',
        options: [ 'seo' => false ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'POST' ],
    )]
    public function index(RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $checkRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                $dataBag,
                $salesChannelContext,
                $cart,
                $this->paymentHandler,
            ),

            $this->requestEnricherChain,
        );

        try {
            $response = $this->client->request($checkRequest->all());
        } catch (PayoneRequestException $exception) {
            $response = [
                'status'  => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }
}
