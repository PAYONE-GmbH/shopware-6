<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;

class CreateSessionRequestParameterBuilderTest extends AbstractKlarna
{
    public function testGetRequestParameter(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $this->createCartWithProduct($salesChannelContext, 123.45, 4);

        $struct = new KlarnaCreateSessionStruct($salesChannelContext, PayoneKlarnaInvoicePaymentHandler::class);

        $service = $this->getContainer()->get(CreateSessionRequestParameterBuilder::class);

        self::assertTrue($service->supports($struct), sprintf('%s::support() have to return true when passing instance of %s', get_class($service), get_class($struct)));
        $parameters = $service->getRequestParameter($struct);

        self::assertArrayHasKey('request', $parameters);
        self::assertArrayHasKey('add_paydata[action]', $parameters);
        self::assertArrayHasKey('clearingtype', $parameters);
        self::assertArrayHasKey('amount', $parameters);

        self::assertArrayHasKey('currency', $parameters);
        self::assertEquals($salesChannelContext->getCurrency()->getIsoCode(), $parameters['currency']);

        $this->assertLineItemHasBeenSet($parameters);
    }

    public function testGetRequestParameterByOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();

        $orderEntity = $this->getRandomOrder($salesChannelContext);

        $struct = new KlarnaCreateSessionStruct($salesChannelContext, PayoneKlarnaInvoicePaymentHandler::class, $orderEntity);

        $service = $this->getContainer()->get(CreateSessionRequestParameterBuilder::class);

        self::assertTrue($service->supports($struct), sprintf('%s::support() have to return true when passing instance of %s', get_class($service), get_class($struct)));
        $parameters = $service->getRequestParameter($struct);

        self::assertArrayHasKey('request', $parameters);
        self::assertArrayHasKey('add_paydata[action]', $parameters);
        self::assertArrayHasKey('clearingtype', $parameters);
        self::assertArrayHasKey('amount', $parameters);

        self::assertArrayHasKey('currency', $parameters);
        self::assertEquals($orderEntity->getCurrency()->getIsoCode(), $parameters['currency']);

        $this->assertLineItemHasBeenSet($parameters);
    }

    protected function getStructForTestingSupportMethod(string $paymentHandler): AbstractRequestParameterStruct
    {
        $mock = $this->createMock(KlarnaCreateSessionStruct::class);
        $mock->method('getPaymentMethod')->willReturn($paymentHandler);

        return $mock;
    }
}
