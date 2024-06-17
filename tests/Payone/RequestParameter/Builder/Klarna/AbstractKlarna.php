<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

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

    private function getClassToTest(): string
    {
        $class = preg_replace('/Test$/', '', static::class);

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Class %s does not exist', $class));
        }

        return $class;
    }
}
