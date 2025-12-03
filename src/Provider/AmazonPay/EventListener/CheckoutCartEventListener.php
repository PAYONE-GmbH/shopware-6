<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\EventListener;

use Doctrine\DBAL\Connection;
use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService;
use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\AmazonPay\ButtonConfiguration;
use PayonePayment\Provider\AmazonPay\PaymentHandler\ExpressPaymentHandler;
use PayonePayment\Provider\AmazonPay\PaymentMethod\ExpressPaymentMethod;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\AddressCompareService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

readonly class CheckoutCartEventListener implements EventSubscriberInterface
{
    private Serializer $serializer;

    public function __construct(
        private PaymentRequestEnricher $paymentRequestEnricher,
        private RequestParameterEnricherChain $requestEnricherChain,
        private ExpressPaymentHandler $paymentHandler,
        private PayoneClientInterface $payoneClient,
        private LoggerInterface $logger,
        private DefaultPaymentFilterService $paymentFilterService,
        private CartExtensionService $cartExtensionService,
        private CartService $cartService,
        private Connection $connection,
        private ButtonConfiguration $buttonConfiguration,
        private AddressCompareService $addressCompareService,
    ) {
        $this->serializer = new Serializer(encoders: [ new JsonEncoder() ]);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class  => 'onCartLoaded',
            OffcanvasCartPageLoadedEvent::class => 'onCartLoaded',
        ];
    }

    public function onCartLoaded(CheckoutCartPageLoadedEvent|OffcanvasCartPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        $sql = <<<'SQL'
SELECT LOWER(HEX(assoc.`sales_channel_id`)) FROM `%s` AS assoc
    LEFT JOIN `%s` AS pm ON pm.`id` = assoc.`payment_method_id`
    WHERE assoc.`payment_method_id` = ? AND pm.`active` = 1
SQL;

        $salesChannels = $this->connection->fetchFirstColumn(
            \sprintf(
                $sql,
                SalesChannelPaymentMethodDefinition::ENTITY_NAME,
                PaymentMethodDefinition::ENTITY_NAME,
            ),
            [ Uuid::fromHexToBytes(ExpressPaymentMethod::UUID) ],
        );

        if (!\in_array($salesChannelContext->getSalesChannelId(), $salesChannels, true)) {
            // payment method is not assigned to sales-channel of is disabled.
            return;
        }

        $paymentMethods = new PaymentMethodCollection([
            (new PaymentMethodEntity())->assign([
                'id'                => ExpressPaymentMethod::UUID,
                'handlerIdentifier' => ExpressPaymentHandler::class,
            ]),
        ]);

        $this->paymentFilterService->filterPaymentMethods(
            $paymentMethods,
            new PaymentFilterContext(
                $salesChannelContext,
                $this->addressCompareService,
                currency: $salesChannelContext->getCurrency(),
                cart: $event->getPage()->getCart(),
                flags: [
                    PaymentFilterContext::FLAG_SKIP_EC_REQUIRED_DATA_VALIDATION,
                ],
            ),
        );

        if (!$paymentMethods->has(ExpressPaymentMethod::UUID)) {
            return;
        }

        // $location = (($event instanceof CheckoutCartPageLoadedEvent || $event instanceof OffcanvasCartPageLoadedEvent) ? 'Cart' : null);
        $location = 'Cart'; // at the moment, only cart is supported

        try {
            $cart = $event->getPage()->getCart();

            $createRequest = $this->paymentRequestEnricher->enrich(
                new PaymentRequestDto(
                    new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                    new RequestDataBag(),
                    $salesChannelContext,
                    $cart,
                    $this->paymentHandler,
                ),

                $this->requestEnricherChain,
            );

            $requestParameters = $createRequest->all();
            $response          = $this->getInitializedPaymentResponse(
                $cart,
                $requestParameters,
            ) ?: $this->payoneClient->request($requestParameters);

            if ('OK' === $response['status']) {
                $this->setInitializedPaymentResponse(
                    $salesChannelContext,
                    $cart,
                    $requestParameters,
                    $response,
                );

                $this->cartExtensionService->addCartExtensionForExpressCheckout(
                    $cart,
                    $salesChannelContext,
                    ExpressPaymentMethod::UUID,
                    $response['workorderid'],
                );

                $event->getPage()->addExtension(
                    'payoneAmazonPayExpressButton',
                    $this->buttonConfiguration->getButtonConfiguration(
                        $salesChannelContext,
                        $location,
                        $response['addpaydata'],
                        true,
                        $cart->getPrice()->getTotalPrice(),
                        $salesChannelContext->getCurrency()->getIsoCode(),
                    ),
                );
            } else {
                $this->logger->error(
                    'Payone Amazon Pay Express: Can not initiate checkout-session for AmazonPay Button in cart. Error: ' . $response['errorcode'],
                    [ 'response' => $response ],
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error('Payone Amazon Pay Express: Can not initiate checkout-session for AmazonPay Button in cart. Error: ' . $exception->getMessage());
        }
    }

    private function setInitializedPaymentResponse(
        SalesChannelContext $context,
        Cart $cart,
        array $requestParameters,
        array $response,
    ): void {
        /** @var ArrayStruct $extension */
        $extension = $cart->getExtension('payoneAmazonPayExpressInit') ?: new ArrayStruct();
        $json      = $this->serializer->encode($requestParameters, JsonEncoder::FORMAT);

        // TODO: Check if we better check via tray-catch-block
        if ('' === $json) {
            return;
        }

        $extension->set(\md5($json), $response);
        $cart->addExtension('payoneAmazonPayExpressInit', $extension);
        $this->cartService->recalculate($cart, $context);
    }

    private function getInitializedPaymentResponse(Cart $cart, array $requestParameters): array|null
    {
        $json = $this->serializer->encode($requestParameters, JsonEncoder::FORMAT);

        if ('' === $json) {
            return null;
        }

        $extension = $cart->getExtension('payoneAmazonPayExpressInit');

        return $extension instanceof ArrayStruct ? $extension->get(\md5($json)) : null;
    }
}
