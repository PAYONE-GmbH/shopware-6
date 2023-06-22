<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Klarna\CreateSessionRequestParameterBuilder
 */
class CreateSessionRequestParameterBuilderTest extends AbstractKlarna
{
    public function testItAddsCorrectCreateSessionParameters(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->createCartWithProduct($salesChannelContext, 123.45, 4);

        $struct = new KlarnaCreateSessionStruct($salesChannelContext, PayoneKlarnaInvoicePaymentHandler::class);

        $service = $this->getContainer()->get(CreateSessionRequestParameterBuilder::class);

        static::assertTrue($service->supports($struct), sprintf('%s::support() have to return true when passing instance of %s', $service::class, $struct::class));
        $parameters = $service->getRequestParameter($struct);

        static::assertArrayHasKey('request', $parameters);
        static::assertArrayHasKey('add_paydata[action]', $parameters);
        static::assertArrayHasKey('clearingtype', $parameters);
        static::assertArrayHasKey('amount', $parameters);

        static::assertArrayHasKey('currency', $parameters);
        static::assertEquals($salesChannelContext->getCurrency()->getIsoCode(), $parameters['currency']);

        $this->assertLineItemHasBeenSet($parameters);
    }

    public function testItAddsCorrectCreateSessionParametersByOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $orderEntity = $this->getRandomOrder($salesChannelContext);

        $struct = new KlarnaCreateSessionStruct($salesChannelContext, PayoneKlarnaInvoicePaymentHandler::class, $orderEntity);

        $service = $this->getContainer()->get(CreateSessionRequestParameterBuilder::class);

        static::assertTrue($service->supports($struct), sprintf('%s::support() have to return true when passing instance of %s', $service::class, $struct::class));
        $parameters = $service->getRequestParameter($struct);

        static::assertArrayHasKey('request', $parameters);
        static::assertArrayHasKey('add_paydata[action]', $parameters);
        static::assertArrayHasKey('clearingtype', $parameters);
        static::assertArrayHasKey('amount', $parameters);

        static::assertArrayHasKey('currency', $parameters);
        static::assertEquals($orderEntity->getCurrency()->getIsoCode(), $parameters['currency']);

        $this->assertLineItemHasBeenSet($parameters);
    }

    protected function getStructForTestingSupportMethod(string $paymentHandler): AbstractRequestParameterStruct
    {
        $mock = $this->createMock(KlarnaCreateSessionStruct::class);
        $mock->method('getPaymentMethod')->willReturn($paymentHandler);

        return $mock;
    }
}
