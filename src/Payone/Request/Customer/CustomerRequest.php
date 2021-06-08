<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerRequest
{
    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var EntityRepositoryInterface */
    private $salutationRepository;

    /** @var EntityRepositoryInterface */
    private $countryRepository;

    public function __construct(
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $countryRepository,
        RequestStack $requestStack
    ) {
        $this->languageRepository   = $languageRepository;
        $this->salutationRepository = $salutationRepository;
        $this->countryRepository    = $countryRepository;
        $this->requestStack         = $requestStack;
    }

    public function getRequestParameters(SalesChannelContext $context): array
    {
        if (null === $context->getCustomer()) {
            throw new RuntimeException('missing customer');
        }

        $language = $this->getCustomerLanguage($context);

        if (null === $language->getLocale()) {
            throw new RuntimeException('missing language locale');
        }

        $billingAddress = $context->getCustomer()->getActiveBillingAddress();

        if (null === $billingAddress) {
            throw new RuntimeException('missing customer billing address');
        }

        $personalData = [
            'company'         => $billingAddress->getCompany(),
            'salutation'      => $this->getCustomerSalutation($billingAddress, $context->getContext())->getDisplayName(),
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $this->getCustomerCountry($billingAddress, $context->getContext())->getIso(),
            'email'           => $context->getCustomer()->getEmail(),
            'language'        => substr($language->getLocale()->getCode(), 0, 2),
            'ip'              => null !== $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        $birthday = $context->getCustomer()->getBirthday();

        if (null !== $birthday) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return array_filter($personalData);
    }

    private function getCustomerSalutation(CustomerAddressEntity $addressEntity, Context $context): SalutationEntity
    {
        $criteria = new Criteria([$addressEntity->getSalutationId()]);

        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (null === $salutation) {
            throw new RuntimeException('missing order customer salutation');
        }

        return $salutation;
    }

    private function getCustomerCountry(CustomerAddressEntity $addressEntity, Context $context): CountryEntity
    {
        $criteria = new Criteria([$addressEntity->getCountryId()]);

        /** @var null|CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (null === $country) {
            throw new RuntimeException('missing order country entity');
        }

        return $country;
    }

    private function getCustomerLanguage(SalesChannelContext $context): LanguageEntity
    {
        if (null === $context->getCustomer()) {
            throw new RuntimeException('missing customer');
        }

        $criteria = new Criteria([$context->getCustomer()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var null|LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        if (null === $language) {
            throw new RuntimeException('missing customer language');
        }

        return $language;
    }
}
