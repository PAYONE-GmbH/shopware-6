<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;

class ClassHelper
{
    
    /**
     * @return string[]
     */
    public static function getPaymentHandlerClasses(): array
    {
        $classList = [];
        $classFiles = glob(__DIR__ . '/../../src/PaymentHandler/*.php');
        foreach ($classFiles as $classFile) {
            if (is_file($classFile)) {
                preg_match('/(.*).php/', basename((string) $classFile), $matches);

                if ($matches && !str_contains($matches[1], 'Abstract') && !str_contains($matches[1], 'Interface')) {
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
                preg_match('/(.*).php/', basename((string) $classFile), $matches);

                if ($matches && !str_contains($matches[1], 'Abstract') && !str_contains($matches[1], 'Interface')) {
                    require_once $classFile;
                    $classList[] = 'PayonePayment\\PaymentMethod\\' . $matches[1];
                }
            }
        }

        return $classList;
    }
}
