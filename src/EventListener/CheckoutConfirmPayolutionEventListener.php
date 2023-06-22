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
        $paymentMethods = $this->removePaymentMethod($paymentMethods, PayonePayolutionInstallment::UUID);

        /* Remove Payolution payment methods if company name is missing */
        $paymentMethods = $this->removePaymentMethodIfMissing($paymentMethods, $event->getSalesChannelContext(), PayonePayolutionInvoicingPaymentHandler::class, PayonePayolutionInvoicing::UUID);
        $paymentMethods = $this->removePaymentMethodIfMissing($paymentMethods, $event->getSalesChannelContext(), PayonePayolutionDebitPaymentHandler::class, PayonePayolutionDebit::UUID);
        $paymentMethods = $this->removePaymentMethodIfMissing($paymentMethods, $event->getSalesChannelContext(), PayonePayolutionInstallmentPaymentHandler::class, PayonePayolutionInstallment::UUID);

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

    /**
     */
    private function removePaymentMethodIfMissing(
        PaymentMethodCollection $paymentMethods,
        SalesChannelContext $context,
        string $paymentHandlerClass,
        string $paymentMethodUUID
    ): PaymentMethodCollection|array {
        if ($this->companyNameMissing($context, $paymentHandlerClass)) {
            return $this->removePaymentMethod($paymentMethods, $paymentMethodUUID);
        }

        return $paymentMethods;
    }

    private function companyDataHandlingIsDisabled(SalesChannelContext $context): bool
    {
        return !($this->getConfiguration($context, 'payolutionInvoicingTransferCompanyData'));
    }

    private function companyNameMissing(SalesChannelContext $context, string $paymentHandler): bool
    {
        return empty($this->getConfiguration($context, ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentHandler] . 'CompanyName'));
    }

    private function getConfiguration(SalesChannelContext $context, $configName): array|bool|int|string
    {
        return $this->configReader->read($context->getSalesChannel()->getId())->get($configName);
    }
}
