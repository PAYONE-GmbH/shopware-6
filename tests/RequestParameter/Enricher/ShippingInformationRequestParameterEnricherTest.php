<?php

declare(strict_types=1);

namespace PayonePayment\Tests\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Provider\PayPal\RequestParameter\Enricher\ShippingInformationRequestParameterEnricher;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

#[CoversClass(ShippingInformationRequestParameterEnricher::class)]
class ShippingInformationRequestParameterEnricherTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function validStateProvider(): iterable
    {
        yield 'two letters (US)' => ['US', 'US-CA', 'CA'];
        yield 'two letters (CA)' => ['CA', 'CA-ON', 'ON'];
        yield 'two digits (JP)' => ['JP', 'JP-13', '13'];
        yield 'two digits (TH)' => ['TH', 'TH-10', '10'];
        yield 'three letters (MX)' => ['MX', 'MX-CMX', 'CMX'];
        yield 'three letters, equal to alpha-3 code of Colombia (MX, Colima)' => ['MX', 'MX-COL', 'COL'];
        yield 'three letters, equal to alpha-3 code of Mexico (MX, Estado de Mexico)' => ['MX', 'MX-MEX', 'MEX'];
        yield 'single letter (AR, Buenos Aires)' => ['AR', 'AR-B', 'B'];
        yield 'single letter (AR, Ciudad Autonoma)' => ['AR', 'AR-C', 'C'];
        yield 'unchanged IN code (Delhi)' => ['IN', 'IN-DL', 'DL'];
        yield 'lowercase short code' => ['MX', 'mx-jal', 'JAL'];
        yield 'short code with surrounding whitespace' => ['US', ' US-CA ', 'CA'];
        yield 'mapped IN code (Telangana)' => ['IN', 'IN-TS', 'TG'];
        yield 'mapped IN code (Uttarakhand)' => ['IN', 'IN-UK', 'UT'];
        yield 'mapped IN code (Odisha)' => ['IN', 'IN-OD', 'OR'];
    }

    #[DataProvider('validStateProvider')]
    public function testShippingStateIsSubdivisionPartOfShortCode(string $country, string $shortCode, string $expected): void
    {
        $parameters = $this->enrich($country, $shortCode);

        static::assertSame($expected, $parameters['shipping_state'] ?? null);
    }

    public function testShippingStateIsOmittedForCountriesWithoutStateRequirement(): void
    {
        $parameters = $this->enrich('DE', 'DE-NW');

        static::assertArrayNotHasKey('shipping_state', $parameters);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsupportedStateProvider(): iterable
    {
        yield 'IN, Dadra and Nagar Haveli and Daman and Diu' => ['IN', 'IN-DH'];
        yield 'IN, Ladakh' => ['IN', 'IN-LA'];
        yield 'ID, Kalimantan Utara' => ['ID', 'ID-KU'];
        yield 'TH, Bueng Kan' => ['TH', 'TH-38'];
    }

    #[DataProvider('unsupportedStateProvider')]
    public function testShippingStateIsOmittedForSubdivisionsUnknownToTheApi(string $country, string $shortCode): void
    {
        $parameters = $this->enrich($country, $shortCode);

        static::assertArrayNotHasKey('shipping_state', $parameters);
        static::assertSame($country, $parameters['shipping_country']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function optionalStateCountryProvider(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'IN' => ['IN'];
        yield 'TH' => ['TH'];
    }

    #[DataProvider('optionalStateCountryProvider')]
    public function testMissingStateIsOmittedForCountriesWithOptionalState(string $country): void
    {
        $parameters = $this->enrich($country, null);

        static::assertArrayNotHasKey('shipping_state', $parameters);
        static::assertSame($country, $parameters['shipping_country']);
    }

    public function testMissingStateThrowsForCountriesWithStateRequirement(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing state in shipping address');

        $this->enrich('US', null);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidStateProvider(): iterable
    {
        yield 'no country prefix' => ['US', 'CA'];
        yield 'prefix of another country' => ['US', 'MX-CMX'];
        yield 'empty subdivision part' => ['US', 'US-'];
    }

    #[DataProvider('invalidStateProvider')]
    public function testInvalidShortCodeThrowsInsteadOfSendingEmptyState(string $country, string $shortCode): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid country state code');

        $this->enrich($country, $shortCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(string $countryIso, string|null $stateShortCode): array
    {
        $country = new CountryEntity();
        $country->setId('country-id');
        $country->setIso($countryIso);

        $address = new CustomerAddressEntity();
        $address->setId('address-id');
        $address->setFirstName('Jane');
        $address->setLastName('Doe');
        $address->setStreet('Main Street 1');
        $address->setZipcode('12345');
        $address->setCity('Springfield');
        $address->setCountry($country);

        if (null !== $stateShortCode) {
            $state = new CountryStateEntity();
            $state->setId('state-id');
            $state->setShortCode($stateShortCode);
            $address->setCountryState($state);
        }

        $customer = new CustomerEntity();
        $customer->setId('customer-id');
        $customer->setActiveShippingAddress($address);

        $salesChannelContext = $this->createStub(SalesChannelContext::class);
        $salesChannelContext->method('getCustomer')->willReturn($customer);

        $arguments = new PaymentRequestDto(
            $this->createStub(PaymentTransactionDto::class),
            new RequestDataBag(),
            $salesChannelContext,
            new Cart('cart-token'),
            $this->createStub(PaymentHandlerInterface::class),
        );

        return (new ShippingInformationRequestParameterEnricher())->enrich($arguments);
    }
}
