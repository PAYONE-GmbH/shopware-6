<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\DeviceFingerprint\DeviceFingerprintService;
use PayonePayment\Components\Ratepay\Installment\InstallmentService;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\Storefront\Struct\RatepayDeviceFingerprintData;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\EventListener\CheckoutConfirmRatepayEventListener
 */
class CheckoutConfirmRatepayEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItHidesPaymentMethodsForCompaniesOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('the-company');

        $event    = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmRatepayEventListener::class);

        $listener->hidePaymentMethodsForCompanies($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayoneDebit::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    public function testItHidesPaymentMethodsForCompaniesOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('the-company');

        $event    = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmRatepayEventListener::class);

        $listener->hidePaymentMethodsForCompanies($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayoneDebit::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    public function testItHidesPaymentMethodsForCompaniesOnAccountPaymentMethodPage(): void
    {
        $page = new AccountPaymentMethodPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('the-company');

        $event    = new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmRatepayEventListener::class);

        $listener->hidePaymentMethodsForCompanies($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayoneDebit::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    public function testItAddsInstallmentCalculatorDataExtensionOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setId(PayoneRatepayInstallment::UUID);

        $calculatorData = new RatepayInstallmentCalculatorData();
        $calculatorData->assign(['minimumRate' => 0.0]);

        $installmentService = $this->createMock(InstallmentService::class);
        $installmentService->expects($this->once())->method('getInstallmentCalculatorData')->willReturn($calculatorData);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(DeviceFingerprintService::class)
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertTrue($event->getPage()->hasExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME));
        static::assertSame(0.0, $event->getPage()->getExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME)->getMinimumRate());
    }

    public function testItAddsInstallmentCalculatorDataExtensionOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setId(PayoneRatepayInstallment::UUID);

        $calculatorData = new RatepayInstallmentCalculatorData();
        $calculatorData->assign(['minimumRate' => 0.0]);

        $installmentService = $this->createMock(InstallmentService::class);
        $installmentService->expects($this->once())->method('getInstallmentCalculatorData')->willReturn($calculatorData);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(DeviceFingerprintService::class)
        );

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertTrue($event->getPage()->hasExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME));
        static::assertSame(0.0, $event->getPage()->getExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME)->getMinimumRate());
    }

    public function testItRemovesInstallmentPaymentMethodOnMissingCalculatorData(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setId(PayoneRatepayInstallment::UUID);

        $installmentService = $this->createMock(InstallmentService::class);
        $installmentService->expects($this->once())->method('getInstallmentCalculatorData')->willReturn(null);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(DeviceFingerprintService::class)
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertFalse($event->getPage()->hasExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME));
        static::assertSame(3, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(0, $event->getPage()->getPaymentMethods()->filter(
            static function (PaymentMethodEntity $paymentMethod) {
                return $paymentMethod->getId() === PayoneRatepayInstallment::UUID;
            }
        )->count());
    }

    public function testItAddsDeviceFingerprintDataExtensionOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier(PayoneRatepayDebitPaymentHandler::class);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $this->createMock(InstallmentService::class),
            $this->getContainer()->get(DeviceFingerprintService::class)
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertTrue($event->getPage()->hasExtension(RatepayDeviceFingerprintData::EXTENSION_NAME));
    }

    public function testItAddsDeviceFingerprintDataExtensionOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier(PayoneRatepayDebitPaymentHandler::class);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $this->createMock(InstallmentService::class),
            $this->getContainer()->get(DeviceFingerprintService::class)
        );

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertTrue($event->getPage()->hasExtension(RatepayDeviceFingerprintData::EXTENSION_NAME));
    }

    protected function setPaymentMethods(Page $page): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod3 = new PaymentMethodEntity();
        $paymentMethod4 = new PaymentMethodEntity();

        $paymentMethod1->setId(PayoneDebit::UUID);
        $paymentMethod2->setId(PayoneRatepayDebit::UUID);
        $paymentMethod3->setId(PayoneRatepayInstallment::UUID);
        $paymentMethod4->setId(PayoneRatepayInvoicing::UUID);

        $page->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
            $paymentMethod3,
            $paymentMethod4,
        ]));
    }
}
