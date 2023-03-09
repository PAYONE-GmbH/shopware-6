<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\Installment\InstallmentService;
use PayonePayment\Components\Ratepay\Profile\ProfileService;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use PayonePayment\TestCaseBase\ConfigurationHelper;
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
    use ConfigurationHelper;

    public function testItHidesPaymentMethodsForCompaniesOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCompany('the-company');

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
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

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
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

        $event = new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmRatepayEventListener::class);

        $listener->hidePaymentMethodsForCompanies($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayoneDebit::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    /**
     * @dataProvider filterByProfilesData
     * @testdox It removes $paymentHandler because the cart value of $cartValue is too low or too high, and it keeps other ratepay methods
     */
    public function testItFiltersPaymentMethodsByProfilesOnCheckoutConfirmPage(string $paymentHandler, int $cartValue): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler, [
            sprintf('tx-limit-%s-min', ProfileService::PAYMENT_KEYS[$paymentHandler]) => '50',
            sprintf('tx-limit-%s-max', ProfileService::PAYMENT_KEYS[$paymentHandler]) => '100',
        ]);

        $validPaymentHandler = array_diff(PaymentHandlerGroups::RATEPAY, [$paymentHandler]);
        foreach ($validPaymentHandler as $valid) {
            $this->setValidRatepayProfiles($this->getContainer(), $valid, [
                sprintf('tx-limit-%s-min', ProfileService::PAYMENT_KEYS[$valid]) => (string) ($cartValue - 10),
                sprintf('tx-limit-%s-max', ProfileService::PAYMENT_KEYS[$valid]) => (string) ($cartValue + 10),
            ]);
        }

        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->fillCart($salesChannelContext->getToken(), $cartValue);

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmRatepayEventListener::class);

        $listener->hidePaymentMethodsByProfiles($event);

        static::assertSame(3, $event->getPage()->getPaymentMethods()->count());
        static::assertNotInPaymentCollection($paymentHandler, $event->getPage()->getPaymentMethods());
        foreach ($validPaymentHandler as $valid) {
            static::assertInPaymentCollection($valid, $event->getPage()->getPaymentMethods());
        }
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
        $installmentService->expects(static::once())->method('getInstallmentCalculatorData')->willReturn($calculatorData);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(ProfileService::class)
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
        $installmentService->expects(static::once())->method('getInstallmentCalculatorData')->willReturn($calculatorData);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(ProfileService::class)
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
        $installmentService->expects(static::once())->method('getInstallmentCalculatorData')->willReturn(null);

        $listener = new CheckoutConfirmRatepayEventListener(
            $this->getContainer()->get(SystemConfigService::class),
            $installmentService,
            $this->getContainer()->get(ProfileService::class)
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertFalse($event->getPage()->hasExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME));
        static::assertSame(3, $event->getPage()->getPaymentMethods()->count());
        static::assertNotInPaymentCollection(
            PayoneRatepayInstallmentPaymentHandler::class,
            $event->getPage()->getPaymentMethods()
        );
    }

    public function filterByProfilesData(): array
    {
        return [
            [PayoneRatepayDebitPaymentHandler::class, 30],
            [PayoneRatepayDebitPaymentHandler::class, 130],
            [PayoneRatepayInstallmentPaymentHandler::class, 30],
            [PayoneRatepayInstallmentPaymentHandler::class, 130],
            [PayoneRatepayInvoicingPaymentHandler::class, 30],
            [PayoneRatepayInvoicingPaymentHandler::class, 130],
        ];
    }

    protected function setPaymentMethods(Page $page): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod3 = new PaymentMethodEntity();
        $paymentMethod4 = new PaymentMethodEntity();

        $paymentMethod1->setId(PayoneDebit::UUID);
        $paymentMethod1->setHandlerIdentifier(PayoneDebitPaymentHandler::class);
        $paymentMethod2->setId(PayoneRatepayDebit::UUID);
        $paymentMethod2->setHandlerIdentifier(PayoneRatepayDebitPaymentHandler::class);
        $paymentMethod3->setId(PayoneRatepayInstallment::UUID);
        $paymentMethod3->setHandlerIdentifier(PayoneRatepayInstallmentPaymentHandler::class);
        $paymentMethod4->setId(PayoneRatepayInvoicing::UUID);
        $paymentMethod4->setHandlerIdentifier(PayoneRatepayInvoicingPaymentHandler::class);

        $page->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
            $paymentMethod3,
            $paymentMethod4,
        ]));
    }

    protected static function assertInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods): void
    {
        static::assertSame(1, $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return $paymentMethod->getHandlerIdentifier() === $paymentHandler;
            }
        )->count());
    }

    protected static function assertNotInPaymentCollection(string $paymentHandler, PaymentMethodCollection $paymentMethods): void
    {
        static::assertSame(0, $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return $paymentMethod->getHandlerIdentifier() === $paymentHandler;
            }
        )->count());
    }
}
