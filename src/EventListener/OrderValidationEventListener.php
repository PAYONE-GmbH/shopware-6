<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use DateTime;
use DateTimeInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\PaymentMethod;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use PayonePayment\PaymentMethod\PayoneSecureInvoice;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationEventListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(RequestStack $requestStack, ConfigReaderInterface $configReader)
    {
        $this->requestStack = $requestStack;
        $this->configReader = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.order.create' => 'validateOrderData',
        ];
    }

    public function validateOrderData(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $context = $this->getContextFromRequest($request);

        $this->addSecureInvoiceValidationDefinitions($context, $event);
        $this->addPayolutionInvoicingValidationDefinitions($context, $event);
        $this->addPayolutionInstallmentValidationDefinitions($context, $event);
    }

    private function addSecureInvoiceValidationDefinitions(SalesChannelContext $salesChannelContext, BuildValidationEvent $event): void
    {
        $customer = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return;
        }

        if ($this->isSecureInvoicePayment($salesChannelContext) === false) {
            return;
        }

        $activeBilling = $customer->getActiveBillingAddress();

        if ($activeBilling !== null && empty($activeBilling->getCompany())) {
            $event->getDefinition()->add(
                'secureInvoiceBirthday',
                new Birthday(['value' => $this->getMinimumDate()])
            );
        }
    }

    private function addPayolutionInvoicingValidationDefinitions(SalesChannelContext $salesChannelContext, BuildValidationEvent $event): void
    {
        if ($this->isPayonePayolutionInvoicing($salesChannelContext) === false) {
            return;
        }

        $event->getDefinition()->add(
            'payolutionConsent',
            new NotBlank()
        );

        $event->getDefinition()->add(
            'payolutionBirthday',
            new Birthday(['value' => $this->getMinimumDate()])
        );

        if ($this->companyDataHandlingIsDisabled($salesChannelContext) === false) {
            return;
        }

        if ($this->customerHasCompanyAddress($salesChannelContext)) {
            $event->getDefinition()->add(
                'payonePaymentMethod',
                new PaymentMethod(['value' => $salesChannelContext->getPaymentMethod()])
            );
        }
    }

    private function addPayolutionInstallmentValidationDefinitions(SalesChannelContext $salesChannelContext, BuildValidationEvent $event): void
    {
        if ($this->isPayonePayolutionInstallment($salesChannelContext) === false) {
            return;
        }

        $event->getDefinition()->add(
            'payolutionConsent',
            new NotBlank()
        );

        $event->getDefinition()->add(
            'payolutionBirthday',
            new Birthday(['value' => $this->getMinimumDate()])
        );

        if ($this->customerHasCompanyAddress($salesChannelContext)) {
            $event->getDefinition()->add(
                'payonePaymentMethod',
                new PaymentMethod(['value' => $salesChannelContext->getPaymentMethod()])
            );
        }
    }

    private function getMinimumDate(): DateTimeInterface
    {
        return (new DateTime())->modify('-18 years')->setTime(0, 0);
    }

    private function getContextFromRequest(Request $request): SalesChannelContext
    {
        return $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }

    private function isPayonePayolutionInstallment(SalesChannelContext $context): bool
    {
        return $context->getPaymentMethod()->getId() === PayonePayolutionInstallment::UUID;
    }

    private function isPayonePayolutionInvoicing(SalesChannelContext $context): bool
    {
        return $context->getPaymentMethod()->getId() === PayonePayolutionInvoicing::UUID;
    }

    private function isSecureInvoicePayment(SalesChannelContext $context): bool
    {
        return $context->getPaymentMethod()->getId() === PayoneSecureInvoice::UUID;
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

    private function companyDataHandlingIsDisabled(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !((bool) $configuration->get('payolutionInvoicingTransferCompanyData'));
    }
}
