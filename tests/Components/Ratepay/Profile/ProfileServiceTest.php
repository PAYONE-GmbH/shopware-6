<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay\Profile;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\Helper\OrderFetcher;
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

/**
 * @covers \PayonePayment\Components\Ratepay\Profile\ProfileService
 */
class ProfileServiceTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItReturnsProfileByOrder(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();

        $profile = $profileService->getProfileByOrder($order, $paymentHandler);

        static::assertNotNull($profile);
        static::assertSame('88880103', $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItReturnsProfileByOrderWithDifferentAddresses(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['delivery-address-elv' => 'yes']
        );

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();
        $this->addFakeShippingAddress($order);

        $profile = $profileService->getProfileByOrder($order, $paymentHandler);

        static::assertNotNull($profile);
        static::assertSame('88880103', $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItNotReturnsProfileByOrderWithDifferentAddresses(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['delivery-address-elv' => 'no']
        );

        $profileService = $this->getContainer()->get(ProfileService::class);
        $order          = $this->getRandomOrder();
        $this->addFakeShippingAddress($order);

        $profile = $profileService->getProfileByOrder($order, $paymentHandler);

        static::assertNull($profile);
    }

    public function testItReturnsProfileBySalesChannel(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);

        $profileService      = $this->getContainer()->get(ProfileService::class);
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $profile = $profileService->getProfileBySalesChannelContext($salesChannelContext, $paymentHandler);

        static::assertNotNull($profile);
        static::assertSame('88880103', $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItReturnsProfileWithValidProfileSearch(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);

        $profileSearch  = $this->getValidProfileSearch($paymentHandler);
        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNotNull($profile);
        static::assertSame('88880103', $profile->getShopId());
        static::assertIsArray($profile->getConfiguration());
        static::assertNotEmpty($profile->getConfiguration());
    }

    public function testItNotReturnsProfileWithWrongBillingCountry(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['country-code-billing' => 'DE']
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);
        $profileSearch->setBillingCountryCode('NL');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithWrongShippingCountry(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['country-code-delivery' => 'DE']
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);
        $profileSearch->setShippingCountryCode('NL');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithWrongCurrency(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['currency' => 'EUR']
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);
        $profileSearch->setCurrency('USD');

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithTooLowInvoiceAmount(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['tx-limit-elv-min' => '50']
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);
        $profileSearch->setTotalAmount(10);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileWithTooHighInvoiceAmount(): void
    {
        $paymentHandler = PayoneRatepayDebitPaymentHandler::class;
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            $paymentHandler,
            ['tx-limit-elv-max' => '50']
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);
        $profileSearch->setTotalAmount(100);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItNotReturnsProfileOnMissingConfiguration(): void
    {
        $paymentHandler      = PayoneRatepayDebitPaymentHandler::class;
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);
        $systemConfigService->delete(
            ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'ProfileConfigurations')
        );

        $profileSearch = $this->getValidProfileSearch($paymentHandler);

        $profileService = $this->getContainer()->get(ProfileService::class);
        $profile        = $profileService->getProfile($profileSearch);

        static::assertNull($profile);
    }

    public function testItUpdatesProfileConfigurations(): void
    {
        $paymentHandler           = PayoneRatepayDebitPaymentHandler::class;
        $profilesKey              = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'Profiles');
        $profileConfigurationsKey = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'ProfileConfigurations');
        $systemConfigService      = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);
        $systemConfigService->delete($profileConfigurationsKey);

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

        $result = $profileService->updateProfileConfiguration($paymentHandler);

        static::assertEmpty($result['errors']);
        static::assertIsArray($result['updates'][$profilesKey]);
        static::assertIsArray($result['updates'][$profileConfigurationsKey]);

        $configuration = $systemConfigService->get($profileConfigurationsKey);

        static::assertIsArray($configuration);
        static::assertArrayHasKey('88880103', $configuration);
    }

    public function testItReturnsApiErrorsWhenUpdatingProfileConfigurations(): void
    {
        $paymentHandler           = PayoneRatepayDebitPaymentHandler::class;
        $profilesKey              = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'Profiles');
        $profileConfigurationsKey = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'ProfileConfigurations');
        $systemConfigService      = $this->getContainer()->get(SystemConfigService::class);
        $this->setValidRatepayProfiles($this->getContainer(), $paymentHandler);
        $systemConfigService->delete($profileConfigurationsKey);

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

        $result = $profileService->updateProfileConfiguration($paymentHandler);

        static::assertEmpty($result['updates'][$profilesKey]);
        static::assertEmpty($result['updates'][$profileConfigurationsKey]);
        static::assertIsArray($result['errors'][$profilesKey]);
        static::assertSame('Failed', $result['errors'][$profilesKey][0]['error']);

        $configuration = $systemConfigService->get($profileConfigurationsKey);

        static::assertIsArray($configuration);
        static::assertEmpty($configuration);
    }

    public function testItReturnsPreValidationErrorsWhenUpdatingProfileConfigurations(): void
    {
        $paymentHandler           = PayoneRatepayDebitPaymentHandler::class;
        $profilesKey              = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'Profiles');
        $profileConfigurationsKey = ConfigReader::getConfigKeyByPaymentHandler($paymentHandler, 'ProfileConfigurations');
        $systemConfigService      = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->set($profilesKey, [
            [
                'shopId'   => null,
                'currency' => 'EUR',
            ],
            [
                'shopId'   => '88880103',
                'currency' => null,
            ],
            [
                'shopId'   => '',
                'currency' => 'EUR',
            ],
            [
                'shopId'   => '88880103',
                'currency' => '',
            ],
        ]);

        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects($this->never())->method('request');

        $profileService = new ProfileService(
            $client,
            $this->getContainer()->get(RequestParameterFactory::class),
            $this->getContainer()->get(SystemConfigService::class),
            $this->getContainer()->get(OrderFetcher::class),
            $this->getContainer()->get(CartService::class)
        );

        $result = $profileService->updateProfileConfiguration($paymentHandler);

        static::assertEmpty($result['updates'][$profilesKey]);
        static::assertEmpty($result['updates'][$profileConfigurationsKey]);
        static::assertIsArray($result['errors'][$profilesKey]);
        static::assertCount(4, $result['errors'][$profilesKey]);
        static::assertSame('Shop ID or Currency missing', $result['errors'][$profilesKey][0]['error']);
        static::assertSame('Shop ID or Currency missing', $result['errors'][$profilesKey][1]['error']);
        static::assertSame('Shop ID or Currency missing', $result['errors'][$profilesKey][2]['error']);
        static::assertSame('Shop ID or Currency missing', $result['errors'][$profilesKey][3]['error']);

        $configuration = $systemConfigService->get($profileConfigurationsKey);

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
