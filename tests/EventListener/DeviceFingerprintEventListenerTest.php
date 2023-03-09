<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\DeviceFingerprint\DeviceFingerprintServiceCollection;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\Storefront\Struct\DeviceFingerprintData;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\EventListener\DeviceFingerprintEventListener
 */
class DeviceFingerprintEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsDeviceFingerprintDataExtensionOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier(PayoneRatepayDebitPaymentHandler::class);

        $listener = new DeviceFingerprintEventListener(
            $this->getContainer()->get(DeviceFingerprintServiceCollection::class),
        );

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addDeviceFingerprintData($event);

        static::assertTrue($event->getPage()->hasExtension(DeviceFingerprintData::EXTENSION_NAME));
    }

    public function testItAddsDeviceFingerprintDataExtensionOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setHandlerIdentifier(PayoneRatepayDebitPaymentHandler::class);

        $listener = new DeviceFingerprintEventListener(
            $this->getContainer()->get(DeviceFingerprintServiceCollection::class),
        );

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addDeviceFingerprintData($event);

        static::assertTrue($event->getPage()->hasExtension(DeviceFingerprintData::EXTENSION_NAME));
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
}
