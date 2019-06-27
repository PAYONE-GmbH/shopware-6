<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CardService\CardServiceInterface;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Struct\PayonePaymentData;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    /** @var CreditCardCheckRequestFactory */
    private $requestFactory;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var CardServiceInterface */
    private $cardService;

    public function __construct(
        CreditCardCheckRequestFactory $requestFactory,
        EntityRepositoryInterface $languageRepository,
        CardServiceInterface $cardService
    ) {
        $this->requestFactory     = $requestFactory;
        $this->languageRepository = $languageRepository;
        $this->cardService        = $cardService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutEvents::CHECKOUT_CONFIRM_PAGE_LOADED_EVENT => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(CheckoutConfirmPageLoadedEvent $event)
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $context             = $salesChannelContext->getContext();

        $request = $this->requestFactory->getRequestParameters($salesChannelContext);

        $cards = $this->cardService->getCards($salesChannelContext->getCustomer(), $context);

        $payoneData = new PayonePaymentData();
        $payoneData->assign([
            'cardRequest' => $request,
            'language'    => $this->getCustomerLanguage($context),
            'savedCards'  => $cards,
        ]);

        $event->getPage()->addExtension('payone', $payoneData);
    }

    private function getCustomerLanguage(Context $context): string
    {
        $languages = $context->getLanguageId();
        $criteria  = new Criteria([$languages]);
        $criteria->addAssociation('locale');

        /** @var null|LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        if (null === $language || null === $language->getLocale()) {
            return 'en';
        }

        return substr($language->getLocale()->getCode(), 0, 2);
    }
}
