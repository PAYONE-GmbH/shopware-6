<?php

declare(strict_types=1);

namespace PayonePayment\Components\KlarnaSessionService;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use PayonePayment\Storefront\Struct\CheckoutKlarnaSessionData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class KlarnaSessionService implements KlarnaSessionServiceInterface
{
    /**
     * @var RequestParameterFactory
     */
    private $requestParameterFactory;
    /**
     * @var PayoneClientInterface
     */
    private $payoneClient;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var CartHasherInterface
     */
    private $cartHasher;
    /**
     * @var EntityRepository
     */
    private $orderEntityRepository;

    public function __construct(
        PayoneClientInterface $payoneClient,
        RequestParameterFactory $requestParameterFactory,
        CartService $cartService,
        CartHasherInterface $cartHasher,
        EntityRepository $orderEntityRepository
    ) {
        $this->requestParameterFactory = $requestParameterFactory;
        $this->payoneClient            = $payoneClient;
        $this->cartService             = $cartService;
        $this->cartHasher              = $cartHasher;
        $this->orderEntityRepository   = $orderEntityRepository;
    }

    public function createKlarnaSession(SalesChannelContext $salesChannelContext, string $orderId = null): CheckoutKlarnaSessionData
    {
        if ($orderId) {
            $orderCriteria = $this->cartHasher->getCriteriaForOrder($orderId);
            $order         = $this->orderEntityRepository->search($orderCriteria, $salesChannelContext->getContext())->first();
        }

        $cartHash = $this->cartHasher->generate(
            $order ?? $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext),
            $salesChannelContext
        );

        $struct        = new KlarnaCreateSessionStruct($salesChannelContext, $order ?? null);
        $requestParams = $this->requestParameterFactory->getRequestParameter($struct);
        $response      = $this->payoneClient->request($requestParams);

        return new CheckoutKlarnaSessionData(
            $response['addpaydata']['client_token'],
            $response['workorderid'],
            $response['addpaydata']['payment_method_category_identifier'],
            $cartHash
        );
    }
}
