<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayonePaysafeInstallment;
use PayonePayment\PaymentMethod\PayonePaysafeInvoicing;
use PayonePayment\Payone\Client\PayoneClient;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Payone\Request\PaysafeInstallment\PaysafePreCheckRequestFactory;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
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
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'preCheck'
        ];
    }

    public function preCheck(CheckoutConfirmPageLoadedEvent $event)
    {
        // TODO: call precheck if needed

        $dataBag = new DataBag();
    }
}
