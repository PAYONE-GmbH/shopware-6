<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;
use PayonePayment\Provider\Payone\PaymentMethod\CreditCardPaymentMethod;
use PayonePayment\Provider\Payone\RequestParameter\CreditCardCheckRequestDto;
use PayonePayment\Provider\Payone\RequestParameter\RequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\StoreApi\Route\AbstractCardRoute;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmCreditCardEventListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestEnricher $requestEnricher,
        private RequestParameterEnricherChain $requestEnricherChain,
        private CreditCardPaymentHandler $paymentHandler,
        private EntityRepository $languageRepository,
        private AbstractCardRoute $cardRoute,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'addPayonePageData',
            AccountEditOrderPageLoadedEvent::class => 'addPayonePageData',
        ];
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): void
    {
        $page    = $event->getPage();
        $context = $event->getSalesChannelContext();

        if (CreditCardPaymentMethod::UUID !== $context->getPaymentMethod()->getId()) {
            return;
        }

        $cardRequest = $this->requestEnricher->enrich(
            new CreditCardCheckRequestDto(
                $context,
                $this->paymentHandler,
                true,
            ),

            $this->requestEnricherChain,
        );

        $savedCards = null;

        // Disable storage of credit card data
        // if (null !== $context->getCustomer()) {
        //     $savedCards = $this->cardRoute->load($context)->getSearchResult();
        // }

        $language = $this->getCustomerLanguage($context->getContext());

        if ($page->hasExtension(CheckoutCartPaymentData::EXTENSION_NAME)) {
            $payoneData = $page->getExtension(CheckoutCartPaymentData::EXTENSION_NAME);
        } else {
            $payoneData = new CheckoutConfirmPaymentData();
        }

        if (null !== $payoneData) {
            $payoneData->assign([
                'cardRequest' => $cardRequest->all(),
                'language'    => $language,
                'savedCards'  => $savedCards,
            ]);

            $page->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
        }
    }

    private function getCustomerLanguage(Context $context): string
    {
        $languages = $context->getLanguageId();
        $criteria  = new Criteria([$languages]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        if (null === $language || null === $language->getLocale()) {
            return 'en';
        }

        return \substr($language->getLocale()->getCode(), 0, 2);
    }
}
