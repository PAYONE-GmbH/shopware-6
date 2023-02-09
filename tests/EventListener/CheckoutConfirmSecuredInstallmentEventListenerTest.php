<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\SecuredInstallment\InstallmentService;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayoneSecuredInstallment;
use PayonePayment\PaymentMethod\PayoneSecuredInvoice;
use PayonePayment\Storefront\Struct\SecuredInstallmentOption;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\EventListener\CheckoutConfirmSecuredInstallmentEventListener
 */
class CheckoutConfirmSecuredInstallmentEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsInstallmentOptionsDataExtensionOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setId(PayoneSecuredInstallment::UUID);

        $installmentOptionsData = new SecuredInstallmentOptionsData();
        $installmentOptionsData->assign([
            'options' => [
                new SecuredInstallmentOption(),
            ],
        ]);

        $installmentService = $this->createMock(InstallmentService::class);
        $installmentService->expects(static::once())->method('getInstallmentOptions')->willReturn($installmentOptionsData);

        $listener = new CheckoutConfirmSecuredInstallmentEventListener($installmentService);

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertTrue($event->getPage()->hasExtension(SecuredInstallmentOptionsData::EXTENSION_NAME));
        static::assertCount(1, $event->getPage()->getExtension(SecuredInstallmentOptionsData::EXTENSION_NAME)->getOptions());
    }

    public function testItRemovesInstallmentPaymentMethodOnMissingOptions(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getPaymentMethod()->setId(PayoneSecuredInstallment::UUID);

        $installmentOptionsData = new SecuredInstallmentOptionsData();
        $installmentOptionsData->assign([
            'options' => [],
        ]);

        $installmentService = $this->createMock(InstallmentService::class);
        $installmentService->expects(static::once())->method('getInstallmentOptions')->willReturn($installmentOptionsData);

        $listener = new CheckoutConfirmSecuredInstallmentEventListener($installmentService);

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener->addPayonePageData($event);

        static::assertFalse($event->getPage()->hasExtension(SecuredInstallmentOptionsData::EXTENSION_NAME));
        static::assertSame(2, $event->getPage()->getPaymentMethods()->count());
        static::assertNotInPaymentCollection(
            PayoneSecuredInstallmentPaymentHandler::class,
            $event->getPage()->getPaymentMethods()
        );
    }

    protected function setPaymentMethods(Page $page): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod2 = new PaymentMethodEntity();
        $paymentMethod3 = new PaymentMethodEntity();

        $paymentMethod1->setId(PayoneDebit::UUID);
        $paymentMethod1->setHandlerIdentifier(PayoneDebitPaymentHandler::class);
        $paymentMethod2->setId(PayoneSecuredInvoice::UUID);
        $paymentMethod2->setHandlerIdentifier(PayoneSecuredInvoicePaymentHandler::class);
        $paymentMethod3->setId(PayoneSecuredInstallment::UUID);
        $paymentMethod3->setHandlerIdentifier(PayoneSecuredInstallmentPaymentHandler::class);

        $page->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
            $paymentMethod3,
        ]));
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
