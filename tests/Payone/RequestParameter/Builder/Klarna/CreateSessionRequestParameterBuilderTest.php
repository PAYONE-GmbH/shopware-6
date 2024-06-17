<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
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

        $this->createCartWithProduct($salesChannelContext);

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

    protected function assertLineItemHasBeenSet(array $parameters, int $index = 1): void
    {
        // just verify if the keys exists. Tests for the contents, will be performed by testing the line-item-hydrator
        $indexStr = "[$index]";
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_NAME . $indexStr, $parameters);
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_NUMBER . $indexStr, $parameters);
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_PRICE . $indexStr, $parameters);
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_QTY . $indexStr, $parameters);
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_TAX_RATE . $indexStr, $parameters);
        static::assertArrayHasKey(LineItemHydrator::PAYONE_ARRAY_KEY_TYPE . $indexStr, $parameters);
    }
}
