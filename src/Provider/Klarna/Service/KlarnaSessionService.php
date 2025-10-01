<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Klarna\Service;

use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\Klarna\PaymentHandler\DirectDebitPaymentHandler;
use PayonePayment\Provider\Klarna\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Klarna\PaymentHandler\InvoicePaymentHandler;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\CartHasherService;
use PayonePayment\Storefront\Struct\CheckoutKlarnaSessionData;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class KlarnaSessionService
{
    final public const EMPTY_ORDER_ID = '--empty-order-id--';

    /**
     * @var list<AbstractPaymentHandler>
     */
    private array $paymentHandler;

    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
        private PaymentRequestEnricher $paymentRequestEnricher,
        private RequestParameterEnricherChain $sessionRequestEnricherChain,
        private PayoneClientInterface $payoneClient,
        private CartService $cartService,
        private CartHasherService $cartHasher,
        private EntityRepository $orderEntityRepository,
        DirectDebitPaymentHandler $directDebitPaymentHandler,
        InstallmentPaymentHandler $installmentPaymentHandler,
        InvoicePaymentHandler $invoiceResponseHandler,
    ) {
        $this->paymentHandler = [
            DirectDebitPaymentHandler::class => $directDebitPaymentHandler,
            InstallmentPaymentHandler::class => $installmentPaymentHandler,
            InvoicePaymentHandler::class     => $invoiceResponseHandler,
        ];
    }

    public function createKlarnaSession(
        SalesChannelContext $salesChannelContext,
        string|null $orderId = null,
    ): CheckoutKlarnaSessionData {
        if ($orderId) {
            $orderCriteria = $this->cartHasher->getCriteriaForOrder($orderId);

            /** @var OrderEntity|null $order */
            $order = $this->orderEntityRepository->search(
                $orderCriteria,
                $salesChannelContext->getContext(),
            )->first();
        }

        $cart          = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cartHash      = $this->cartHasher->generate($order ?? $cart, $salesChannelContext);
        $paymentMethod = $this->paymentMethodRegistry->get(
            $salesChannelContext->getPaymentMethod()->getTechnicalName(),
        );

        $request = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), $order ?? $this->createFakeOrderEntity(), []),
                new RequestDataBag(),
                $salesChannelContext,
                $cart,
                $this->paymentHandler[$paymentMethod->getPaymentHandlerClassName()],
            ),

            $this->sessionRequestEnricherChain,
        );

        $response = $this->payoneClient->request($request->all());

        return new CheckoutKlarnaSessionData(
            $response['addpaydata']['client_token'],
            $response['workorderid'],
            $response['addpaydata']['payment_method_category_identifier'],
            $cartHash,
        );
    }

    private function createFakeOrderEntity(): OrderEntity
    {
        $orderEntity = new OrderEntity();

        $orderEntity->setId(self::EMPTY_ORDER_ID);

        return $orderEntity;
    }
}
