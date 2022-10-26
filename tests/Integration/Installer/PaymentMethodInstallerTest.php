<?php

declare(strict_types=1);

namespace PayonePayment\Integration\Installer;

use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\TestCaseBase\ClassHelper;
use PHPUnit\Framework\TestCase;

class PaymentMethodInstallerTest extends TestCase
{
    public function testIfMissingMethodRegistration(): void
    {
        foreach (ClassHelper::getPaymentMethodClasses() as $class) {
            // test UUID & payment method registered
            static::assertArrayHasKey($class, PaymentMethodInstaller::PAYMENT_METHOD_IDS, sprintf('%s needs to have a key with the classname %s', PaymentMethodInstaller::class . '::PAYMENT_METHOD_IDS', $class));
            static::assertContains($class, PaymentMethodInstaller::PAYMENT_METHODS, sprintf('%s needs registered in %s', $class, PaymentMethodInstaller::class . '::PAYMENT_METHODS'));
            static::assertTrue(\defined($class . '::UUID'), sprintf('class %s needs to implement constant %s', $class, 'UUID'));
        }
    }
}
