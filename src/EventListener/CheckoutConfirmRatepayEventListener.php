<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Ratepay\InstallmentServiceInterface;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\Storefront\Struct\RatepayInstallmentCalculatorData;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmRatepayEventListener implements EventSubscriberInterface
{
    /** @var InstallmentServiceInterface */
    private $installmentService;

    public function __construct(InstallmentServiceInterface $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['hidePaymentMethodsForCompanies'],
                ['addPayonePageData'],
            ],
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethodsForCompanies',
            AccountEditOrderPageLoadedEvent::class     => 'hidePaymentMethodsForCompanies',
        ];
    }

    public function hidePaymentMethodsForCompanies(PageLoadedEvent $event): void
    {
        $page = $event->getPage();

        if (
            !method_exists($page, 'getPaymentMethods') ||
            !method_exists($page, 'setPaymentMethods')
        ) {
            return;
        }

        if (!$this->customerHasCompanyAddress($event->getSalesChannelContext())) {
            return;
        }

        $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), [
            PayoneRatepayDebit::UUID,
            PayoneRatepayInstallment::UUID,
            PayoneRatepayInvoicing::UUID,
        ]);

        $page->setPaymentMethods($paymentMethods);
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() !== PayoneRatepayInstallment::UUID) {
            return;
        }

        $installmentCalculator = $this->installmentService->getInstallmentCalculatorData();

        $page->addExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME, $installmentCalculator);
    }

    private function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentMethodIds): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentMethodIds) {
                return !in_array($paymentMethod->getId(), $paymentMethodIds, true);
            }
        );
    }

    private function customerHasCompanyAddress(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return false;
        }

        return !empty($billingAddress->getCompany());
    }
}
