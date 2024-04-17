<?php

declare(strict_types=1);

namespace PayonePayment\Functional\Payment\PaypalExpress;

use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PayonePayment\Components\GenericExpressCheckout\RequestParameterBuilder\CreateCheckoutSessionParameterBuilder
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PaypalExpress\CreateCheckoutSessionParameterBuilder
 */
class CreateCheckoutSessionRequestParametersTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfParametersGotCreatedSuccessful(): void
    {
        /** @var RequestParameterFactory $factory */
        $factory = $this->getContainer()->get(RequestParameterFactory::class);

        $context = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        // cart got stored automatically in the CartService, which is reused in the plugin
        $this->createCartWithProduct($context, 200, 2);

        $requestParams = $factory->getRequestParameter(new CreateExpressCheckoutSessionStruct(
            $context,
            PayonePaypalExpressPaymentHandler::class
        ));

        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('PPE', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertEquals(400 * 100, $requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertArrayHasKey('add_paydata[action]', $requestParams);
        static::assertEquals('setexpresscheckout', $requestParams['add_paydata[action]']);
        static::assertArrayHasKey('successurl', $requestParams);
        static::assertArrayHasKey('backurl', $requestParams);
        static::assertArrayHasKey('errorurl', $requestParams);
    }
}
