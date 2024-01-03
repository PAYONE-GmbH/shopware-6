<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPayolutionEventListener implements EventSubscriberInterface
{
    public function __construct(private readonly ConfigReaderInterface $configReader)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'hidePaymentMethods',
            AccountPaymentMethodPageLoadedEvent::class => 'hidePaymentMethods',
            AccountEditOrderPageLoadedEvent::class => 'hidePaymentMethods',
        ];
    }

    public function hidePaymentMethods(
        CheckoutConfirmPageLoadedEvent|AccountPaymentMethodPageLoadedEvent|AccountEditOrderPageLoadedEvent $event
    ): void {
        $page = $event->getPage();

        $paymentMethods = $page->getPaymentMethods();

        if ($this->companyNameMissing($event->getSalesChannelContext(), PayonePayolutionInvoicingPaymentHandler::class)) {
            $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePayolutionInvoicing::UUID);
        }

        if ($this->companyNameMissing($event->getSalesChannelContext(), PayonePayolutionDebitPaymentHandler::class)) {
            $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePayolutionDebit::UUID);
        }

        if ($this->companyNameMissing($event->getSalesChannelContext(), PayonePayolutionInstallmentPaymentHandler::class)) {
            $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePayolutionInstallment::UUID);
        }

        if ($this->companyDataHandlingIsDisabled($event->getSalesChannelContext())) {
            $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePayolutionInvoicing::UUID);
        }

        $page->setPaymentMethods($paymentMethods);
    }

    private function removePaymentMethod(PaymentMethodCollection $paymentMethods, string $paymentMethodId): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => $paymentMethod->getId() !== $paymentMethodId
        );
    }

    private function companyDataHandlingIsDisabled(SalesChannelContext $context): bool
    {
        return !($this->getConfiguration($context, 'payolutionInvoicingTransferCompanyData'));
    }

    private function companyNameMissing(SalesChannelContext $context, string $paymentHandler): bool
    {
        return empty($this->getConfiguration($context, ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentHandler] . 'CompanyName'));
    }

    private function getConfiguration(SalesChannelContext $context, string $configName): array|bool|int|string|null
    {
        return $this->configReader->read($context->getSalesChannel()->getId())->get($configName);
    }
}
