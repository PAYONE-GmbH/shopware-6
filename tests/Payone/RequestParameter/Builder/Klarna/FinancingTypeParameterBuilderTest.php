<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\Klarna\FinancingTypeParameterBuilder
 */
class FinancingTypeParameterBuilderTest extends AbstractKlarna
{
    public function testItAddsCorrectFinancingTypeParameter(): void
    {
        $service = $this->getContainer()->get(FinancingTypeParameterBuilder::class);

        $paymentHandlers = [
            PayoneKlarnaInvoicePaymentHandler::class,
            PayoneKlarnaDirectDebitPaymentHandler::class,
            PayoneKlarnaInstallmentPaymentHandler::class,
        ];

        foreach ($paymentHandlers as $paymentHandler) {
            $struct = new TestCredentialsStruct([], 'test-action', $paymentHandler);
            static::assertTrue($service->supports($struct), sprintf('%s needs to support payment handler %s', FinancingTypeParameterBuilder::class, $paymentHandler));
            $parameters = $service->getRequestParameter($struct);
            static::assertArrayHasKey('financingtype', $parameters);
        }
    }

    public function testItThrowsExceptionOnWrongPaymentHandler(): void
    {
        $service = $this->getContainer()->get(FinancingTypeParameterBuilder::class);

        $struct = new TestCredentialsStruct([], 'test-action', '\Wrong\ClassName');
        static::assertFalse($service->supports($struct), sprintf('%s should not support invalid/not klarna payment handler', FinancingTypeParameterBuilder::class));

        $this->expectExceptionMessage('invalid payment method');
        $service->getRequestParameter($struct);
    }

    protected function getStructForTestingSupportMethod(string $paymentHandler): AbstractRequestParameterStruct
    {
        $mock = $this->createMock(TestCredentialsStruct::class);
        $mock->method('getPaymentMethod')->willReturn($paymentHandler);

        return $mock;
    }
}
