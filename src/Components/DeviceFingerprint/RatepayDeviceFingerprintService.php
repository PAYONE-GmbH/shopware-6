<?php

declare(strict_types=1);

namespace PayonePayment\Components\DeviceFingerprint;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

class RatepayDeviceFingerprintService extends AbstractDeviceFingerprintService
{
    final public const SESSION_VAR_NAME = 'payone_ratepay_device_ident_token';

    public function __construct(RequestStack $requestStack, protected ConfigReaderInterface $configReader)
    {
        parent::__construct($requestStack);
    }

    public function getSupportedPaymentHandlerClasses(): array
    {
        return [
            PayoneRatepayDebitPaymentHandler::class,
            PayoneRatepayInstallmentPaymentHandler::class,
            PayoneRatepayInvoicingPaymentHandler::class,
        ];
    }

    public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string
    {
        $location = 'Checkout';
        $snippetId = $this->getSnippetId($salesChannelContext);

        $snippet = sprintf(
            '<script language="JavaScript">var di = %s;</script>',
            json_encode([
                'v' => $snippetId,
                't' => $deviceIdentToken,
                'l' => $location,
            ], \JSON_THROW_ON_ERROR)
        );

        $snippet .= sprintf(
            '<script type="text/javascript" src="//d.ratepay.com/%1$s/di.js"></script>
             <noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?v=%1$s&t=%2$s&l=%3$s" /></noscript>',
            $snippetId,
            $deviceIdentToken,
            $location
        );

        return $snippet;
    }

    protected function getSessionVarName(): string
    {
        return self::SESSION_VAR_NAME;
    }

    protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionId = $this->getSession()?->get('sessionId') ?? '';

        return md5($sessionId . '_' . microtime());
    }

    protected function getSnippetId(SalesChannelContext $salesChannelContext): string
    {
        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());

        $paymentMethod = $salesChannelContext->getPaymentMethod()->getHandlerIdentifier();
        $configurationPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod] ?? '';

        /** @var string $snippetId */
        $snippetId = $configuration->getByPrefix(
            'DeviceFingerprintSnippetId',
            $configurationPrefix,
            'ratepay'
        );

        return $snippetId;
    }
}
