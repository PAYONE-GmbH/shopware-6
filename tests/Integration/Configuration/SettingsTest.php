<?php

declare(strict_types=1);

namespace PayonePayment\Configuration;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testIfDefaultSettingsDoesNotExistsForPaymentMethods(): void
    {
        $configFile = __DIR__ . '/../../../src/Resources/config/settings.xml';
        if (!is_file($configFile) || !is_readable($configFile)) {
            throw new \RuntimeException(sprintf('file %s dos not exist or is not readable.', $configFile));
        }

        $xml = simplexml_load_file($configFile);
        $xmlArray = json_decode(json_encode($xml), true);

        $prefixes = ConfigurationPrefixes::CONFIGURATION_PREFIXES;

        foreach ($prefixes as $prefix) {
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sMerchantId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sAccountId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPortalId', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPortalKey', $prefix)));

            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusAppointed', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusCapture', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusPartialCapture', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusPaid', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusUnderpaid', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusCancelation', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusPartialRefund', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusDebit', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusReminder', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusVauthorization', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusVsettlement', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusInvoice', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusFailed', $prefix)));
            static::assertThat($xmlArray, new ConfigKeyShouldNotExist(sprintf('%sPaymentStatusPartialRefund', $prefix)));
        }
    }
}

class ConfigKeyShouldNotExist extends Constraint
{
    /**
     * @param int|string $configKey
     */
    public function __construct(private string $configKey)
    {
    }

    public function toString(): string
    {
        return sprintf(
            'config key does NOT exists: %s',
            $this->exporter()->export($this->configKey)
        );
    }

    protected function matches($configArray): bool
    {
        if (\is_array($configArray)) {
            foreach ($configArray['card'] as $card) {
                if (\is_array($card) && isset($card['input-field'])) {
                    foreach ($card['input-field'] as $fields) {
                        if (($fields['name'] ?? null) == $this->configKey) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    protected function failureDescription(mixed $other): string
    {
        return $this->toString();
    }
}
