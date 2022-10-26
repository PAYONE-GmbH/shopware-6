<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;

class ClassHelper
{
    public static function getPaymentHandlerClasses(): array
    {
        $classList = [];
        $classFiles = glob(__DIR__ . '/../../src/PaymentHandler/*.php');
        foreach ($classFiles as $classFile) {
            if (is_file($classFile)) {
                preg_match('/(.*).php/', basename($classFile), $matches);

                if ($matches && strpos($matches[1], 'Abstract') === false && strpos($matches[1], 'Interface') === false) {
                    $className = 'PayonePayment\\PaymentHandler\\' . $matches[1];

                    if (is_subclass_of($className, AbstractPayonePaymentHandler::class)) {
                        $classList[] = $className;
                    }
                }
            }
        }

        return $classList;
    }

    public static function getPaymentMethodClasses(): array
    {
        $classList = [];
        $classFiles = glob(__DIR__ . '/../../src/PaymentMethod/*.php');
        foreach ($classFiles as $classFile) {
            if (is_file($classFile)) {
                preg_match('/(.*).php/', basename($classFile), $matches);

                if ($matches && strpos($matches[1], 'Abstract') === false && strpos($matches[1], 'Interface') === false) {
                    require_once $classFile;
                    $classList[] = 'PayonePayment\\PaymentMethod\\' . $matches[1];
                }
            }
        }

        return $classList;
    }
}
