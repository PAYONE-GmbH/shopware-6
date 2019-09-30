<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Struct\CheckoutConfirmPaymentData;
use PayonePayment\Struct\PaypalExpressCartData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
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
            CheckoutConfirmPageLoadedEvent::class => [
                ['addPayonePageData'],
                ['hideInternalPaymentMethods'],
            ],
        ];
    }

    public function addPayonePageData(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $context             = $salesChannelContext->getContext();

        if (!$this->isPayonePayment($salesChannelContext->getPaymentMethod())) {
            return;
        }

        $cardRequest = $this->requestFactory->getRequestParameters($salesChannelContext);
        $savedCards  = $this->cardRepository->getCards($salesChannelContext->getCustomer(), $context);

        $language = $this->getCustomerLanguage($context);
        $template = $this->getTemplateFromPaymentMethod($salesChannelContext->getPaymentMethod());

        $payoneData = new CheckoutConfirmPaymentData();

        $payoneData->assign([
            'cardRequest' => $cardRequest,
            'language'    => $language,
            'savedCards'  => $savedCards,
            'template'    => $template,
        ]);

        /** @var null|PaypalExpressCartData $extension */
        $extension = $event->getPage()->getCart()->getExtension(PaypalExpressCartData::EXTENSION_NAME);

        if (null !== $extension) {
            $payoneData->assign([
                'workOrderId' => $extension->getWorkorderId(),
                'cartHash'    => $extension->getCartHash(),
            ]);
        }

        $event->getPage()->addExtension(CheckoutConfirmPaymentData::EXTENSION_NAME, $payoneData);
    }

    public function hideInternalPaymentMethods(CheckoutConfirmPageLoadedEvent $event)
    {
        $internalPaymentMethods = [
            PayonePaypalExpress::UUID,
        ];

        $salesChannelContext = $event->getSalesChannelContext();

        $event->getPage()->setPaymentMethods(
            $event->getPage()->getPaymentMethods()->filter(
                static function (PaymentMethodEntity $entity) use ($internalPaymentMethods, $salesChannelContext) {
                    if ($salesChannelContext->getPaymentMethod()->getId() === $entity->getId()) {
                        return true;
                    }

                    return !in_array($entity->getId(), $internalPaymentMethods, true);
                }
            )
        );
    }

    private function getTemplateFromPaymentMethod(PaymentMethodEntity $paymentMethod): ?string
    {
        $customFields = $paymentMethod->getCustomFields();

        if (!empty($customFields[CustomFieldInstaller::TEMPLATE])) {
            return $customFields[CustomFieldInstaller::TEMPLATE];
        }

        return null;
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

    private function isPayonePayment(PaymentMethodEntity $paymentMethod): bool
    {
        $customFields = $paymentMethod->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::IS_PAYONE])) {
            return false;
        }

        if (!$customFields[CustomFieldInstaller::IS_PAYONE]) {
            return false;
        }

        return true;
    }
}
