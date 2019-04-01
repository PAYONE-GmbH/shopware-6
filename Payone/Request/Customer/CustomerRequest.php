<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use http\Exception\RuntimeException;
use PayonePayment\Payone\Request\RequestInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerRequest implements RequestInterface
{
    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        EntityRepositoryInterface $languageRepository,
        RequestStack $requestStack
    ) {
        $this->languageRepository      = $languageRepository;
        $this->requestStack            = $requestStack;
    }

    public function getParentRequest(): string
    {
        return SystemRequest::class;
    }

    /**
     * TODO: Validate if Order Entity is filled with all the required data, thow exceptions if not?
     * TODO: If errors are unavoidable, implement helper to retrieve a "correct" order entity with all needed data.
     */
    public function getRequestParameters($transaction, Context $context): array
    {
        $order = $transaction->getOrderTransaction()->getOrder();

        if (null === $order) {
            throw new InvalidOrderException($transaction->getOrderTransaction()->getOrderId());
        }

        $languages = $context->getLanguageIdChain();
        $criteria  = new Criteria([reset($languages)]);
        $criteria->addAssociation('language.locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        $address        = $order->getAddresses();
        $billingAddress = $address->get($order->getBillingAddressId());

        if (null === $billingAddress) {
            throw new RuntimeException('missing order billing address');
        }

        $personalData = [
            'salutation'      => $billingAddress->getSalutation()->getDisplayName(),
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $billingAddress->getCountry()->getIso(),
            'email'           => $order->getOrderCustomer()->getEmail(),
            'language'        => substr($language->getLocale()->getCode(), 0, 2),
            'ip'              => $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        $birthday = $order->getOrderCustomer()->getCustomer()->getBirthday();
        if (null !== $birthday) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return $personalData;
    }
}
