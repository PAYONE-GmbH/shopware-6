<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Ratepay\DeviceFingerprint\DeviceFingerprintServiceInterface;
use PayonePayment\Components\Ratepay\Installment\InstallmentServiceInterface;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayoneRatepayDebit;
use PayonePayment\PaymentMethod\PayoneRatepayInstallment;
use PayonePayment\PaymentMethod\PayoneRatepayInvoicing;
use PayonePayment\Storefront\Struct\RatepayDeviceFingerprintData;
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
    /** @var SystemConfigService */
    protected $systemConfigService;

    /** @var InstallmentServiceInterface */
    protected $installmentService;

    /** @var DeviceFingerprintServiceInterface */
    protected $deviceFingerprintService;

    public function __construct(
        SystemConfigService $systemConfigService,
        InstallmentServiceInterface $installmentService,
        DeviceFingerprintServiceInterface $deviceFingerprintService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->installmentService       = $installmentService;
        $this->deviceFingerprintService = $deviceFingerprintService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['hidePaymentMethodsForCompanies'],
                ['addPayonePageData'],
            ],
            AccountEditOrderPageLoadedEvent::class => [
                ['hidePaymentMethodsForCompanies'],
                ['addPayonePageData'],
            ],
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethodsForCompanies',
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

        if ($context->getPaymentMethod()->getId() === PayoneRatepayInstallment::UUID) {
            $this->addInstallmentCalculatorData($page, $context);
        }

        if (in_array($context->getPaymentMethod()->getHandlerIdentifier(), PaymentHandlerGroups::RATEPAY, true)) {
            $this->addDeviceFingerprintData($page, $context);
        }
    }

    protected function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentMethodIds): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static function (PaymentMethodEntity $paymentMethod) use ($paymentMethodIds) {
                return !in_array($paymentMethod->getId(), $paymentMethodIds, true);
            }
        );
    }

    protected function customerHasCompanyAddress(SalesChannelContext $context): bool
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

    protected function addInstallmentCalculatorData(Page $page, SalesChannelContext $context): void
    {
        if (
            !method_exists($page, 'getPaymentMethods') ||
            !method_exists($page, 'setPaymentMethods')
        ) {
            return;
        }

        $installmentCalculator = $this->installmentService->getInstallmentCalculatorData($context);

        if ($installmentCalculator === null) {
            $paymentMethods = $this->removePaymentMethods($page->getPaymentMethods(), [
                PayoneRatepayInstallment::UUID,
            ]);
            $page->setPaymentMethods($paymentMethods);

            return;
        }

        $page->addExtension(RatepayInstallmentCalculatorData::EXTENSION_NAME, $installmentCalculator);
    }

    protected function addDeviceFingerprintData(Page $page, SalesChannelContext $context): void
    {
        if ($this->deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated() === false) {
            $snippetId = $this->systemConfigService->get(
                ConfigReader::getConfigKeyByPaymentHandler(
                    PayoneRatepayInvoicingPaymentHandler::class,
                    'DeviceFingerprintSnippetId'
                ),
                $context->getSalesChannelId()
            ) ?? 'ratepay';

            $deviceIdentToken = $this->deviceFingerprintService->getDeviceIdentToken();
            $snippet = $this->deviceFingerprintService->getDeviceIdentSnippet($snippetId, $deviceIdentToken);

            $extension = new RatepayDeviceFingerprintData();
            $extension->setSnippet($snippet);

            $page->addExtension(RatepayDeviceFingerprintData::EXTENSION_NAME, $extension);
        }
    }
}
