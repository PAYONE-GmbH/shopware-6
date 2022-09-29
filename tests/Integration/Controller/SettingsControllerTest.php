<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\TestCaseBase\ClassHelper;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

class SettingsControllerTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * the functions test the private function `getPaymentParameters` to make ensure that no tests-credentials are missing for credentials-tests
     */
    public function testForMissingTestParameters(): void
    {
        $refClass = new \ReflectionClass(SettingsController::class);
        $method   = $refClass->getMethod('getPaymentParameters');
        $method->setAccessible(true);

        $controller = $this->getContainer()->get(SettingsController::class);
        foreach (ClassHelper::getPaymentHandlerClasses() as $paymentHandlerClass) {
            if (preg_match('/PayoneRatepay.*PaymentHandler/', $paymentHandlerClass)) {
                // ratepay do not have and need any test credentials
                continue;
            }

            try {
                $parameters = $method->invoke($controller, $paymentHandlerClass);
            } finally {
                self::assertIsArray($parameters ?? null, sprintf('There is no test-data defined in %s for payment handler %s', SettingsController::class, $paymentHandlerClass));
            }
        }
    }
}
