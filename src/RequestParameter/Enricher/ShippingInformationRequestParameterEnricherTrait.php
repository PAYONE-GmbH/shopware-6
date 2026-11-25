<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\RequestParameter\AbstractRequestDto;

/**
 * @template T of AbstractRequestDto
 */
trait ShippingInformationRequestParameterEnricherTrait
{
    protected const COUNTRIES_FOR_WHICH_A_STATE_MUST_BE_SPECIFIED = [
        'AR', 'BR', 'CA', 'CN', 'ID', 'IN', 'JP', 'MX', 'TH', 'US',
    ];

    /**
     * Countries for which the PAYONE test API accepted requests without `shipping_state`.
     * For the other countries listed above this has not been tested, so a missing state still aborts the request.
     *
     * @see https://github.com/PAYONE-GmbH/shopware-6/issues/414
     */
    protected const COUNTRIES_FOR_WHICH_A_STATE_IS_OPTIONAL = ['ID', 'IN', 'TH'];

    /**
     * ISO 3166-2 subdivision codes the PAYONE API does not know. `shipping_state` must not be sent for them.
     */
    protected const UNSUPPORTED_SUBDIVISION_CODES = [
        'ID' => ['KU'],
        'IN' => ['DH', 'LA'],
        'TH' => ['38'],
    ];

    /**
     * ISO 3166-2 subdivision codes for which the PAYONE API still expects the previous code (IN: Telangana,
     * Uttarakhand, Odisha).
     */
    protected const SUBDIVISION_CODE_MAPPING = [
        'IN' => ['TS' => 'TG', 'UK' => 'UT', 'OD' => 'OR'],
    ];

    /**
     * @param T $arguments
     */
    public function enrich(AbstractRequestDto $arguments): array
    {
        $salesChannelContext = $arguments->salesChannelContext;
        $shippingAddress     = $salesChannelContext->getCustomer()?->getActiveShippingAddress();

        $parameters = [];

        if (null === $shippingAddress) {
            return $parameters;
        }

        $country = $shippingAddress->getCountry()?->getIso();

        $parameters = \array_filter([
            'shipping_firstname' => $shippingAddress->getFirstName(),
            'shipping_lastname'  => $shippingAddress->getLastName(),
            'shipping_company'   => $shippingAddress->getCompany(),
            'shipping_street'    => $shippingAddress->getStreet(),
            'shipping_zip'       => $shippingAddress->getZipcode(),
            'shipping_city'      => $shippingAddress->getCity(),
            'shipping_country'   => $country,
        ]);

        if (null === $country || !$this->isStateMandatory($country)) {
            return $parameters;
        }

        $countryStateCode = $shippingAddress->getCountryState()?->getShortCode();

        if (null === $countryStateCode) {
            if (\in_array($country, self::COUNTRIES_FOR_WHICH_A_STATE_IS_OPTIONAL, true)) {
                return $parameters;
            }

            throw new \RuntimeException('missing state in shipping address');
        }

        $subdivisionCode = $this->getSubdivisionCode($countryStateCode, $country);

        if (null === $subdivisionCode) {
            throw new \RuntimeException('invalid country state code');
        }

        if (\in_array($subdivisionCode, self::UNSUPPORTED_SUBDIVISION_CODES[$country] ?? [], true)) {
            return $parameters;
        }

        $parameters['shipping_state'] = self::SUBDIVISION_CODE_MAPPING[$country][$subdivisionCode] ?? $subdivisionCode;

        return $parameters;
    }

    protected function isStateMandatory(string $country): bool
    {
        return \in_array($country, self::COUNTRIES_FOR_WHICH_A_STATE_MUST_BE_SPECIFIED, true);
    }

    /**
     * Returns the ISO 3166-2 subdivision part of a Shopware country state short code, e.g. "CA" for
     * "US-CA" or "B" for "AR-B". Returns null if the short code does not belong to the given country.
     *
     * \Locale::getRegion() must not be used for this: it parses the short code as a locale identifier,
     * so it returns an empty string instead of null for single-character subdivisions (AR-B), rewrites
     * subdivisions that equal an ISO 3166-1 alpha-3 code to that country's alpha-2 code (MX-MEX becomes
     * "MX", MX-COL becomes "CO") and accepts short codes of other countries (MX-CMX for a US address).
     */
    protected function getSubdivisionCode(string $countryStateCode, string $country): string|null
    {
        $countryStateCode = \strtoupper(\trim($countryStateCode));
        $prefix           = $country . '-';

        if (!\str_starts_with($countryStateCode, $prefix)) {
            return null;
        }

        $subdivisionCode = \substr($countryStateCode, \strlen($prefix));

        return '' === $subdivisionCode ? null : $subdivisionCode;
    }
}
