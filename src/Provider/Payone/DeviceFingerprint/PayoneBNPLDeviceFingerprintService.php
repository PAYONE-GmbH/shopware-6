<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\DeviceFingerprint;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredDirectDebitPaymentHandler;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredInstallmentPaymentHandler;
use PayonePayment\Provider\Payone\PaymentHandler\SecuredInvoicePaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

class PayoneBNPLDeviceFingerprintService extends AbstractDeviceFingerprintService
{
    final public const SESSION_VAR_NAME = 'payone_bnpl_device_ident_token';

    private const PAYLA_PARTNER_ID = 'e7yeryF2of8X';

    public function __construct(
        RequestStack $requestStack,
        protected ConfigReaderInterface $configReader,
        protected PaymentMethodRegistry $paymentMethodRegistry,
    ) {
        parent::__construct($requestStack);
    }

    public function getSupportedPaymentHandlerClasses(): array
    {
        return [
            SecuredDirectDebitPaymentHandler::class,
            SecuredInstallmentPaymentHandler::class,
            SecuredInvoicePaymentHandler::class,
        ];
    }

    public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string
    {
        $html = <<<'HTML'
<script id="paylaDcs" type="text/javascript" src="https://d.payla.io/dcs/%1$s/%2$s/dcs.js"></script>
<script>var paylaDcsT = paylaDcs.init("%3$s", "%4$s");</script>

<link id="paylaDcsCss" type="text/css" rel="stylesheet" href="https://d.payla.io/dcs/dcs.css?st=%4$s&pi=%1$s&psi=%2$s&e=%3$s">
HTML;

        return \sprintf(
            $html,
            self::PAYLA_PARTNER_ID,
            $this->getPartnerMerchantId($salesChannelContext),
            $this->getEnvironment($salesChannelContext),
            $deviceIdentToken,
        );
    }

    protected function getSessionVarName(): string
    {
        return self::SESSION_VAR_NAME;
    }

    protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionId = $this->getSession()?->get('sessionId') ?? '';

        return self::PAYLA_PARTNER_ID . '_' . $this->getPartnerMerchantId($salesChannelContext) . '_' . $sessionId;
    }

    protected function getPartnerMerchantId(SalesChannelContext $salesChannelContext): string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $paymentMethod = $this->paymentMethodRegistry->getByHandler(
            $salesChannelContext->getPaymentMethod()->getHandlerIdentifier(),
        );

        $configurationPrefix = $paymentMethod ? $paymentMethod::getConfigurationPrefix() : '';

        /** @var string $merchantId */
        $merchantId = $configuration->getByPrefix(
            ConfigInstaller::CONFIG_FIELD_MERCHANT_ID,
            $configurationPrefix,
            $configuration->get(ConfigInstaller::CONFIG_FIELD_MERCHANT_ID),
        );

        return $merchantId;
    }

    protected function getEnvironment(SalesChannelContext $salesChannelContext): string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $mode          = $configuration->get(ConfigInstaller::CONFIG_FIELD_TRANSACTION_MODE);

        // "t" for TEST, "p" for PROD
        return 'live' === $mode ? 'p' : 't';
    }
}
