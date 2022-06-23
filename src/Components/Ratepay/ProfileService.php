<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ProfileService implements ProfileServiceInterface
{
    public const DEBIT_PROFILES_CONFIG_KEY = 'PayonePayment.settings.ratepayDebitProfiles';
    public const DEBIT_PROFILE_CONFIGURATIONS_CONFIG_KEY = 'PayonePayment.settings.ratepayDebitProfileConfigurations';
    public const INSTALLMENT_PROFILES_CONFIG_KEY = 'PayonePayment.settings.ratepayInstallmentProfiles';
    public const INSTALLMENT_PROFILE_CONFIGURATIONS_CONFIG_KEY = 'PayonePayment.settings.ratepayInstallmentProfileConfigurations';
    public const INVOICING_PROFILES_CONFIG_KEY = 'PayonePayment.settings.ratepayInvoicingProfiles';
    public const INVOICING_PROFILE_CONFIGURATIONS_CONFIG_KEY = 'PayonePayment.settings.ratepayInvoicingProfileConfigurations';

    public const PROFILES_CONFIG_KEYS = [
        self::DEBIT_PROFILES_CONFIG_KEY,
        self::INSTALLMENT_PROFILES_CONFIG_KEY,
        self::INVOICING_PROFILES_CONFIG_KEY,
    ];

    public const PROFILE_CONFIG_MAPPING = [
        self::DEBIT_PROFILES_CONFIG_KEY => [
            'paymentHandler' => PayoneRatepayDebitPaymentHandler::class,
            'paymentKey' => 'elv',
            'profileConfigurationsKey' => self::DEBIT_PROFILE_CONFIGURATIONS_CONFIG_KEY,
        ],
        self::INSTALLMENT_PROFILES_CONFIG_KEY => [
            'paymentHandler' => PayoneRatepayInstallmentPaymentHandler::class,
            'paymentKey' => 'installment',
            'profileConfigurationsKey' => self::INSTALLMENT_PROFILE_CONFIGURATIONS_CONFIG_KEY,
        ],
        self::INVOICING_PROFILES_CONFIG_KEY => [
            'paymentHandler' => PayoneRatepayInvoicingPaymentHandler::class,
            'paymentKey' => 'invoicing',
            'profileConfigurationsKey' => self::INVOICING_PROFILE_CONFIGURATIONS_CONFIG_KEY,
        ],
    ];

    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory,
        SystemConfigService $systemConfigService
    ) {
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->systemConfigService     = $systemConfigService;
    }

    public function getProfile(ProfileSearch $profileSearch): ?array
    {
        $configMapping = $this->getConfigMappingByPaymentHandler($profileSearch->getPaymentHandler());
        $paymentKey = $configMapping['paymentKey'];

        $profileConfiguration = $this->systemConfigService->get(
            $configMapping['profileConfigurationsKey'],
            $profileSearch->getSalesChannelId()
        );

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

    public function updateProfileConfiguration(string $profilesConfigKey, ?string $salesChannelId): array
    {
        $configUpdates = [];
        $errors = [];
        if (isset(self::PROFILE_CONFIG_MAPPING[$profilesConfigKey])) {
            $configMapping = self::PROFILE_CONFIG_MAPPING[$profilesConfigKey];
            $profiles = $this->systemConfigService->get($profilesConfigKey, $salesChannelId);

            $validProfiles = [];
            $configurationResponses = [];
            foreach ($profiles as $profile) {
                $shopId   = (int) $profile['shopId'];
                $currency = $profile['currency'];

                $profileRequest = $this->requestParameterFactory->getRequestParameter(
                    new RatepayProfileStruct(
                        $shopId,
                        $currency,
                        $salesChannelId ?? '',
                        $configMapping['paymentHandler'],
                        AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
                    )
                );

                try {
                    $response = $this->client->request($profileRequest);
                } catch (PayoneRequestException $exception) {
                    $profile['error'] = $exception->getResponse()['error']['ErrorMessage'];
                    $errors[$profilesConfigKey][] = $profile;
                    continue;
                }

                $configurationResponses[$shopId]  = $response['addpaydata'];
                $validProfiles[$shopId] = $profile;
            }

            $validProfiles = array_values($validProfiles);
            $this->systemConfigService->set(
                $profilesConfigKey,
                $validProfiles,
                $salesChannelId
            );
            $this->systemConfigService->set(
                $configMapping['profileConfigurationsKey'],
                $configurationResponses,
                $salesChannelId
            );
            $configUpdates[$profilesConfigKey] = $validProfiles;
            $configUpdates[$configMapping['profileConfigurationsKey']] = $configurationResponses;
        }

        return [
            'updates' => $configUpdates,
            'errors' => $errors,
        ];
    }

    protected function getConfigMappingByPaymentHandler(string $paymentHandler): array
    {
        foreach (self::PROFILE_CONFIG_MAPPING as $configMapping) {
            if ($configMapping['paymentHandler'] === $paymentHandler) {
                return $configMapping;
            }
        }

        throw new \RuntimeException('invalid payment handler');
    }
}
