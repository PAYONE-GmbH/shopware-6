<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentStateHandler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Translation\Translator;

class PaymentStateHandlerTest extends TestCase
{
    public function testIfExceptionGotThrownOnCancel(): void
    {
        $handler = new PaymentStateHandler($this->createMock(Translator::class));

        if (class_exists(PaymentException::class)) {
            $this->expectException(PaymentException::class);
        } elseif (class_exists(CustomerCanceledAsyncPaymentException::class)) {
            $this->expectException(CustomerCanceledAsyncPaymentException::class);
        } else {
            throw new \Exception('neither PaymentException nor CustomerCanceledAsyncPaymentException does exist.');
        }

        $handler->handleStateResponse($this->createTransactionStruct(), 'cancel');
    }

    /**
     * @dataProvider exceptionGotThrownOnErrorDataProvider
     */
    public function testIfExceptionGotThrownOnError(string|null $state): void
    {
        $handler = new PaymentStateHandler($this->createMock(Translator::class));

        if (class_exists(PaymentException::class)) {
            $this->expectException(PaymentException::class);
        } elseif (class_exists(AsyncPaymentFinalizeException::class)) {
            $this->expectException(AsyncPaymentFinalizeException::class);
        } else {
            throw new \Exception('neither PaymentException nor AsyncPaymentFinalizeException does exist.');
        }

        $handler->handleStateResponse($this->createTransactionStruct(), $state);
    }

    public static function exceptionGotThrownOnErrorDataProvider(): array
    {
        return [
            [null],
            ['error'],
        ];
    }

    private function createTransactionStruct(): AsyncPaymentTransactionStruct
    {
        $mock = $this->createMock(AsyncPaymentTransactionStruct::class);

        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getId')->willReturn(Uuid::randomHex());
        $mock->method('getOrderTransaction')->willReturn($orderTransaction);

        return $mock;
    }
}
