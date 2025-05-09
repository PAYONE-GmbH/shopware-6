<?php

declare(strict_types=1);

namespace PayonePayment\Functional\Payment\PaypalV2Express;

use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\Constants;
use PayonePayment\PaymentHandler\PayonePaypalV2ExpressPaymentHandler;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PayonePayment\Components\GenericExpressCheckout\RequestParameterBuilder\CreateCheckoutSessionParameterBuilder
 * @covers \PayonePayment\Payone\RequestParameter\Builder\PaypalV2Express\CreateCheckoutSessionParameterBuilder
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
        $this->createCartWithProduct($context);

        $requestParams = $factory->getRequestParameter(new CreateExpressCheckoutSessionStruct(
            $context,
            PayonePaypalV2ExpressPaymentHandler::class
        ));

        static::assertArrayHasKey('clearingtype', $requestParams);
        static::assertEquals('wlt', $requestParams['clearingtype']);
        static::assertArrayHasKey('wallettype', $requestParams);
        static::assertEquals('PAL', $requestParams['wallettype']);
        static::assertArrayHasKey('amount', $requestParams);
        static::assertEquals(Constants::DEFAULT_PRODUCT_PRICE * 100, $requestParams['amount']);
        static::assertArrayHasKey('currency', $requestParams);
        static::assertArrayHasKey('add_paydata[action]', $requestParams);
        static::assertEquals('setexpresscheckout', $requestParams['add_paydata[action]']);
        static::assertArrayHasKey('successurl', $requestParams);
        static::assertArrayHasKey('backurl', $requestParams);
        static::assertArrayHasKey('errorurl', $requestParams);
    }
}
