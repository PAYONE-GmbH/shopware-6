<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testIfDefaultSettingsExistsForPaymentMethods(): void
    {
        $configFile = __DIR__ . '/../../../src/Resources/config/settings.xml';
        if (!is_file($configFile) || !is_readable($configFile)) {
            throw new \RuntimeException(sprintf('file %s dos not exist or is not readable.', $configFile));
        }

        $xml = simplexml_load_file($configFile);
        $xmlArray = json_decode(json_encode($xml), true);

        $prefixes = ConfigurationPrefixes::CONFIGURATION_PREFIXES;

        foreach ($prefixes as $prefix) {
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sMerchantId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sAccountId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPortalId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPortalKey', $prefix)));
            //self::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sAuthorizationMethod', $prefix))); // is not supported by all methods
            //self::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sProvideNarrativeText', $prefix))); // is not supported by all methods
            if (strpos($prefix, 'ratepay') === 0) {
                // skip status checks
                continue;
            }
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusAppointed', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusCapture', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusPartialCapture', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusPaid', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusUnderpaid', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusCancelation', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusPartialRefund', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusDebit', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusReminder', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusVauthorization', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusVsettlement', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusInvoice', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusFailed', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyMatches('name', sprintf('%sPaymentStatusPartialRefund', $prefix)));
        }
    }
}

class ConfigKeyMatches extends Constraint
{
    /**
     * @var int|string
     */
    private $key;

    /**
     * @var int|string
     */
    private $value;

    /**
     * @param int|string $key
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function toString(): string
    {
        return sprintf(
            'config key exists %s with value %s',
            $this->exporter()->export($this->key),
            $this->exporter()->export($this->value)
        );
    }

    protected function matches($configArray): bool
    {
        if (\is_array($configArray)) {
            foreach ($configArray['card'] as $card) {
                if (\is_array($card) && isset($card['input-field'])) {
                    foreach ($card['input-field'] as $fields) {
                        if ($fields[$this->key] === $this->value) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns the description of the failure.
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other evaluated value or object
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    protected function failureDescription($other): string
    {
        return 'an array ' . $this->toString();
    }
}
