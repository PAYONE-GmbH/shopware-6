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
            throw new \RuntimeException('missing state in shipping address');
        }

        $locale = \Locale::getRegion($countryStateCode);

        if (null === $locale) {
            throw new \RuntimeException('invalid country state code');
        }

        $parameters['shipping_state'] = $locale;

        return $parameters;
    }

    protected function isStateMandatory(string $country): bool
    {
        return \in_array($country, self::COUNTRIES_FOR_WHICH_A_STATE_MUST_BE_SPECIFIED, true);
    }
}
