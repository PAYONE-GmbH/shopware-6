<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\EventListener;

use PayonePayment\PaymentHandler\CustomerHasCompanyAddressTrait;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentMethod\InstallmentPaymentMethod;
use PayonePayment\Provider\Ratepay\Service\InstallmentService;
use PayonePayment\Provider\Ratepay\Service\ProfileService;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmRatepayEventListener implements EventSubscriberInterface
{
    use CustomerHasCompanyAddressTrait;

    public function __construct(
        protected SystemConfigService $systemConfigService,
        protected InstallmentService $installmentService,
        protected ProfileService $profileService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class      => [
                [ 'hidePaymentMethodsForCompanies' ],
                [ 'hidePaymentMethodsByProfiles' ],
                [ 'addPayonePageData' ],
            ],
            AccountEditOrderPageLoadedEvent::class     => [
                [ 'hidePaymentMethodsForCompanies' ],
                [ 'hidePaymentMethodsByProfiles' ],
                [ 'addPayonePageData' ],
            ],
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethodsForCompanies',
        ];
    }

    public function hidePaymentMethodsForCompanies(
        CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent|AccountPaymentMethodPageLoadedEvent $event,
    ): void {
        $page = $event->getPage();

        if (!$this->customerHasCompanyAddress($event->getSalesChannelContext())) {
            return;
        }

        $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), PaymentHandlerGroups::RATEPAY);

        $page->setPaymentMethods($paymentMethods);
    }

    public function hidePaymentMethodsByProfiles(
        CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event,
    ): void {
        $page = $event->getPage();

        $paymentMethods = $page->getPaymentMethods();

        foreach (PaymentHandlerGroups::RATEPAY as $ratepayPaymentHandler) {
            $profile = $this->profileService->getProfileBySalesChannelContext(
                $event->getSalesChannelContext(),
                $ratepayPaymentHandler,
            );

            if (!$profile) {
                $paymentMethods = $this->removePaymentMethods($paymentMethods, [$ratepayPaymentHandler]);
            }
        }

        $page->setPaymentMethods($paymentMethods);
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if (InstallmentPaymentMethod::UUID === $context->getPaymentMethod()->getId()) {
            $this->addInstallmentCalculatorData($page, $context);
        }
    }

    protected function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentHandler): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => !\in_array($paymentMethod->getHandlerIdentifier(), $paymentHandler, true),
        );
    }

    protected function addInstallmentCalculatorData(
        CheckoutConfirmPage|AccountEditOrderPage $page,
        SalesChannelContext $context,
    ): void {
        $installmentCalculator = $this->installmentService->getInstallmentCalculatorData($context);

        if (null === $installmentCalculator) {
            $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), [
                InstallmentPaymentHandler::class,
            ]);
            $page->setPaymentMethods($paymentMethods);

            return;
        }

        $page->addExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME, $installmentCalculator);
    }
}
