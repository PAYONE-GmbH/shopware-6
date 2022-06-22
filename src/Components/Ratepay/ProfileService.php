<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProfileService implements ProfileServiceInterface
{
    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getProfile(ProfileSearch $profileSearch): ?array
    {
        $profileConfiguration = $this->getConfigurationByPaymentHandler(
            $profileSearch->getPaymentHandler(),
            $profileSearch->getSalesChannelId()
        );

        $paymentKey = $this->getPaymentKeyByPaymentHandler($profileSearch->getPaymentHandler());

        foreach ($profileConfiguration as $shopId => $configuration) {
            if ($profileSearch->isNeedsAllowDifferentAddress() && $configuration['delivery-address-' . $paymentKey] !== 'yes') {
                continue;
            }

            $allowedBillingCountries = explode(',', $configuration['country-code-billing']);
            if (!in_array($profileSearch->getBillingCountryCode(), $allowedBillingCountries, true)) {
                continue;
            }

            $allowedDeliveryCountries = explode(',', $configuration['country-code-delivery']);
            if (!in_array($profileSearch->getShippingCountryCode(), $allowedDeliveryCountries, true)) {
                continue;
            }

            $allowedCurrencies = explode(',', $configuration['currency']);
            if (!in_array($profileSearch->getCurrency(), $allowedCurrencies, true)) {
                continue;
            }

            if ($profileSearch->getTotalAmount() > $configuration['tx-limit-' . $paymentKey . '-max']) {
                continue;
            }

            if ($profileSearch->getTotalAmount() < $configuration['tx-limit-' . $paymentKey . '-min']) {
                continue;
            }

            return [
                'shopId' => $shopId,
                'configuration' => $configuration,
            ];
        }

        return null;
    }

    protected function getConfigurationByPaymentHandler(string $paymentHandler, ?string $salesChannelId): array
    {
        switch ($paymentHandler) {
            case PayoneRatepayDebitPaymentHandler::class:
                return $this->systemConfigService->get(
                    'PayonePayment.settings.ratepayDebitProfileConfigurations',
                    $salesChannelId
                );
            case PayoneRatepayInstallmentPaymentHandler::class:
                return $this->systemConfigService->get(
                    'PayonePayment.settings.ratepayInstallmentProfileConfigurations',
                    $salesChannelId
                );
            case PayoneRatepayInvoicingPaymentHandler::class:
                return $this->systemConfigService->get(
                    'PayonePayment.settings.ratepayInvoicingProfileConfigurations',
                    $salesChannelId
                );
        }

        return [];
    }

    protected function getPaymentKeyByPaymentHandler(string $paymentHandler): string
    {
        switch ($paymentHandler) {
            case PayoneRatepayDebitPaymentHandler::class:
                return 'elv';
            case PayoneRatepayInstallmentPaymentHandler::class:
                return 'invoice';
            case PayoneRatepayInvoicingPaymentHandler::class:
                return 'installment';
        }

        throw new \RuntimeException('invalid payment handler');
    }
}
