<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\Installment\InstallmentServiceInterface;
use PayonePayment\Components\Ratepay\Profile\ProfileServiceInterface;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmRatepayEventListener implements EventSubscriberInterface
{
    protected SystemConfigService $systemConfigService;

    protected InstallmentServiceInterface $installmentService;

    protected ProfileServiceInterface $profileService;

    public function __construct(
        SystemConfigService $systemConfigService,
        InstallmentServiceInterface $installmentService,
        ProfileServiceInterface $profileService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->installmentService = $installmentService;
        $this->profileService = $profileService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['hidePaymentMethodsForCompanies'],
                ['hidePaymentMethodsByProfiles'],
                ['addPayonePageData'],
            ],
            AccountEditOrderPageLoadedEvent::class => [
                ['hidePaymentMethodsForCompanies'],
                ['hidePaymentMethodsByProfiles'],
                ['addPayonePageData'],
            ],
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethodsForCompanies',
        ];
    }

    public function hidePaymentMethodsForCompanies(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if (
            !method_exists($page, 'getPaymentMethods')
            || !method_exists($page, 'setPaymentMethods')
        ) {
            return;
        }

        if (!$this->customerHasCompanyAddress($event->getSalesChannelContext())) {
            return;
        }

        $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), PaymentHandlerGroups::RATEPAY);

        $page->setPaymentMethods($paymentMethods);
    }

    public function hidePaymentMethodsByProfiles(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if (
            !method_exists($page, 'getPaymentMethods')
            || !method_exists($page, 'setPaymentMethods')
        ) {
            return;
        }

        $paymentMethods = $page->getPaymentMethods();

        foreach (PaymentHandlerGroups::RATEPAY as $ratepayPaymentHandler) {
            $profile = $this->profileService->getProfileBySalesChannelContext(
                $event->getSalesChannelContext(),
                $ratepayPaymentHandler
            );

            if (!$profile) {
                $paymentMethods = $this->removePaymentMethods($paymentMethods, [$ratepayPaymentHandler]);
            }
        }

        $page->setPaymentMethods($paymentMethods);
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        /** @var Page $page */
        $page = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() === PayoneRatepayInstallment::UUID) {
            $this->addInstallmentCalculatorData($page, $context);
        }
    }

    protected function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentHandler): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return !\in_array($paymentMethod->getHandlerIdentifier(), $paymentHandler, true);
            }
        );
    }

    protected function customerHasCompanyAddress(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return false;
        }

        return !empty($billingAddress->getCompany());
    }

    protected function addInstallmentCalculatorData(Page $page, SalesChannelContext $context): void
    {
        if (
            !method_exists($page, 'getPaymentMethods')
            || !method_exists($page, 'setPaymentMethods')
        ) {
            return;
        }

        $installmentCalculator = $this->installmentService->getInstallmentCalculatorData($context);

        if ($installmentCalculator === null) {
            $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), [
                PayoneRatepayInstallmentPaymentHandler::class,
            ]);
            $page->setPaymentMethods($paymentMethods);

            return;
        }

        $page->addExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME, $installmentCalculator);
    }
}
