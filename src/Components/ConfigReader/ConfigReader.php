<?php

declare(strict_types=1);

namespace PayonePayment\Components\ConfigReader;

use PayonePayment\Components\ConfigReader\Exception\ConfigurationPrefixMissingException;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Struct\Configuration;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader implements ConfigReaderInterface
{
    final public const SYSTEM_CONFIG_DOMAIN = 'PayonePayment.settings.';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    /**
     * @throws ConfigurationPrefixMissingException
     */
    public function getConfigKeyByPaymentHandler(string $paymentHandler, string $configuration): string
    {
        $paymentMethod = $this->paymentMethodRegistry->getByHandler($paymentHandler);

        if (null === $paymentMethod) {
            throw new ConfigurationPrefixMissingException(\sprintf(
                'No configuration prefix for payment handler "%s" found!',
                $paymentHandler,
            ));
        }

        return self::SYSTEM_CONFIG_DOMAIN
            . $paymentMethod::getConfigurationPrefix()
            . $configuration
        ;
    }

    public function read(?string $salesChannelId = null, bool $fallback = true): Configuration
    {
        $values = $this->systemConfigService->getDomain(
            self::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $fallback,
        );

        $config = [];

        foreach ($values as $key => $value) {
            $property = \substr((string) $key, \strlen(self::SYSTEM_CONFIG_DOMAIN));

            $config[$property] = $value;
        }

        return new Configuration($config);
    }
}
