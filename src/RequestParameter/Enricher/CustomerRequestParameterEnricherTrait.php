<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
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
        $customer            = $salesChannelContext->getCustomer();

        if (null === $customer) {
            throw new \RuntimeException('missing customer');
        }

        $language = $this->getCustomerLanguage($salesChannelContext);
        $locale   = $language->getLocale();

        if (null === $locale) {
            throw new \RuntimeException('missing language locale');
        }

        $billingAddress = $arguments instanceof PaymentRequestDto
            ? $arguments->paymentTransaction->order->getBillingAddress()
            : $customer->getActiveBillingAddress()
        ;

        if (null === $billingAddress) {
            throw new \RuntimeException('missing customer billing address');
        }

        $context    = $salesChannelContext->getContext();
        $salutation = $this->getCustomerSalutation($billingAddress, $context)->getDisplayName();
        $country    = $this->getCustomerCountry($billingAddress, $context)->getIso();

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
            'email'           => $customer->getEmail(),
            'language'        => \substr($locale->getCode(), 0, 2),
            'ip'              => $ip,
        ];

        $birthday = $customer->getBirthday();

        if (null !== $birthday) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return \array_filter($personalData);
    }

    protected function getCustomerSalutation(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        Context $context
    ): SalutationEntity {
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

    protected function getCustomerCountry(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        Context $context
    ): CountryEntity {
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
