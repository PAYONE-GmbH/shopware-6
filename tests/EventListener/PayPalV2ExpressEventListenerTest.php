<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Helper\ActivePaymentMethodsLoaderInterface;
use PayonePayment\PaymentMethod\PayonePaypalV2Express;
use PayonePayment\Storefront\Struct\PayPalV2ExpressButtonData;
use PayonePayment\Struct\Configuration;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \PayonePayment\EventListener\PayPalV2ExpressEventListener
 */
class PayPalV2ExpressEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    /**
     * @dataProvider subscribedEventClasses
     * @testdox It adds PayPalV2ExpressButtonData extension on $pageClass
     *
     * @param class-string<PageLoadedEvent> $eventClass
     * @param class-string<Page> $pageClass
     */
    public function testItAddsExpressButtonDataExtension(string $eventClass, string $pageClass): void
    {
        $activePaymentMethodIds = [
            Uuid::randomHex(),
            PayonePaypalV2Express::UUID,
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $request = $this->getRequestWithSession([]);
        $request->setLocale('de-DE');

        $configuration = new Configuration([
            'transactionMode' => 'test',
            'paypalV2ExpressPayPalMerchantId' => 'the-merchant-id',
            'paypalV2ExpressShowPayLaterButton' => false,
        ]);

        $activePaymentMethodsLoader = $this->createMock(ActivePaymentMethodsLoaderInterface::class);
        $activePaymentMethodsLoader->method('getActivePaymentMethodIds')->willReturn($activePaymentMethodIds);

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->method('read')->willReturn($configuration);

        $router = $this->createMock(RouterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new PayPalV2ExpressEventListener(
            $activePaymentMethodsLoader,
            $configReader,
            $router,
            $logger
        );

        $page = new $pageClass();
        $event = new $eventClass($page, $salesChannelContext, $request);

        $listener->addExpressCheckoutDataToPage($event);

        $extension = $page->getExtension(PayPalV2ExpressButtonData::EXTENSION_NAME);

        static::assertInstanceOf(PayPalV2ExpressButtonData::class, $extension);
        static::assertTrue($extension->isSandbox());
        static::assertSame('EUR', $extension->getCurrency());
        static::assertSame('de_DE', $extension->getLocale());
        static::assertFalse($extension->isShowPayLaterButton());
    }

    public function testItNotAddsExpressButtonDataExtensionBecausePaymentMethodIsNotActive(): void
    {
        $activePaymentMethodIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $request = $this->getRequestWithSession([]);
        $request->setLocale('de-DE');

        $activePaymentMethodsLoader = $this->createMock(ActivePaymentMethodsLoaderInterface::class);
        $activePaymentMethodsLoader->method('getActivePaymentMethodIds')->willReturn($activePaymentMethodIds);

        $configReader = $this->createMock(ConfigReaderInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new PayPalV2ExpressEventListener(
            $activePaymentMethodsLoader,
            $configReader,
            $router,
            $logger
        );

        $page = new CheckoutCartPage();
        $event = new CheckoutCartPageLoadedEvent($page, $salesChannelContext, $request);

        $listener->addExpressCheckoutDataToPage($event);

        $extension = $page->getExtension(PayPalV2ExpressButtonData::EXTENSION_NAME);

        static::assertNull($extension);
    }

    public function testItNotAddsExpressButtonDataExtensionBecauseMerchantIdIsMissingInLiveMode(): void
    {
        $activePaymentMethodIds = [
            Uuid::randomHex(),
            PayonePaypalV2Express::UUID,
        ];

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $request = $this->getRequestWithSession([]);
        $request->setLocale('de-DE');

        $configuration = new Configuration([
            'transactionMode' => 'live',
            'paypalV2ExpressPayPalMerchantId' => '',
            'paypalV2ExpressShowPayLaterButton' => false,
        ]);

        $activePaymentMethodsLoader = $this->createMock(ActivePaymentMethodsLoaderInterface::class);
        $activePaymentMethodsLoader->method('getActivePaymentMethodIds')->willReturn($activePaymentMethodIds);

        $configReader = $this->createMock(ConfigReaderInterface::class);
        $configReader->method('read')->willReturn($configuration);

        $router = $this->createMock(RouterInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('warning');

        $listener = new PayPalV2ExpressEventListener(
            $activePaymentMethodsLoader,
            $configReader,
            $router,
            $logger
        );

        $page = new CheckoutCartPage();
        $event = new CheckoutCartPageLoadedEvent($page, $salesChannelContext, $request);

        $listener->addExpressCheckoutDataToPage($event);

        $extension = $page->getExtension(PayPalV2ExpressButtonData::EXTENSION_NAME);

        static::assertNull($extension);
    }

    protected static function subscribedEventClasses(): array
    {
        return [
            [CheckoutCartPageLoadedEvent::class, CheckoutCartPage::class],
            [CheckoutRegisterPageLoadedEvent::class, CheckoutRegisterPage::class],
            [OffcanvasCartPageLoadedEvent::class, OffcanvasCartPage::class],
        ];
    }
}
