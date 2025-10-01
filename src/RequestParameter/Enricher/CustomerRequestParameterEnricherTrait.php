<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\RequestParameter\AbstractRequestDto;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @template T of AbstractRequestDto
 */
trait CustomerRequestParameterEnricherTrait
{
    protected readonly EntityRepository $languageRepository;

    protected readonly EntityRepository $salutationRepository;

    protected readonly EntityRepository $countryRepository;

    protected readonly RequestStack $requestStack;

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        $salesChannelContext = $arguments->salesChannelContext;

        if (null === $salesChannelContext->getCustomer()) {
            throw new \RuntimeException('missing customer');
        }

        $language = $this->getCustomerLanguage($salesChannelContext);

        if (null === $language->getLocale()) {
            throw new \RuntimeException('missing language locale');
        }

        $billingAddress = $salesChannelContext->getCustomer()->getActiveBillingAddress();

        if (null === $billingAddress) {
            throw new \RuntimeException('missing customer billing address');
        }

        $salutation = $this->getCustomerSalutation($billingAddress, $salesChannelContext->getContext())
            ->getDisplayName()
        ;

        $country = $this->getCustomerCountry($billingAddress, $salesChannelContext->getContext())
            ->getIso()
        ;

        $ip = null !== $this->requestStack->getCurrentRequest()
            ? $this->requestStack->getCurrentRequest()->getClientIp()
            : null
        ;

        $personalData = [
            'company'         => $billingAddress->getCompany(),
            'salutation'      => $salutation,
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $country,
            'email'           => $salesChannelContext->getCustomer()->getEmail(),
            'language'        => \substr($language->getLocale()->getCode(), 0, 2),
            'ip'              => $ip,
        ];

        $birthday = $salesChannelContext->getCustomer()->getBirthday();

        if (null !== $birthday) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return \array_filter($personalData);
    }

    protected function getCustomerSalutation(CustomerAddressEntity $addressEntity, Context $context): SalutationEntity
    {
        $salutationId = $addressEntity->getSalutationId();

        if (null === $salutationId) {
            throw new \RuntimeException('missing order customer salutation');
        }

        $criteria   = new Criteria([$salutationId]);
        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (!$salutation instanceof SalutationEntity) {
            throw new \RuntimeException('missing order customer salutation');
        }

        return $salutation;
    }

    protected function getCustomerCountry(CustomerAddressEntity $addressEntity, Context $context): CountryEntity
    {
        $criteria = new Criteria([$addressEntity->getCountryId()]);

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (null === $country) {
            throw new \RuntimeException('missing order country entity');
        }

        return $country;
    }

    protected function getCustomerLanguage(SalesChannelContext $context): LanguageEntity
    {
        if (null === $context->getCustomer()) {
            throw new \RuntimeException('missing customer');
        }

        $criteria = new Criteria([$context->getCustomer()->getLanguageId()]);

        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        if (null === $language) {
            throw new \RuntimeException('missing customer language');
        }

        return $language;
    }
}
