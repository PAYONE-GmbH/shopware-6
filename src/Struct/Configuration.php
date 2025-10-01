<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Configuration extends Struct
{
    public function __construct(
        protected array $configuration,
    ) {
    }

    public function getByPrefix(
        string $key,
        string $prefix = '',
        array|bool|int|string|null $default = '',
    ): array|bool|int|string|null {
        return $this->get(\sprintf('%s%s', $prefix, \ucfirst($key)), $default);
    }

    public function get(string $key, array|bool|int|string|null $default = ''): array|bool|int|string|null
    {
        if (!\array_key_exists($key, $this->configuration)) {
            return $default;
        }

        if (empty($this->configuration[$key])) {
            return $default;
        }

        return $this->configuration[$key];
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        if (false === \is_string($value)) {
            return $default;
        }

        return $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (false === \is_bool($value)) {
            return $default;
        }

        return $value;
    }
}
