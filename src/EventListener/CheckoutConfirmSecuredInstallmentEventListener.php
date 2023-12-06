<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\SecuredInstallment\InstallmentServiceInterface;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentMethod\PayoneSecuredInstallment;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmSecuredInstallmentEventListener implements EventSubscriberInterface
{
    public function __construct(protected InstallmentServiceInterface $installmentService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['addPayonePageData'],
            ],
        ];
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() === PayoneSecuredInstallment::UUID) {
            $this->addInstallmentOptionsData($page, $context);
        }
    }

    protected function addInstallmentOptionsData(CheckoutConfirmPage $page, SalesChannelContext $context): void
    {
        $installmentOptions = $this->installmentService->getInstallmentOptions($context);

        if (\count($installmentOptions->getOptions()) > 0) {
            $page->addExtension(SecuredInstallmentOptionsData::EXTENSION_NAME, $installmentOptions);
        } else {
            $page->setPaymentMethods(
                $this->removePaymentMethods(
                    $page->getPaymentMethods(),
                    [PayoneSecuredInstallmentPaymentHandler::class]
                )
            );
        }
    }

    protected function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $paymentHandler): PaymentMethodCollection
    {
        return $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => !\in_array($paymentMethod->getHandlerIdentifier(), $paymentHandler, true)
        );
    }
}
