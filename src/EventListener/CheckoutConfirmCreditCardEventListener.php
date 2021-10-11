<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\CreditCardCheckStruct;
use PayonePayment\StoreApi\Route\AbstractCardRoute;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmCreditCardEventListener implements EventSubscriberInterface
{
    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var AbstractCardRoute */
    private $cardRoute;

    public function __construct(
        RequestParameterFactory $requestParameterFactory,
        EntityRepositoryInterface $languageRepository,
        AbstractCardRoute $cardRoute
    ) {
        $this->requestParameterFactory = $requestParameterFactory;
        $this->languageRepository      = $languageRepository;
        $this->cardRoute               = $cardRoute;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'addPayonePageData',
            AccountEditOrderPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(PageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if ($context->getPaymentMethod()->getId() !== PayoneCreditCard::UUID) {
            return;
        }

        $cardRequest = $this->requestParameterFactory->getRequestParameter(
            new CreditCardCheckStruct(
                $event->getSalesChannelContext(),
                PayoneCreditCardPaymentHandler::class
            )
        );

        if (null !== $context->getCustomer()) {
            $savedCards = $this->cardRoute->load($context)->getSearchResult();
        }

        $language = $this->getCustomerLanguage($context->getContext());

        if ($page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        if (null !== $payoneData) {
            $payoneData->assign([
                'cardRequest' => $cardRequest,
                'language'    => $language,
                'savedCards'  => !empty($savedCards) ? $savedCards : null,
            ]);
        }

        $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
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
