<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ApplePay\StoreApi\Route\ApplePayRoute;
use PayonePayment\Core\Utils\PayoneClassLoader;
use PayonePayment\PaymentMethod\PayoneApplePay;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
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

    public function __construct(private readonly string $kernelDirectory)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hideApplePayForNonSafariUsers',
            AccountPaymentMethodPageLoadedEvent::class => 'hideApplePayForNonSafariUsers',
            AccountEditOrderPageLoadedEvent::class => 'hideApplePayForNonSafariUsers',
        ];
    }

    public function hideApplePayForNonSafariUsers(
        CheckoutConfirmPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|AccountEditOrderPageLoadedEvent $event
    ): void {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        $request = $event->getRequest();
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
        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.key')) {
            return false;
        }

        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.pem')) {
            return false;
        }

        return true;
    }
}
