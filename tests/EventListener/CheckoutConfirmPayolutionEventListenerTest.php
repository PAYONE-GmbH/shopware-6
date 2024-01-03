<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\EventListener\CheckoutConfirmPayolutionEventListener
 */
class CheckoutConfirmPayolutionEventListenerTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItHidesPaymentMethodsOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPayolutionEventListener::class);

        $listener->hidePaymentMethods($event);

        static::assertSame(0, $event->getPage()->getPaymentMethods()->count());
    }

    public function testItHidesAnotherPaymentMethodsWithInvoicingOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[PayonePayolutionInvoicingPaymentHandler::class] . 'CompanyName',
            'the-company'
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPayolutionEventListener::class);

        $listener->hidePaymentMethods($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayonePayolutionInvoicing::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    public function testItHidesAnotherPaymentMethodsWithDebitOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[PayonePayolutionDebitPaymentHandler::class] . 'CompanyName',
            'the-company'
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPayolutionEventListener::class);

        $listener->hidePaymentMethods($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayonePayolutionDebit::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    public function testItHidesAnotherPaymentMethodsWithInstallmentOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigInstaller::CONFIG_FIELD_PAYOLUTION_INVOICING_TRANSFER_COMPANY_DATA,
            true
        );

        $this->setPayoneConfig(
            $this->getContainer(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[PayonePayolutionInstallmentPaymentHandler::class] . 'CompanyName',
            'the-company'
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPayolutionEventListener::class);

        $listener->hidePaymentMethods($event);

        static::assertSame(1, $event->getPage()->getPaymentMethods()->count());
        static::assertSame(PayonePayolutionInstallment::UUID, $event->getPage()->getPaymentMethods()->first()->getId());
    }

    protected function setPaymentMethods(Page $page): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod3 = new PaymentMethodEntity();
        $paymentMethod4 = new PaymentMethodEntity();

        $paymentMethod1->setId(PayonePayolutionInstallment::UUID);
        $paymentMethod1->setHandlerIdentifier(PayonePayolutionInstallmentPaymentHandler::class);

        $paymentMethod2->setId(PayonePayolutionInvoicing::UUID);
        $paymentMethod2->setHandlerIdentifier(PayonePayolutionInvoicingPaymentHandler::class);

        $paymentMethod3->setId(PayonePayolutionDebit::UUID);
        $paymentMethod3->setHandlerIdentifier(PayonePayolutionDebitPaymentHandler::class);

        $paymentMethod4->setId(PayonePayolutionInstallment::UUID);
        $paymentMethod4->setHandlerIdentifier(PayonePayolutionInstallmentPaymentHandler::class);

        $page->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
            $paymentMethod3,
            $paymentMethod4,
        ]));
    }
}
