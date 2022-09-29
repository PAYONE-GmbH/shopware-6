<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PayonePayment\TestCaseBase\ClassHelper;
use PHPUnit\Framework\TestCase;

class ConfigurationPrefixesTest extends TestCase
{
    public function testForMissingPrefixes(): void
    {
        $prefixes = ConfigurationPrefixes::CONFIGURATION_PREFIXES;

        foreach (ClassHelper::getPaymentHandlerClasses() as $paymentHandlersClass) {
            self::assertArrayHasKey($paymentHandlersClass, $prefixes, sprintf('ConfigurationPrefixes::CONFIGURATION_PREFIXES does not has a key for payment handler %s', $paymentHandlersClass));
        }
    }
}
