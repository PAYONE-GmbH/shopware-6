<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use Doctrine\DBAL\Connection;
use Exception;
use PayonePayment\Components\AmazonPay\ButtonConfiguration;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\GenericExpressCheckout\CartExtensionService;
use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\Components\PaymentFilter\DefaultPaymentFilterService;
use PayonePayment\Components\PaymentFilter\PaymentFilterContext;
use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\PaymentMethod\PayoneAmazonPayExpress;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelPaymentMethod\SalesChannelPaymentMethodDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutCartAmazonPayExpressEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestParameterFactory $requestParameterFactory,
        private readonly PayoneClientInterface $payoneClient,
        private readonly LoggerInterface $logger,
        private readonly ConfigReaderInterface $configReader,
        private readonly DefaultPaymentFilterService $paymentFilterService,
        private readonly CartExtensionService $cartExtensionService,
        private readonly CartService $cartService,
        private readonly Connection $connection,
        private readonly ButtonConfiguration $buttonConfiguration
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'onCartLoaded',
            OffcanvasCartPageLoadedEvent::class => 'onCartLoaded',
        ];
    }

    public function onCartLoaded(CheckoutCartPageLoadedEvent|OffcanvasCartPageLoadedEvent $event): void
    {
        $salesChannels = $this->connection->fetchFirstColumn(
            sprintf(
                'SELECT LOWER(HEX(assoc.`sales_channel_id`)) FROM `%s` AS assoc
                    LEFT JOIN `%s` AS pm ON pm.`id` = assoc.`payment_method_id`
                    WHERE assoc.`payment_method_id` = ? AND pm.`active` = 1',
                SalesChannelPaymentMethodDefinition::ENTITY_NAME,
                PaymentMethodDefinition::ENTITY_NAME
            ),
            [Uuid::fromHexToBytes(PayoneAmazonPayExpress::UUID)]
        );

        if (!\in_array($event->getSalesChannelContext()->getSalesChannelId(), $salesChannels, true)) {
            // payment method is not assigned to sales-channel of is disabled.
            return;
        }

        $filteredPaymentMethods = $this->paymentFilterService->filterPaymentMethods(
            new PaymentMethodCollection([(new PaymentMethodEntity())->assign([
                'id' => PayoneAmazonPayExpress::UUID,
                'handlerIdentifier' => PayoneAmazonPayExpressPaymentHandler::class,
            ])]),
            new PaymentFilterContext(
                salesChannelContext: $event->getSalesChannelContext(),
                currency: $event->getSalesChannelContext()->getCurrency(),
                cart: $event->getPage()->getCart(),
                flags: [
                    PaymentFilterContext::FLAG_SKIP_EC_REQUIRED_DATA_VALIDATION,
                ]
            )
        );

        if (!$filteredPaymentMethods->has(PayoneAmazonPayExpress::UUID)) {
            return;
        }

        //$location = (($event instanceof CheckoutCartPageLoadedEvent || $event instanceof OffcanvasCartPageLoadedEvent) ? 'Cart' : null);
        $location = 'Cart'; // at the moment, only cart is supported

        $config = $this->configReader->read($event->getSalesChannelContext()->getSalesChannelId());

        try {
            $requestStruct = new CreateExpressCheckoutSessionStruct(
                $event->getSalesChannelContext(),
                PayoneAmazonPayExpressPaymentHandler::class
            );
            $requestData = $this->requestParameterFactory->getRequestParameter($requestStruct);
            $response = $this->getInitializedPaymentResponse($event->getPage()->getCart(), $requestData) ?: $this->payoneClient->request($requestData);

            if ($response['status'] === 'OK') {
                $this->setInitializedPaymentResponse($event->getSalesChannelContext(), $event->getPage()->getCart(), $requestData, $response);

                $this->cartExtensionService->addCartExtension(
                    $event->getPage()->getCart(),
                    $event->getSalesChannelContext(),
                    $response['workorderid']
                );

                $event->getPage()->addExtension(
                    'payoneAmazonPayExpressButton',
                    $this->buttonConfiguration->getButtonConfiguration(
                        $event->getSalesChannelContext(),
                        $location,
                        $response['addpaydata'],
                        true,
                        $event->getPage()->getCart()->getPrice()->getTotalPrice(),
                        $event->getSalesChannelContext()->getCurrency()->getIsoCode(),
                    )
                );
            } else {
                $this->logger->error('Payone Amazon Pay Express: Can not initiate checkout-session for AmazonPay Button in cart. Error: ' . $response['errorcode'], [
                    'response' => $response,
                ]);
            }
        } catch (Exception $exception) {
            $this->logger->error('Payone Amazon Pay Express: Can not initiate checkout-session for AmazonPay Button in cart. Error: ' . $exception->getMessage());
        }
    }

    private function setInitializedPaymentResponse(SalesChannelContext $context, Cart $cart, array $requestParameters, array $response): void
    {
        /** @var ArrayStruct $extension */
        $extension = $cart->getExtension('payoneAmazonPayExpressInit') ?: new ArrayStruct();
        $json = json_encode($requestParameters);
        if ($json === false) {
            return;
        }

        $extension->set(md5($json), $response);
        $cart->addExtension('payoneAmazonPayExpressInit', $extension);
        $this->cartService->recalculate($cart, $context);
    }

    private function getInitializedPaymentResponse(Cart $cart, array $requestParameters): ?array
    {
        $json = json_encode($requestParameters);
        if ($json === false) {
            return null;
        }

        $extension = $cart->getExtension('payoneAmazonPayExpressInit');

        return $extension instanceof ArrayStruct ? $extension->get(md5($json)) : null;
    }
}
