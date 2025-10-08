<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler;
use PayonePayment\Provider\Payone\PaymentMethod\SecuredInstallmentPaymentMethod;
use PayonePayment\Provider\Payone\Service\SecuredInstallmentService;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmSecuredInstallmentEventListener implements EventSubscriberInterface
{
    public function __construct(
        protected SecuredInstallmentService $installmentService,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if (SecuredInstallmentPaymentMethod::UUID === $context->getPaymentMethod()->getId()) {
            $this->addInstallmentOptionsData($page, $context);
        }
    }

    protected function addInstallmentOptionsData(CheckoutConfirmPage $page, SalesChannelContext $context): void
    {
        $installmentOptions = $this->installmentService->getInstallmentOptions($context);

        if (\count($installmentOptions->getOptions()) > 0) {
            $page->addExtension(SecuredInstallmentOptionsData::EXTENSION_NAME, $installmentOptions);
        } else {
            $page->setPaymentMethods($this->removePaymentMethods(
                $page->getPaymentMethods(),
                [ SecuredInstallmentPaymentHandler::class ],
            ));
        }
    }

    protected function removePaymentMethods(
        PaymentMethodCollection $paymentMethods,
        array $paymentHandler,
    ): PaymentMethodCollection {
        return $paymentMethods->filter(
            static fn (PaymentMethodEntity $paymentMethod) => !\in_array(
                $paymentMethod->getHandlerIdentifier(),
                $paymentHandler,
                true,
            ),
        );
    }
}
