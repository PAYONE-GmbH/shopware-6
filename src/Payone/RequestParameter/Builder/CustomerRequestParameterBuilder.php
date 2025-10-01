<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\PaymentHandler\AbstractPostfinancePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use PayonePayment\Payone\RequestParameter\Struct\ManageMandateStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use PayonePayment\Provider;
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
 * @deprecated
 */
class CustomerRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly EntityRepository $languageRepository,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param ManageMandateStruct|PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $this->validateMethod($arguments, 'getSalesChannelContext');
        $salesChannelContext = $arguments->getSalesChannelContext();

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

        $personalData = [
            'company'         => $billingAddress->getCompany(),
            'salutation'      => $this->getCustomerSalutation($billingAddress, $salesChannelContext->getContext())->getDisplayName(),
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $this->getCustomerCountry($billingAddress, $salesChannelContext->getContext())->getIso(),
            'email'           => $salesChannelContext->getCustomer()->getEmail(),
            'language'        => substr((string) $language->getLocale()->getCode(), 0, 2),
            'ip'              => null !== $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        $birthday = $salesChannelContext->getCustomer()->getBirthday();

        if (null !== $birthday) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return array_filter($personalData);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof ManageMandateStruct;
    }

    private function getCustomerSalutation(CustomerAddressEntity $addressEntity, Context $context): SalutationEntity
    {
        $salutationId = $addressEntity->getSalutationId();
        if (null === $salutationId) {
            throw new \RuntimeException('missing order customer salutation');
        }

        $criteria = new Criteria([$salutationId]);

        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if (!$salutation instanceof SalutationEntity) {
            throw new \RuntimeException('missing order customer salutation');
        }

        return $salutation;
    }

    private function getCustomerCountry(CustomerAddressEntity $addressEntity, Context $context): CountryEntity
    {
        $criteria = new Criteria([$addressEntity->getCountryId()]);

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if (null === $country) {
            throw new \RuntimeException('missing order country entity');
        }

        return $country;
    }

    private function getCustomerLanguage(SalesChannelContext $context): LanguageEntity
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
