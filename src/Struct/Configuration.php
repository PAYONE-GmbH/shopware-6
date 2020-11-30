<?php

declare(strict_types=1);

namespace PayonePayment\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Configuration extends Struct
{
    protected $configuration = [];

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function get(string $key, $default = '')
    {
        if (!array_key_exists($key, $this->configuration)) {
            return $default;
        }

        if (empty($this->configuration[$key])) {
            return $default;
        }

        return $this->configuration[$key];
    }
}
