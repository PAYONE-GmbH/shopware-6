<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClient;
use PayonePayment\Payone\Request\PaysafeInstallment\PaysafePreCheckRequestFactory;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class CheckoutConfirmPaysafeEventListener implements EventSubscriberInterface
{
    /** @var PaysafePreCheckRequestFactory */
    private $requestFactory;

    /** @var PayoneClient */
    private $client;

    public function __construct(
        PaysafePreCheckRequestFactory $requestFactory,
        PayoneClient $client
    ) {
        $this->requestFactory = $requestFactory;
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
        return;

        // TODO: call precheck if needed

        $dataBag = new RequestDataBag();

        $request = $this->requestFactory->getRequestParameters(
            $event->getPage()->getCart(),
            $dataBag,
            $event->getSalesChannelContext()
        );

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            // TODO: error handling
        } catch (Throwable $exception) {
            // TODO: error handling
        }
    }
}
