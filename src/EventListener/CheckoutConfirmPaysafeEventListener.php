<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Payone\Client\PayoneClient;
use PayonePayment\Payone\Request\PaysafeInstallment\PaysafePreCheckRequestFactory;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPaysafeEventListener implements EventSubscriberInterface
{
    /** @var PaysafePreCheckRequestFactory */
    private $requestFactory;

    /** @var PayoneClient */
    private $client;

    public function __construct(
        PaysafePreCheckRequestFactory $paysafePreCheckRequestFactory,
        PayoneClient $client
    ) {
        $this->requestFactory = $paysafePreCheckRequestFactory;
        $this->client         = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'preCheck',
        ];
    }

    public function preCheck(CheckoutConfirmPageLoadedEvent $event)
    {
        // TODO: call precheck if needed

        $dataBag = new DataBag();
    }
}
