<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\Payone\PaymentHandler\CreditCardPaymentHandler;
use PayonePayment\Provider\Payone\PaymentMethod\CreditCardPaymentMethod;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\PaymentRequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\StoreApi\Route\AbstractCardRoute;
use PayonePayment\Storefront\Struct\CheckoutCartPaymentData;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutConfirmCreditCardEventListener implements EventSubscriberInterface
{
    public function __construct(
        private PaymentRequestEnricher $paymentRequestEnricher,
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

        $cardRequest = $this->paymentRequestEnricher->enrich(
            new PaymentRequestDto(
                new PaymentTransactionDto(new OrderTransactionEntity(), new OrderEntity(), []),
                new RequestDataBag(),
                $context,
                $page->getCart(),
                $this->paymentHandler,
                clientApiRequest: true,
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
