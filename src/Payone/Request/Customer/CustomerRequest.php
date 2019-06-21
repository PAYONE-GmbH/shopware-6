<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerRequest
{
    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var EntityRepositoryInterface */
    private $addressRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var EntityRepositoryInterface */
    private $salutationRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    public function __construct(
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $addressRepository,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $countryRepository,
        RequestStack $requestStack
    ) {
        $this->languageRepository   = $languageRepository;
        $this->addressRepository    = $addressRepository;
        $this->salutationRepository = $salutationRepository;
        $this->countryRepository    = $countryRepository;
        $this->requestStack         = $requestStack;
    }

    public function getRequestParameters(OrderEntity $order, Context $context): array
    {
        $language       = $this->getCustomerLanguage($context);
        $billingAddress = $this->getCustomerBillingAddress($context, $order);

        $personalData = [
            'salutation'      => $this->getCustomerSalutation($billingAddress, $context)->getDisplayName(),
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $this->getCustomerCurrency($billingAddress, $context)->getIso(),
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

    private function getCustomerSalutation(OrderAddressEntity $addressEntity, Context $context): SalutationEntity
    {
        $criteria = new Criteria([$addressEntity->getSalutationId()]);

        /** @var null|SalutationEntity $language */
        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (null === $salutation) {
            throw new RuntimeException('missing order customer salutation');
        }

        return $salutation;
    }

    private function getCustomerCurrency(OrderAddressEntity $addressEntity, Context $context): CountryEntity
    {
        $criteria = new Criteria([$addressEntity->getCountryId()]);

        /** @var null|CurrencyEntity $language */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (null === $country) {
            throw new RuntimeException('missing order country entity');
        }

        return $country;
    }

    private function getCustomerLanguage(Context $context): LanguageEntity
    {
        $criteria = new Criteria([$context->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var null|LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        if (null === $language) {
            throw new RuntimeException('missing order customer language');
        }

        return $language;
    }

    private function getCustomerBillingAddress(Context $context, OrderEntity $order): OrderAddressEntity
    {
        $criteria = new Criteria([$order->getBillingAddressId()]);

        /** @var null|OrderAddressEntity $billingAddress */
        $billingAddress = $this->addressRepository->search($criteria, $context)->first();

        if (null === $billingAddress) {
            throw new RuntimeException('missing order billing address');
        }

        return $billingAddress;
    }
}
