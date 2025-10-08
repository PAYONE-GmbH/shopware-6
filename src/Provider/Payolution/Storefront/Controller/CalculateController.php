<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\Storefront\Controller;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Provider\Payolution\Enum\RequestActionEnum;
use PayonePayment\Provider\Payolution\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\CartHasherService;
use PayonePayment\Service\OrderLoaderService;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class CalculateController extends StorefrontController
{
    public function __construct(
        private readonly InstallmentPaymentHandler $paymentHandler,
        private readonly PaymentRequestEnricher $paymentRequestEnricher,
        private readonly RequestParameterEnricherChain $calculateRequestEnricherChain,
        private readonly RequestParameterEnricherChain $checkRequestEnricherChain,
        private readonly CartService $cartService,
        private readonly CartHasherService $cartHasher,
        private readonly PayoneClientInterface $client,
        private readonly OrderLoaderService $orderLoaderService,
        private readonly OrderConverter $orderConverter,
    ) {
    }

    #[Route(
        path: '/payone/installment/calculation/{orderId}',
        name: 'frontend.payone.payolution.installment.calculation',
        options: [
            'seo'         => false,
            'deprecation' => [
                'since'   => '6.4',
                'message' => 'The route "frontend.payone.payolution.installment.calculation" is deprecated and will be removed in Shopware 7.0. Use "payone.payolution.frontend.installment.calculate" instead.',
            ],
        ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'POST' ],
    )]
    #[Route(
        path: '/payone/payolution/installment/calculate/{orderId}',
        name: 'payone.payolution.frontend.installment.calculate',
        options: [ 'seo' => false ],
        defaults: [ 'XmlHttpRequest' => true ],
        methods: [ 'POST' ],
    )]
    public function index(
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        string|null $orderId = null,
    ): JsonResponse {
        $context = $salesChannelContext->getContext();

        try {
            $order = null;

            if ($orderId) {
                $order = $this->orderLoaderService->getOrderById($orderId, $context);

                if (!$order instanceof OrderEntity) {
                    throw $this->createNotFoundException();
                }

                $cart = $this->orderConverter->convertToCart($order, $context);
            } else {
                $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            }

            if ($this->isPreCheckNeeded($cart, $dataBag, $salesChannelContext)) {
                $checkRequest = $this->paymentRequestEnricher->enrich(
                    new PaymentRequestDto(
                        new PaymentTransactionDto(new OrderTransactionEntity(), $order ?? new OrderEntity(), []),
                        $dataBag,
                        $salesChannelContext,
                        $cart,
                        $this->paymentHandler,
                        RequestActionEnum::PAYOLUTION_PRE_CHECK->value,
                    ),

                    $this->checkRequestEnricherChain,
                );

                try {
                    $response = $this->client->request($checkRequest->all());
                } catch (PayoneRequestException) {
                    throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
                }

                // will be used inside the calculation request
                $dataBag->set(RequestConstantsEnum::WORK_ORDER_ID->value, $response['workorderid']);

                $response['carthash'] = $this->cartHasher->generate($cart, $salesChannelContext);
            } else {
                $response = [
                    'status'      => 'OK',
                    'workorderid' => $dataBag->get(RequestConstantsEnum::WORK_ORDER_ID->value),
                    'carthash'    => $dataBag->get(RequestConstantsEnum::CART_HASH->value),
                ];
            }

            $calculationRequest = $this->paymentRequestEnricher->enrich(
                new PaymentRequestDto(
                    new PaymentTransactionDto(new OrderTransactionEntity(), $order ?? new OrderEntity(), []),
                    $dataBag,
                    $salesChannelContext,
                    $cart,
                    $this->paymentHandler,
                    RequestActionEnum::PAYOLUTION_CALCULATION->value,
                ),

                $this->calculateRequestEnricherChain,
            );

            try {
                $calculationResponse = $this->client->request($calculationRequest->all());
            } catch (PayoneRequestException) {
                throw new \RuntimeException($this->trans('PayonePayment.errorMessages.genericError'));
            }

            $calculationResponse = $this->prepareCalculationOutput($calculationResponse);

            $response['installmentSelection'] = $this->getInstallmentSelectionHtml($calculationResponse);
            $response['calculationOverview']  = $this->getCalculationOverviewHtml($calculationResponse);

            $this->saveCalculationResponse($cart, $calculationResponse, $salesChannelContext);
        } catch (\Throwable $exception) {
            $response = [
                'status'  => 'ERROR',
                'message' => $exception->getMessage(),
            ];
        }

        return new JsonResponse($response);
    }

    private function prepareCalculationOutput(array $response): array
    {
        $data = [];

        foreach ($response['addpaydata'] as $key => $value) {
            $key  = \str_replace('PaymentDetails_', '', (string) $key);
            $keys = \explode('_', $key);

            if (4 === \count($keys)) {
                $data[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $this->convertType($keys[3], $value);

                \uksort($data[$keys[0]][$keys[1]][$keys[2]], 'strcmp');
                \uksort($data[$keys[0]][$keys[1]], 'strcmp');
                \uksort($data[$keys[0]], 'strcmp');
            }

            if (3 === \count($keys)) {
                $data[$keys[0]][$keys[1]][$keys[2]] = $this->convertType($keys[2], $value);

                \uksort($data[$keys[0]][$keys[1]], 'strcmp');
                \uksort($data[$keys[0]], 'strcmp');
            }

            if (2 === \count($keys)) {
                $data[$keys[0]][$keys[1]] = $this->convertType($keys[1], $value);

                \uksort($data[$keys[0]], 'strcmp');
            }
        }

        \uksort($data, 'strcmp');

        $response['addpaydata'] = [];

        foreach ($data as $element) {
            if (!empty($element['Installment'])) {
                $element['Installment'] = \array_values($element['Installment']);
            }

            $response['addpaydata'][] = $element;
        }

        return $response;
    }

    private function isPreCheckNeeded(Cart $cart, RequestDataBag $dataBag, SalesChannelContext $context): bool
    {
        $cartHash = $dataBag->get(RequestConstantsEnum::CART_HASH->value);

        if (!$this->cartHasher->validate($cart, $cartHash, $context)) {
            return true;
        }

        if (empty($dataBag->get(RequestConstantsEnum::WORK_ORDER_ID->value))) {
            return true;
        }

        return false;
    }

    private function getInstallmentSelectionHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/storefront/payone/payolution/payolution-installment-selection.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    private function getCalculationOverviewHtml(array $calculationResponse): string
    {
        $view = '@PayonePayment/storefront/payone/payolution/payolution-calculation-overview.html.twig';

        return $this->renderView($view, $calculationResponse);
    }

    /**
     * This method does return mixed. Therefore we are ignoring errors for now.
     *
     * @phpstan-ignore-next-line
     */
    private function convertType(string $key, $value): false|float|int|string|\DateTime
    {
        $float = [
            'Amount',
            'InterestRate',
            'OriginalAmount',
            'TotalAmount',
            'EffectiveInterestRate',
            'MinimumInstallmentFee',
        ];

        $int  = [ 'Duration' ];
        $date = [ 'Due' ];

        if (\in_array($key, $float, true)) {
            return (float) $value;
        }

        if (\in_array($key, $int, true)) {
            return (int) $value;
        }

        if (\in_array($key, $date, true)) {
            return \DateTime::createFromFormat('Y-m-d', $value);
        }

        return $value;
    }

    private function saveCalculationResponse(Cart $cart, array $calculationResponse, SalesChannelContext $context): void
    {
        $cartData = new CheckoutCartPaymentData();

        $cartData->assign(\array_filter([
            'calculationResponse' => $calculationResponse,
        ]));

        $cart->addExtension(CheckoutCartPaymentData::EXTENSION_NAME, $cartData);

        $this->cartService->recalculate($cart, $context);
    }
}
