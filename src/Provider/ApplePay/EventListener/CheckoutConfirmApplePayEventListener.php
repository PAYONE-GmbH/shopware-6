<?php

declare(strict_types=1);

namespace PayonePayment\Provider\ApplePay\EventListener;

use PayonePayment\EventListener\RemovesPaymentMethod;
use PayonePayment\Provider\ApplePay\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\ApplePay\StoreApi\Route\ApplePayRoute;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UAParser\Parser;

class CheckoutConfirmApplePayEventListener implements EventSubscriberInterface
{
    use RemovesPaymentMethod;

    public function __construct(
        private readonly string $kernelDirectory,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'hideApplePayForNonSafariUsers',
            AccountEditOrderPageLoadedEvent::class => 'hideApplePayForNonSafariUsers',
        ];
    }

    public function hideApplePayForNonSafariUsers(
        CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event,
    ): void {
        $paymentMethods = $event->getPage()->getPaymentMethods();

        $request   = $event->getRequest();
        $userAgent = $request->server->get('HTTP_USER_AGENT');

        if ($this->isSafariBrowser($userAgent) && true === $this->isSetup()) {
            return;
        }

        $paymentMethods = $this->removePaymentMethod($paymentMethods, StandardPaymentMethod::UUID);

        $event->getPage()->setPaymentMethods($paymentMethods);
    }

    private function isSafariBrowser(string $userAgent): bool
    {
        return 'safari' === \strtolower((Parser::create())->parse($userAgent)->ua->family);
    }

    private function isSetup(): bool
    {
        if (!\file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.key')) {
            return false;
        }

        if (!\file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.pem')) {
            return false;
        }

        return true;
    }
}
