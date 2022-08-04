<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\Components\Helper\OrderFetcher;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProfileServiceTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItReturnsProfileByOrder(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();

        $profile = $profileService->getProfileByOrder($order, PayoneRatepayDebitPaymentHandler::class);

        static::assertNotNull($profile);
        static::assertSame(88880103, $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItReturnsProfileByOrderWithDifferentAddresses(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['delivery-address-elv' => 'yes']
        );

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();
        $this->addFakeShippingAddress($order);

        $profile = $profileService->getProfileByOrder($order, PayoneRatepayDebitPaymentHandler::class);

        static::assertNotNull($profile);
        static::assertSame(88880103, $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItNotReturnsProfileByOrderWithDifferentAddresses(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['delivery-address-elv' => 'no']
        );

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();
        $this->addFakeShippingAddress($order);

        $profile = $profileService->getProfileByOrder($order, PayoneRatepayDebitPaymentHandler::class);

        static::assertNull($profile);
    }

    public function testItReturnsProfileBySalesChannel(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);

        $profileService      = $this->getContainer()->get(ProfileService::class);
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $profile = $profileService->getProfileBySalesChannelContext(
            $salesChannelContext,
            PayoneRatepayDebitPaymentHandler::class
        );

        static::assertNotNull($profile);
        static::assertSame(88880103, $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItReturnsProfileWithValidProfileSearch(): void
    {
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);

        $profileSearch  = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNotNull($profile);
        static::assertSame(88880103, $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItNotReturnsProfileWithWrongBillingCountry(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['country-code-billing' => 'DE']
        );

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileSearch->setBillingCountryCode('NL');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithWrongShippingCountry(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['country-code-delivery' => 'DE']
        );

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileSearch->setShippingCountryCode('NL');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithWrongCurrency(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['currency' => 'EUR']
        );

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileSearch->setCurrency('USD');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithTooLowInvoiceAmount(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['tx-limit-elv-min' => '50']
        );

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileSearch->setTotalAmount(10);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithTooHighInvoiceAmount(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayDebitPaymentHandler::class,
            ['tx-limit-elv-max' => '50']
        );

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);
        $profileSearch->setTotalAmount(100);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileOnMissingConfiguration(): void
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);
        $configMapping = ProfileService::getConfigMappingByPaymentHandler(PayoneRatepayDebitPaymentHandler::class);
        $systemConfigService->delete($configMapping['profileConfigurationsKey']);

        $profileSearch = $this->getValidProfileSearch(PayoneRatepayDebitPaymentHandler::class);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItThrowsExceptionOnInvalidPaymentHandler(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid payment handler');

        ProfileService::getConfigMappingByPaymentHandler(PayoneDebitPaymentHandler::class);
    }

    public function testItUpdatesProfileConfigurations(): void
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);
        $configMapping = ProfileService::getConfigMappingByPaymentHandler(PayoneRatepayDebitPaymentHandler::class);
        $systemConfigService->delete($configMapping['profileConfigurationsKey']);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willReturn(
            [
                'addpaydata' => $this->getValidRatepayProfileConfigurations()['88880103'],
            ]
        );

        $profileService = new ProfileService(
            $client,
            $this->getContainer()->get(RequestParameterFactory::class),
            $this->getContainer()->get(SystemConfigService::class),
            $this->getContainer()->get(OrderFetcher::class),
            $this->getContainer()->get(CartService::class)
        );

        $result = $profileService->updateProfileConfiguration($configMapping['profilesKey']);

        static::assertEmpty($result['errors']);
        static::assertIsArray($result['updates'][$configMapping['profilesKey']]);
        static::assertIsArray($result['updates'][$configMapping['profileConfigurationsKey']]);

        $configuration = $systemConfigService->get($configMapping['profileConfigurationsKey']);

        static::assertIsArray($configuration);
        static::assertArrayHasKey('88880103', $configuration);
    }

    public function testItReturnsErrorsWhenUpdatingProfileConfigurations(): void
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), PayoneRatepayDebitPaymentHandler::class);
        $configMapping = ProfileService::getConfigMappingByPaymentHandler(PayoneRatepayDebitPaymentHandler::class);
        $systemConfigService->delete($configMapping['profileConfigurationsKey']);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->once())->method('request')->willThrowException(
            new PayoneRequestException('payone returned an empty response', [], [
                'error' => [
                    'ErrorMessage' => 'Failed',
                ],
            ])
        );

        $profileService = new ProfileService(
            $client,
            $this->getContainer()->get(RequestParameterFactory::class),
            $this->getContainer()->get(SystemConfigService::class),
            $this->getContainer()->get(OrderFetcher::class),
            $this->getContainer()->get(CartService::class)
        );

        $result = $profileService->updateProfileConfiguration($configMapping['profilesKey']);

        static::assertEmpty($result['updates'][$configMapping['profilesKey']]);
        static::assertEmpty($result['updates'][$configMapping['profileConfigurationsKey']]);
        static::assertIsArray($result['errors'][$configMapping['profilesKey']]);
        static::assertSame('Failed', $result['errors'][$configMapping['profilesKey']][0]['error']);

        $configuration = $systemConfigService->get($configMapping['profileConfigurationsKey']);

        static::assertIsArray($configuration);
        static::assertEmpty($configuration);
    }

    protected function addFakeShippingAddress(OrderEntity $order): void
    {
        $shippingAddressId = Uuid::randomHex();
        $shippingAddress   = new OrderAddressEntity();
        $shippingAddress->setId($shippingAddressId);
        $shippingAddress->setFirstName('Differs from billing address');
        $shippingAddress->setCountry($order->getAddresses()->get($order->getBillingAddressId())->getCountry());
        $order->getAddresses()->add($shippingAddress);
        $order->getDeliveries()->first()->setShippingOrderAddressId($shippingAddressId);
    }

    protected function getValidProfileSearch(string $paymentHandler): ProfileSearch
    {
        $profileSearch = new ProfileSearch();
        $profileSearch->setBillingCountryCode('DE');
        $profileSearch->setShippingCountryCode('DE');
        $profileSearch->setPaymentHandler($paymentHandler);
        $profileSearch->setSalesChannelId(null);
        $profileSearch->setCurrency('EUR');
        $profileSearch->setNeedsAllowDifferentAddress(false);
        $profileSearch->setTotalAmount(100);

        return $profileSearch;
    }
}
