<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PayoneBNPLDeviceFingerprintService extends AbstractDeviceFingerprintService
{
    public const SESSION_VAR_NAME = 'payone_bnpl_device_ident_token';
    private const PAYLA_PARTNER_ID = 'e7yeryF2of8X';

    protected ConfigReaderInterface $configReader;

    public function __construct(SessionInterface $session, ConfigReaderInterface $configReader)
    {
        parent::__construct($session);
        $this->configReader = $configReader;
    }

    public function getSupportedPaymentHandlerClasses(): array
    {
        return PaymentHandlerGroups::BNPL;
    }

    public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string
    {
        return sprintf(
            '<script id="paylaDcs" type="text/javascript" src="https://d.payla.io/dcs/%1$s/%2$s/dcs.js"></script>
             <script>
                var paylaDcsT = paylaDcs.init("%3$s", "%4$s");
             </script>

             <link id="paylaDcsCss" type="text/css" rel="stylesheet" href="https://d.payla.io/dcs/dcs.css?st=%4$s&pi=%1$s&psi=%2$s&e=%3$s">',
            self::PAYLA_PARTNER_ID,
            $this->getPartnerMerchantId($salesChannelContext),
            $this->getEnvironment($salesChannelContext),
            $deviceIdentToken
        );
    }

    protected function getSessionVarName(): string
    {
        return self::SESSION_VAR_NAME;
    }

    protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionId = $this->session->get('sessionId');

        return self::PAYLA_PARTNER_ID . '_' . $this->getPartnerMerchantId($salesChannelContext) . '_' . $sessionId;
    }

    protected function getPartnerMerchantId(SalesChannelContext $salesChannelContext): string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());

        $paymentMethod = $salesChannelContext->getPaymentMethod()->getHandlerIdentifier();
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod] ?? '';

        /** @var string $merchantId */
        $merchantId = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_MERCHANT_ID,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID)
        );

        return $merchantId;
    }

    protected function getEnvironment(SalesChannelContext $salesChannelContext): string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());

        $mode = $configuration->get(ConfigInstaller::CONFIG_FIELD_TRANSACTION_MODE);

        // "t" for TEST, "p" for PROD
        return $mode === 'live' ? 'p' : 't';
    }
}
