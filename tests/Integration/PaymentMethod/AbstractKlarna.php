<?php

declare(strict_types=1);

namespace PayonePayment\Integration\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PHPUnit\Framework\TestCase;

abstract class AbstractKlarna extends TestCase
{
    /**
     * test if the payment methods for Klarna does not have a translation for the `name`, because the german value are
     * the product names for the payment name. So they should be not translated into any language.
     */
    public function testNoTranslationForEnDe(): void
    {
        $className = $this->getClassToTest();

        /** @var AbstractPaymentMethod $paymentMethod */
        $paymentMethod = new $className();

        foreach ($paymentMethod->getTranslations() as $locale => $translations) {
            static::assertArrayNotHasKey('name', $translations, sprintf('please do not specify a translation for the key `name`. PaymentMethod: %s, Locale: %s', $className, $locale));
        }
    }

    private function getClassToTest(): string
    {
        $class = preg_replace('/Test$/', '', static::class);
        $class = preg_replace('/\\\Integration/', '', $class);

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Class %s does not exist', $class));
        }

        return $class;
    }
}
