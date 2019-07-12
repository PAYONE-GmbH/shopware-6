<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Payone\Request\ManageMandate\ManageMandateRequestFactory;
use PayonePayment\Struct\PayonePaymentData;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    /** @var CreditCardCheckRequestFactory */
    private $requestFactory;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var CardRepositoryInterface */
    private $cardRepository;

    public function __construct(
        CreditCardCheckRequestFactory $requestFactory,
        EntityRepositoryInterface $languageRepository,
        CardRepositoryInterface $cardRepository
    ) {
        $this->requestFactory     = $requestFactory;
        $this->languageRepository = $languageRepository;
        $this->cardRepository     = $cardRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(CheckoutConfirmPageLoadedEvent $event)
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $context             = $salesChannelContext->getContext();

        $cardRequest = $this->requestFactory->getRequestParameters($salesChannelContext);
        $savedCards  = $this->cardRepository->getCards($salesChannelContext->getCustomer(), $context);

        $payoneData = new PayonePaymentData();
        $payoneData->assign([
            'cardRequest' => $cardRequest,
            'language'    => $this->getCustomerLanguage($context),
            'savedCards'  => $savedCards,
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
