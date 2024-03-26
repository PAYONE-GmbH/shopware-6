<?php

declare(strict_types=1);

namespace PayonePayment\Functional\Payment\AmazonPayExpress;

use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\AmazonPayExpressUpdateCheckoutSessionStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;

class UpdateCheckoutSessionParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfSuccessful(): void
    {
        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        // cart got stored automatically in the CartService, which is reused in the plugin
        $this->createCartWithProduct($context, 200, 2);

        $struct = new AmazonPayExpressUpdateCheckoutSessionStruct($context, 'test-123');

        $requestParams = $factory->getRequestParameter($struct);

        static::assertArrayHasKey('request', $requestParams);
        static::assertEquals('genericpayment', $requestParams['request']);
        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('AMP', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertIsNumeric($requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertIsString($requestParams['currency']);
        static::assertArrayHasKey('workorderid', $requestParams);
        static::assertEquals('test-123', $requestParams['workorderid']);
    }
}
