<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PayonePaymentData;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Storefront\Event\CheckoutEvents;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckoutConfirmEventListener implements EventSubscriberInterface
{
    /** @var CreditCardCheckRequestFactory */
    private $requestFactory;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    public function __construct(
        CreditCardCheckRequestFactory $requestFactory,
        EntityRepositoryInterface $languageRepository
    ) {
        $this->requestFactory     = $requestFactory;
        $this->languageRepository = $languageRepository;
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

        if ($salesChannelContext->getPaymentMethod()->getId() !== PayoneCreditCard::UUID) {
            return;
        }

        $requestBag = new RequestDataBag($event->getRequest()->request->all());
        $paymentTransaction = new PaymentTransactionStruct();

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $requestBag,
            $salesChannelContext
        );

        $payoneData = new PayonePaymentData();
        $payoneData->assign([
            'cardRequest'   => $request,
            'language'      => $this->getCustomerLanguage($context),
            'paymentMethod' => $salesChannelContext->getPaymentMethod(),
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

        if (null === $language) {
            return 'en';
        }

        return substr($language->getLocale()->getCode(), 0, 2);
    }
}
