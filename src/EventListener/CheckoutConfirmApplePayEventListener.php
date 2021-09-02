<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Core\Utils\PayoneClassLoader;
use PayonePayment\PaymentMethod\PayoneApplePay;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Sinergi\BrowserDetector\Browser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    (new PayoneClassLoader())->register();
}

class CheckoutConfirmApplePayEventListener implements EventSubscriberInterface
{
    use RemovesPaymentMethod;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => 'hideApplePayForNonSafariUsers',
            AccountPaymentMethodPageLoadedEvent::class => 'hideApplePayForNonSafariUsers',
        ];
    }

    /**
     * @param AccountPaymentMethodPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function hideApplePayForNonSafariUsers($event): void
    {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        $request   = $event->getRequest();
        $userAgent = $request->server->get('HTTP_USER_AGENT');

        $browser = (new Browser($userAgent))->getName();

        if ($browser === Browser::SAFARI && $this->isSetup() === true) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayoneApplePay::UUID);
        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    private function isSetup(): bool
    {
        if (!file_exists(__DIR__ . '/../apple-pay-cert/merchant_id.key')) {
            return false;
        }

        if (!file_exists(__DIR__ . '/../apple-pay-cert/merchant_id.pem')) {
            return false;
        }

        return true;
    }
}
