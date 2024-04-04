<?php

declare(strict_types=1);

namespace PayonePayment\Integration\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\TestCaseBase\ClassHelper;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function testIfUuidIsSetAndUnique(): void
    {
        $idMapping = [];
        foreach (ClassHelper::getPaymentMethodClasses() as $class) {
            /** @var AbstractPaymentMethod $instance */
            $instance = new $class();
            static::assertInstanceOf(PaymentMethodInterface::class, $instance);

            $id = \constant($class . '::UUID');
            static::assertNotNull($id, 'id for ' . $class . ' should not be null.');
            static::assertEquals($id, $instance->getId(), sprintf('%s needs to be the same values as %s', $class . '::$id', $class . '::UUID'));
            static::assertArrayNotHasKey($instance->getId(), $idMapping, 'id for ' . $class . ' does already exist for payment method ' . ($idMapping[$instance->getId()] ?? null));
            $idMapping[$instance->getId()] = $class;
        }
    }

    public function testIfTechnicalNameIsSetAndUnique(): void
    {
        $technicalMapping = [];
        foreach (ClassHelper::getPaymentMethodClasses() as $class) {
            /** @var AbstractPaymentMethod $instance */
            $instance = new $class();
            static::assertInstanceOf(PaymentMethodInterface::class, $instance);

            $technicalName = \constant($class . '::TECHNICAL_NAME');
            static::assertNotNull($technicalName, 'technical name for ' . $class . ' should not be null.');
            static::assertEquals($technicalName, $instance->getTechnicalName(), 'method ' . $class . '::getTechnicalName should return the same value as ' . $class . '::TECHNICAL_NAME');
            static::assertStringStartsWith('payone_', $technicalName, 'technical-name for ' . $class . ' should start with `payone_`');
            static::assertArrayNotHasKey($technicalName, $technicalMapping, 'technical.name for ' . $class . ' does already exist for payment method ' . ($technicalMapping[$technicalName] ?? null));
            $technicalMapping[$technicalName] = $class;
        }
    }
}
