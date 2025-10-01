<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Service\ActivePaymentMethodsLoaderService;
use PayonePayment\Provider\PayPal\PaymentMethod\ExpressV2PaymentMethod;
use PayonePayment\Storefront\Controller\GenericExpressController;
use PayonePayment\Storefront\Struct\PayPalV2ExpressButtonData;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class PayPalV2ExpressEventListener implements EventSubscriberInterface
{
    private const LIVE_CLIENT_ID = 'AVNBj3ypjSFZ8jE7shhaY2mVydsWsSrjmHk0qJxmgJoWgHESqyoG35jLOhH3GzgEPHmw7dMFnspH6vim';

    private const SANDBOX_CLIENT_ID = 'AUn5n-4qxBUkdzQBv6f8yd8F4AWdEvV6nLzbAifDILhKGCjOS62qQLiKbUbpIKH_O2Z3OL8CvX7ucZfh';

    private const SANDBOX_MERCHANT_ID = '3QK84QGGJE5HW';

    public function __construct(
        private readonly ActivePaymentMethodsLoaderService $activePaymentMethodsLoader,
        private readonly ConfigReaderInterface $configReader,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class     => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class    => 'addExpressCheckoutDataToPage',
        ];
    }

    public function addExpressCheckoutDataToPage(PageLoadedEvent $event): void
    {
        $salesChannelContext    = $event->getSalesChannelContext();
        $activePaymentMethodIds = $this->activePaymentMethodsLoader->getActivePaymentMethodIds($salesChannelContext);
        if (!\in_array(ExpressV2PaymentMethod::UUID, $activePaymentMethodIds, true)) {
            return;
        }

        $request    = $event->getRequest();
        $config     = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $isSandbox  = 'test' === $config->get('transactionMode');
        $merchantId = $isSandbox ? self::SANDBOX_MERCHANT_ID : $config->get('paypalV2ExpressPayPalMerchantId');
        if (!\is_string($merchantId) || '' === $merchantId) {
            $this->logger->warning('The payment method “PAYONE PayPal Express v2” is active, but the configuration “PayPal Merchant ID (Live)” is missing!');

            return;
        }

        $struct = new PayPalV2ExpressButtonData();

        $struct->assign([
            'sandbox'                  => $isSandbox,
            'clientId'                 => $isSandbox ? self::SANDBOX_CLIENT_ID : self::LIVE_CLIENT_ID,
            'merchantId'               => $merchantId,
            'currency'                 => $salesChannelContext->getCurrency()->getIsoCode(),
            'locale'                   => \str_replace('-', '_', $request->getLocale()),
            'showPayLaterButton'       => $config->getBool('paypalV2ExpressShowPayLaterButton'),
            'createCheckoutSessionUrl' => $this->router->generate(
                'frontend.account.payone.express-checkout.generic.create-session',
                [
                    'paymentMethodId' => ExpressV2PaymentMethod::UUID,
                ],
            ),
            'onApproveRedirectUrl'     => $this->router->generate(
                'frontend.account.payone.express-checkout.generic.return',
                [
                    'paymentMethodId' => ExpressV2PaymentMethod::UUID,
                    'state'           => GenericExpressController::STATE_SUCCESS,
                ],
            ),
            'onCancelRedirectUrl'      => $this->router->generate(
                'frontend.account.payone.express-checkout.generic.return',
                [
                    'paymentMethodId' => ExpressV2PaymentMethod::UUID,
                    'state'           => GenericExpressController::STATE_CANCEL,
                ],
            ),
            'onErrorRedirectUrl'       => $this->router->generate(
                'frontend.account.payone.express-checkout.generic.return',
                [
                    'paymentMethodId' => ExpressV2PaymentMethod::UUID,
                    'state'           => GenericExpressController::STATE_ERROR,
                ],
            ),
        ]);

        $event->getPage()->addExtension(PayPalV2ExpressButtonData::EXTENSION_NAME, $struct);
    }
}
