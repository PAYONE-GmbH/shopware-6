<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\SecuredInstallment\InstallmentServiceInterface;
use PayonePayment\PaymentMethod\PayoneSecuredInstallment;
use PayonePayment\Storefront\Struct\SecuredInstallmentOptionsData;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
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
            AccountEditOrderPageLoadedEvent::class => [
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

        $page->addExtension(SecuredInstallmentOptionsData::EXTENSION_NAME, $installmentOptions);
    }
}
