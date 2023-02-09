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
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmSecuredInstallmentEventListener implements EventSubscriberInterface
{
    protected InstallmentServiceInterface $installmentService;

    public function __construct(InstallmentServiceInterface $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['addPayonePageData'],
            ],
        ];
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        /** @var Page $page */
        $page = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() === PayoneSecuredInstallment::UUID) {
            $this->addInstallmentOptionsData($page, $context);
        }
    }

    protected function addInstallmentOptionsData(Page $page, SalesChannelContext $context): void
    {
        $installmentOptions = $this->installmentService->getInstallmentOptions($context);

        if (\count($installmentOptions->getOptions()) > 0) {
            $page->addExtension(SecuredInstallmentOptionsData::EXTENSION_NAME, $installmentOptions);
        } elseif (method_exists($page, 'getPaymentMethods') && method_exists($page, 'setPaymentMethods')) {
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
            static function (PaymentMethodEntity $paymentMethod) use ($paymentHandler) {
                return !\in_array($paymentMethod->getHandlerIdentifier(), $paymentHandler, true);
            }
        );
    }
}
