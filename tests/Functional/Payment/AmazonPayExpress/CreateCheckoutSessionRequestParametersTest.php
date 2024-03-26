<?php

declare(strict_types=1);

namespace PayonePayment\Functional\Payment\AmazonPayExpress;

use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayoneAmazonPayExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @covers \PayonePayment\Components\GenericExpressCheckout\RequestParameterBuilder\CreateCheckoutSessionParameterBuilder
 * @covers \PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress\CreateCheckoutSessionRequestParameterBuilder
 */
class CreateCheckoutSessionRequestParametersTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfParametersGotCreatedSuccessfulWithoutRestriction(): void
    {
        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        // cart got stored automatically in the CartService, which is reused in the plugin
        $this->createCartWithProduct($context, 200, 2);

        $requestParams = $factory->getRequestParameter(new CreateExpressCheckoutSessionStruct(
            $context,
            PayoneAmazonPayExpressPaymentHandler::class
        ));

        static::assertArrayHasKey('request', $requestParams);
        static::assertEquals('genericpayment', $requestParams['request']);
        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('AMP', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertEquals(400 * 100, $requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertArrayHasKey('add_paydata[action]', $requestParams);
        static::assertEquals('createCheckoutSessionPayload', $requestParams['add_paydata[action]']);
        static::assertArrayHasKey('successurl', $requestParams);
        static::assertArrayHasKey('backurl', $requestParams);
        static::assertArrayHasKey('errorurl', $requestParams);
        static::assertArrayNotHasKey('add_paydata[specialRestrictions]', $requestParams);
        static::assertArrayHasKey('add_paydata[platform_id]', $requestParams);
    }

    /**
     * @dataProvider restrictionData
     */
    public function testIfParametersGotCreatedSuccessfulWithRestriction(array $restrictionsKeys): void
    {
        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        /** @var SystemConfigService $configReader */
        $configReader = $this->getContainer()->get(SystemConfigService::class);

        foreach ($restrictionsKeys as $key) {
            $configReader->set(
                ConfigReader::getConfigKeyByPaymentHandler(PayoneAmazonPayExpressPaymentHandler::class, $key),
                true
            );
        }

        // cart got stored automatically in the CartService, which is reused in the plugin
        $this->createCartWithProduct($context, 200, 2);

        $requestParams = $factory->getRequestParameter(new CreateExpressCheckoutSessionStruct(
            $context,
            PayoneAmazonPayExpressPaymentHandler::class
        ));

        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('AMP', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertEquals(400 * 100, $requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertArrayHasKey('add_paydata[action]', $requestParams);
        static::assertEquals('createCheckoutSessionPayload', $requestParams['add_paydata[action]']);
        static::assertArrayHasKey('successurl', $requestParams);
        static::assertArrayHasKey('backurl', $requestParams);
        static::assertArrayHasKey('errorurl', $requestParams);

        static::assertArrayHasKey('add_paydata[specialRestrictions]', $requestParams);
        foreach ($restrictionsKeys as $key) {
            static::assertStringContainsString($key, $requestParams['add_paydata[specialRestrictions]']);
        }
    }

    protected static function restrictionData(): array
    {
        return [
            [['RestrictPOBoxes']],
            [['RestrictPackstations']],
            [['RestrictPOBoxes', 'RestrictPackstations']],
        ];
    }
}
