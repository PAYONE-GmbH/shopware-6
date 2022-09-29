<?php

declare(strict_types=1);

namespace PayonePayment\Integration\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\TestCaseBase\ClassHelper;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function testSameUuid(): void
    {
        foreach (ClassHelper::getPaymentMethodClasses() as $class) {
            /** @var AbstractPaymentMethod $instance */
            $instance = new $class();
            self::assertInstanceOf(PaymentMethodInterface::class, $instance);
            // test if UUID is the same as $id
            self::assertEquals(constant($class . '::UUID'), $instance->getId(), sprintf('%s needs to be the same values as %s', $class . '::$id', $class . '::UUID'));
        }
    }
}
