<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Ratepay\Service;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Provider\Ratepay\PaymentHandler\DebitPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InstallmentPaymentHandler;
use PayonePayment\Provider\Ratepay\PaymentHandler\InvoicePaymentHandler;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class RatepayDeviceFingerprintService extends AbstractDeviceFingerprintService
{
    final public const SESSION_VAR_NAME = 'payone_ratepay_device_ident_token';

    private readonly Serializer $serializer;

    public function __construct(
        RequestStack $requestStack,
        protected ConfigReaderInterface $configReader,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
        parent::__construct($requestStack);

        $this->serializer = new Serializer([], [ new JsonEncoder() ]);
    }

    #[\Override]
    public function getSupportedPaymentHandlerClasses(): array
    {
        return [
            DebitPaymentHandler::class,
            InstallmentPaymentHandler::class,
            InvoicePaymentHandler::class,
        ];
    }

    #[\Override]
    public function getDeviceIdentSnippet(string $deviceIdentToken, SalesChannelContext $salesChannelContext): string
    {
        $location  = 'Checkout';
        $snippetId = $this->getSnippetId($salesChannelContext);

        $snippet = \sprintf(
            '<script language="JavaScript">var di = %s;</script>',
            $this->serializer->encode([
                'v' => $snippetId,
                't' => $deviceIdentToken,
                'l' => $location,
            ], JsonEncoder::FORMAT),
        );

        $html = <<<'HTML'
<script type="text/javascript" src="//d.ratepay.com/%1$s/di.js"></script>
<noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?v=%1$s&t=%2$s&l=%3$s" /></noscript>
HTML;

        $snippet .= \sprintf($html, $snippetId, $deviceIdentToken, $location);

        return $snippet;
    }

    #[\Override]
    protected function getSessionVarName(): string
    {
        return self::SESSION_VAR_NAME;
    }

    #[\Override]
    protected function buildDeviceIdentToken(SalesChannelContext $salesChannelContext): string
    {
        $sessionId = $this->getSession()?->get('sessionId') ?? '';

        return \md5($sessionId . '_' . \microtime());
    }

    protected function getSnippetId(SalesChannelContext $salesChannelContext): string
    {
        $configuration           = $this->configReader->read($salesChannelContext->getSalesChannelId());
        $paymentHandlerClassName = $salesChannelContext->getPaymentMethod()->getHandlerIdentifier();
        $paymentMethod           = $this->paymentMethodRegistry->getByHandler($paymentHandlerClassName);
        $configurationPrefix     = '';

        if ($paymentMethod instanceof PaymentMethodInterface) {
            $configurationPrefix = $paymentMethod::getConfigurationPrefix();
        }

        /** @var string $snippetId */
        $snippetId = $configuration->getByPrefix(
            'DeviceFingerprintSnippetId',
            $configurationPrefix,
            'ratepay',
        );

        return $snippetId;
    }
}
