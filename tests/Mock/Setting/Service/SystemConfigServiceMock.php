<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock\Setting\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class SystemConfigServiceMock extends SystemConfigService
{
    /** @var mixed[][] */
    private $data = [];

    public function get(string $key, ?string $salesChannelId = null, bool $inherit = true)
    {
        $salesChannelId = (string) $salesChannelId;

        if (!isset($this->data[$salesChannelId][$key])) {
            return null;
        }

        return $this->data[$salesChannelId][$key] ?? null;
    }

    public function getDomain(string $domain, ?string $salesChannelId = null, bool $inherit = false): array
    {
        $values = [];
        $domain = rtrim($domain, '.') . '.';

        if ($inherit && $salesChannelId !== null) {
            foreach ($this->data[''] as $key => $value) {
                if (strpos($key, $domain) === 0) {
                    $values[$key] = $value;
                }
            }
        }
        $salesChannelId = (string) $salesChannelId;

        if (!isset($this->data[$salesChannelId])) {
            return $values;
        }

        foreach ($this->data[$salesChannelId] as $key => $value) {
            if (strpos($key, $domain) === 0) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $salesChannelId = (string) $salesChannelId;

        if (!isset($this->data[$salesChannelId])) {
            $this->data[$salesChannelId] = [];
        }
        $this->data[$salesChannelId][$key] = $value;
    }

    public function delete(string $key, ?string $salesChannel = null): void
    {
        $this->set($key, null, $salesChannel);
    }
}
