<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;

class FinancingTypeParameterBuilderTest extends AbstractKlarna
{
    public function testGetRequestParameter(): void
    {
        $service = $this->getContainer()->get(FinancingTypeParameterBuilder::class);

        $paymentHandlers = [
            PayoneKlarnaInvoicePaymentHandler::class,
            PayoneKlarnaDirectDebitPaymentHandler::class,
            PayoneKlarnaInstallmentPaymentHandler::class,
        ];

        foreach ($paymentHandlers as $paymentHandler) {
            $struct = new TestCredentialsStruct([], 'test-action', $paymentHandler);
            self::assertTrue($service->supports($struct), sprintf('%s needs to support payment handler %s', FinancingTypeParameterBuilder::class, $paymentHandler));
            $parameters = $service->getRequestParameter($struct);
            self::assertArrayHasKey('financingtype', $parameters);
        }
    }

    public function testGetRequestParameterWrongPaymentHandler(): void
    {
        $service = $this->getContainer()->get(FinancingTypeParameterBuilder::class);

        $struct = new TestCredentialsStruct([], 'test-action', '\Wrong\ClassName');
        self::assertFalse($service->supports($struct), sprintf('%s should not support invalid/not klarna payment handler', FinancingTypeParameterBuilder::class));

        self::expectExceptionMessage('invalid payment method');
        $service->getRequestParameter($struct);
    }

    protected function getStructForTestingSupportMethod(string $paymentHandler): AbstractRequestParameterStruct
    {
        $mock = $this->createMock(TestCredentialsStruct::class);
        $mock->method('getPaymentMethod')->willReturn($paymentHandler);

        return $mock;
    }
}
