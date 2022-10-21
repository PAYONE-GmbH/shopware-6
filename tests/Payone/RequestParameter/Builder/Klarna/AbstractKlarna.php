<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\PaymentHandler\PayoneKlarnaDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneKlarnaInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;

abstract class AbstractKlarna extends TestCase
{
    use PayoneTestBehavior;

    final public function testItSupportsOnlyKlarnaMethods(): void
    {
        $classToTest = $this->getClassToTest();
        /** @var AbstractRequestParameterBuilder $builder */
        $builder = $this->getContainer()->get($classToTest);

        $paymentHandlerList = [
            PayoneKlarnaInvoicePaymentHandler::class,
            PayoneKlarnaDirectDebitPaymentHandler::class,
            PayoneKlarnaInstallmentPaymentHandler::class,
        ];

        foreach ($paymentHandlerList as $paymentHandler) {
            $result = $builder->supports($this->getStructForTestingSupportMethod($paymentHandler));
            static::assertTrue($result, 'builder should support');
        }
    }

    abstract protected function getStructForTestingSupportMethod(string $paymentHandler): AbstractRequestParameterStruct;

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

    private function getClassToTest(): string
    {
        $class = preg_replace('/Test$/', '', static::class);

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Class %s does not exist', $class));
        }

        return $class;
    }
}
