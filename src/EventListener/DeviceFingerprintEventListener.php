<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\DeviceFingerprint\DeviceFingerprintServiceCollectionInterface;
use PayonePayment\Storefront\Struct\DeviceFingerprintData;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeviceFingerprintEventListener implements EventSubscriberInterface
{
    public function __construct(protected DeviceFingerprintServiceCollectionInterface $deviceFingerprintServiceCollection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => [
                ['addDeviceFingerprintData'],
            ],
            AccountEditOrderPageLoadedEvent::class => [
                ['addDeviceFingerprintData'],
            ],
        ];
    }

    public function addDeviceFingerprintData(PageLoadedEvent $event): void
    {
        /** @var Page $page */
        $page = $event->getPage();
        $salesChannelContext = $event->getSalesChannelContext();

        $deviceFingerprintService = $this->deviceFingerprintServiceCollection->getForPaymentHandler(
            $salesChannelContext->getPaymentMethod()->getHandlerIdentifier()
        );

        if ($deviceFingerprintService && !$deviceFingerprintService->isDeviceIdentTokenAlreadyGenerated()) {
            $deviceIdentToken = $deviceFingerprintService->getDeviceIdentToken($salesChannelContext);
            $snippet = $deviceFingerprintService->getDeviceIdentSnippet($deviceIdentToken, $salesChannelContext);

            $extension = new DeviceFingerprintData();
            $extension->setSnippet($snippet);

            $page->addExtension(DeviceFingerprintData::EXTENSION_NAME, $extension);
        }
    }
}
