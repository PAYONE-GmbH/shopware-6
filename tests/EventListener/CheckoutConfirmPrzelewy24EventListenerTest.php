<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayonePrzelewy24;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \PayonePayment\EventListener\CheckoutConfirmPrzelewy24EventListener
 */
class CheckoutConfirmPrzelewy24EventListenerTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItHidesPaymentMethodForNotAllowedCountryOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('DE');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItHidesPaymentMethodForNotAllowedCountryOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('DE');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItHidesPaymentMethodForNotAllowedCountryOnAccountPaymentMethodPage(): void
    {
        $page = new AccountPaymentMethodPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('DE');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItHidesPaymentMethodForNotAllowedCurrencyOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItHidesPaymentMethodForNotAllowedCurrencyOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItHidesPaymentMethodForNotAllowedCurrencyOnAccountPaymentMethodPage(): void
    {
        $page = new AccountPaymentMethodPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('EUR');

        $event = new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertNotInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItNotHidesPaymentMethodForAllowedCountryAndCurrencyOnCheckoutConfirmPage(): void
    {
        $page = new CheckoutConfirmPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new CheckoutConfirmPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItNotHidesPaymentMethodForAllowedCountryAndCurrencyOnAccountEditOrderPage(): void
    {
        $page = new AccountEditOrderPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new AccountEditOrderPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    public function testItNotHidesPaymentMethodForAllowedCountryAndCurrencyOnAccountPaymentMethodPage(): void
    {
        $page = new AccountPaymentMethodPage();
        $this->setPaymentMethods($page);

        $country = new CountryEntity();
        $country->setIso('PL');

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $salesChannelContext->getCustomer()->getActiveBillingAddress()->setCountry($country);
        $salesChannelContext->getCurrency()->setIsoCode('PLN');

        $event = new AccountPaymentMethodPageLoadedEvent($page, $salesChannelContext, new Request());
        $listener = $this->getContainer()->get(CheckoutConfirmPrzelewy24EventListener::class);

        $listener->hidePaymentMethod($event);

        static::assertInPaymentCollection(PayonePrzelewy24PaymentHandler::class, $event->getPage()->getPaymentMethods());
        static::assertInPaymentCollection(PayoneDebitPaymentHandler::class, $event->getPage()->getPaymentMethods());
    }

    protected function setPaymentMethods(Page $page): void
    {
        $paymentMethod1 = new PaymentMethodEntity();
        $paymentMethod2 = new PaymentMethodEntity();

        $paymentMethod1->setId(PayoneDebit::UUID);
        $paymentMethod1->setHandlerIdentifier(PayoneDebitPaymentHandler::class);
        $paymentMethod2->setId(PayonePrzelewy24::UUID);
        $paymentMethod2->setHandlerIdentifier(PayonePrzelewy24PaymentHandler::class);

        $page->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod1,
            $paymentMethod2,
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
