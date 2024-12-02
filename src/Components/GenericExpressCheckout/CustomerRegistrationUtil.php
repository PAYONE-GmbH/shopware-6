<?php

declare(strict_types=1);

namespace PayonePayment\Components\GenericExpressCheckout;

use PayonePayment\Core\Utils\AddressCompare;
use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerRegistrationUtil
{
    public function __construct(
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getCustomerDataBagFromGetCheckoutSessionResponse(array $response, Context $context): RequestDataBag
    {
        $salutationId = $this->getSalutationId($context);

        $billingAddress = array_filter([
            'salutationId' => $salutationId,
            'company' => $this->extractBillingData($response, 'company'),
            'firstName' => $this->extractBillingData($response, 'firstname'),
            'lastName' => $this->extractBillingData($response, 'lastname'),
            'street' => $this->extractBillingData($response, 'street'),
            'additionalAddressLine1' => $this->extractBillingData($response, 'addressaddition'),
            'zipcode' => $this->extractBillingData($response, 'zip'),
            'city' => $this->extractBillingData($response, 'city'),
            'countryId' => $this->getCountryIdByCode($this->extractBillingData($response, 'country') ?? '', $context),
            'phone' => $this->extractBillingData($response, 'telephonenumber'),
        ]);

        $shippingAddress = array_filter([
            'salutationId' => $salutationId,
            'company' => $this->extractShippingData($response, 'company'),
            'firstName' => $this->extractShippingData($response, 'firstname'),
            'lastName' => $this->extractShippingData($response, 'lastname'),
            'street' => $this->extractShippingData($response, 'street'),
            'additionalAddressLine1' => $this->extractShippingData($response, 'addressaddition'),
            'zipcode' => $this->extractShippingData($response, 'zip'),
            'city' => $this->extractShippingData($response, 'city'),
            'countryId' => $this->getCountryIdByCode($this->extractShippingData($response, 'country') ?? '', $context),
            'phone' => $this->extractShippingData($response, 'telephonenumber'),
        ]);

        $isBillingAddressComplete = $this->hasAddressRequiredData($billingAddress);
        $isShippingAddressComplete = $this->hasAddressRequiredData($shippingAddress);

        if (!$isBillingAddressComplete && !$isShippingAddressComplete) {
            throw new RuntimeException($this->translator->trans('PayonePayment.errorMessages.genericError'));
        }

        if (!$isBillingAddressComplete && $isShippingAddressComplete) {
            $billingAddress = $shippingAddress;
        }

        $customerData = new RequestDataBag([
            'guest' => true,
            'salutationId' => $salutationId,
            'email' => $response['addpaydata']['email'],
            'firstName' => $billingAddress['firstName'], /** @phpstan-ignore offsetAccess.notFound */
            'lastName' => $billingAddress['lastName'], /** @phpstan-ignore offsetAccess.notFound */
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

        if (!$country instanceof CountryEntity) {
            return null;
        }

        return $country->getId();
    }

    private function hasAddressRequiredData(array $address): bool
    {
        $requiredFields = [
            'firstName',
            'lastName',
            'city',
            'street',
            'countryId',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($address[$field])) {
                return false;
            }
        }

        return true;
    }
}
