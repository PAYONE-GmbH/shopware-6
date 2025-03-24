<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\Core\Utils\AddressCompare;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerRegistrationUtil
{
    public function __construct(
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $countryStateRepository,
        private readonly TranslatorInterface $translator,
        private readonly DataValidationFactoryInterface $addressValidationFactory,
        private readonly DataValidator $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getCustomerDataBagFromGetCheckoutSessionResponse(array $response, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        $billingCountry = $this->extractBillingData($response, 'country') ?? '';

        $billingAddress = [
            'salutationId' => $salutationId,
            'company' => $this->extractBillingData($response, 'company'),
            'firstName' => $this->extractBillingData($response, 'firstname'),
            'lastName' => $this->extractBillingData($response, 'lastname'),
            'street' => $this->extractBillingData($response, 'street'),
            'additionalAddressLine1' => $this->extractBillingData($response, 'addressaddition'),
            'zipcode' => $this->extractBillingData($response, 'zip'),
            'city' => $this->extractBillingData($response, 'city'),
            'countryId' => $this->getCountryIdByCode($billingCountry, $salesChannelContext->getContext()),
            'countryStateId' => $this->getCountryStateIdByCodes(
                $billingCountry,
                $this->extractBillingData($response, 'state') ?? '',
                $salesChannelContext->getContext()
            ),
            'phone' => $this->extractBillingData($response, 'telephonenumber'),
        ];

        $shippingCountry = $this->extractShippingData($response, 'country') ?? '';

        $shippingAddress = [
            'salutationId' => $salutationId,
            'company' => $this->extractShippingData($response, 'company'),
            'firstName' => $this->extractShippingData($response, 'firstname'),
            'lastName' => $this->extractShippingData($response, 'lastname'),
            'street' => $this->extractShippingData($response, 'street'),
            'additionalAddressLine1' => $this->extractShippingData($response, 'addressaddition'),
            'zipcode' => $this->extractShippingData($response, 'zip'),
            'city' => $this->extractShippingData($response, 'city'),
            'countryId' => $this->getCountryIdByCode($shippingCountry, $salesChannelContext->getContext()),
            'countryStateId' => $this->getCountryStateIdByCodes(
                $shippingCountry,
                $this->extractShippingData($response, 'state') ?? '',
                $salesChannelContext->getContext()
            ),
            'phone' => $this->extractShippingData($response, 'telephonenumber'),
        ];

        $billingAddressViolations = $this->validateAddress($billingAddress, $salesChannelContext);
        $shippingAddressViolations = $this->validateAddress($shippingAddress, $salesChannelContext);

        $isBillingAddressComplete = $billingAddressViolations->count() === 0;
        $isShippingAddressComplete = $shippingAddressViolations->count() === 0;

        if (!$isBillingAddressComplete && !$isShippingAddressComplete) {
            $this->logger->error('PAYONE Express Checkout: The delivery and billing address is incomplete', [
                'billingAddress' => $billingAddress,
                'shippingAddress' => $shippingAddress,
                'billingAddressViolations' => $billingAddressViolations->__toString(),
                'shippingAddressViolations' => $shippingAddressViolations->__toString(),
            ]);

            throw new RuntimeException($this->translator->trans('PayonePayment.errorMessages.genericError'));
        }

        if (!$isBillingAddressComplete && $isShippingAddressComplete) {
            $billingAddress = $shippingAddress;
        }

        $customerData = new RequestDataBag([
            'guest' => true,
            'salutationId' => $salutationId,
            'email' => $response['addpaydata']['email'],
            'firstName' => $billingAddress['firstName'],
            'lastName' => $billingAddress['lastName'],
            'acceptedDataProtection' => true,
            'billingAddress' => $billingAddress,
            'shippingAddress' => $shippingAddress,
        ]);

        if ($customerData->get('billingAddress')?->get('company') !== null) {
            $customerData->set('accountType', CustomerEntity::ACCOUNT_TYPE_BUSINESS);
        } else {
            $customerData->set('accountType', CustomerEntity::ACCOUNT_TYPE_PRIVATE);
        }

        $billingAddress = $customerData->get('billingAddress')?->all() ?: [];
        $shippingAddress = $customerData->get('shippingAddress')?->all() ?: [];
        if (!$isShippingAddressComplete || AddressCompare::areRawAddressesIdentical($billingAddress, $shippingAddress)) {
            $customerData->remove('shippingAddress');
        }

        return $customerData;
    }

    private function extractBillingData(array $response, string $key): ?string
    {
        // special case: PayPal v1 express: PayPal does not return firstname. so we need to take the firstname from the shipping-data
        if (($key === 'firstname' || $key === 'lastname')
            && !\array_key_exists('firstname', $response['addpaydata'])
            && isset(
                $response['addpaydata']['lastname'],
                $response['addpaydata']['shipping_firstname'],
                $response['addpaydata']['shipping_lastname']
            )) {
            $paypalExpectedLastname = "{$response['addpaydata']['shipping_firstname']} {$response['addpaydata']['shipping_lastname']}";
            if ($paypalExpectedLastname === $response['addpaydata']['lastname']) {
                return $response['addpaydata']['shipping_' . $key];
            }
        }

        // Do not take any values from the shipping address as a fallback for individual fields.
        // If mandatory fields are missing from the billing address, the complete shipping address is used
        return $response['addpaydata']['billing_' . $key] ?? $response['addpaydata'][$key] ?? null;
    }

    private function extractShippingData(array $response, string $key): ?string
    {
        // Do not take any values from the billing address as a fallback for individual fields.
        // If mandatory fields are missing from the shipping address, the complete shipping address is removed
        return $response['addpaydata']['shipping_' . $key] ?? null;
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salutationKey', 'not_specified')
        );

        /** @var SalutationEntity|null $salutation */
        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if ($salutation === null) {
            throw new RuntimeException($this->translator->trans('PayonePayment.errorMessages.genericError'));
        }

        return $salutation->getId();
    }

    private function getCountryIdByCode(string $code, Context $context): ?string
    {
        if (empty($code)) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('iso', $code)
        );

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        return $country?->getId();
    }

    private function getCountryStateIdByCodes(string $countryCode, string $stateCode, Context $context): ?string
    {
        if (empty($countryCode) || empty($stateCode)) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('shortCode', sprintf('%s-%s', $countryCode, $stateCode))
        );

        /** @var CountryStateEntity|null $countryState */
        $countryState = $this->countryStateRepository->search($criteria, $context)->first();

        return $countryState?->getId();
    }

    private function validateAddress(array $address, SalesChannelContext $salesChannelContext): ConstraintViolationList
    {
        $validation = $this->addressValidationFactory->create($salesChannelContext);

        return $this->validator->getViolations($address, $validation);
    }
}
