<?php

declare(strict_types=1);

namespace PayonePayment\Test\TestCaseBase;

use PayonePayment\Components\Ratepay\ProfileService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

trait ConfigurationHelper
{
    protected function setValidRatepayProfiles(
        SystemConfigService $systemConfigService,
        string $paymentHandler,
        array $configurationOverrides = []
    ): void {
        $configurations = $this->getValidRatepayProfileConfigurations();

        if ($configurationOverrides !== []) {
            foreach ($configurations as $shopId => $configuration) {
                $configurations[$shopId] = array_merge($configuration, $configurationOverrides);
            }
        }

        $configMapping = ProfileService::getConfigMappingByPaymentHandler($paymentHandler);
        $systemConfigService->set($configMapping['profilesKey'], $this->getValidRatepayProfiles());
        $systemConfigService->set($configMapping['profileConfigurationsKey'], $configurations);
    }

    protected function getValidRatepayProfiles(): array
    {
        // It's public available, so we don't need to hide it
        // (see: https://docs.payone.com/pages/releaseview.action?pageId=1213981)
        return [
            [
                'shopId'   => '88880103',
                'currency' => 'EUR',
            ],
        ];
    }

    protected function getValidRatepayProfileConfigurations(): array
    {
        return [
            '88880103' => [
                'name'                                => 'PAYONE_TE_DEU',
                'type'                                => 'DEFAULT',
                'b2b-elv'                             => 'yes',
                'currency'                            => 'EUR',
                'shop-name'                           => 'PAYONE',
                'profile-id'                          => 'PAYONE_TE_DEU',
                'b2b-PQ-full'                         => 'no',
                'b2b-invoice'                         => 'yes',
                'merchant-name'                       => 'PAYONE',
                'month-allowed'                       => '3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24',
                'month-longrun'                       => '0',
                'b2b-prepayment'                      => 'yes',
                'payment-amount'                      => '60',
                'service-charge'                      => '0',
                'b2b-installment'                     => 'no',
                'merchant-status'                     => '2',
                'rate-min-normal'                     => '20',
                'interestrate-max'                    => '13.7',
                'interestrate-min'                    => '13.7',
                'month-number-max'                    => '48',
                'month-number-min'                    => '3',
                'payment-firstday'                    => '2',
                'payment-lastrate'                    => '0',
                'rate-min-longrun'                    => '0',
                'tx-limit-elv-max'                    => '99999',
                'tx-limit-elv-min'                    => '0',
                'amount-min-longrun'                  => '0',
                'country-code-billing'                => 'DE',
                'delivery-address-elv'                => 'yes',
                'interestrate-default'                => '13.7',
                'tx-limit-invoice-max'                => '99999',
                'tx-limit-invoice-min'                => '0',
                'activation-status-elv'               => '2',
                'country-code-delivery'               => 'DE',
                'min-difference-dueday'               => '2',
                'eligibility-ratepay-elv'             => 'yes',
                'tx-limit-prepayment-max'             => '99999',
                'tx-limit-prepayment-min'             => '0',
                'valid-payment-firstdays'             => '2',
                'delivery-address-PQ-full'            => 'no',
                'delivery-address-invoice'            => 'yes',
                'tx-limit-installment-max'            => '99999',
                'tx-limit-installment-min'            => '60',
                'activation-status-invoice'           => '2',
                'delivery-address-prepayment'         => 'yes',
                'eligibility-ratepay-invoice'         => 'yes',
                'eligibility-ratepay-pq-full'         => 'yes',
                'activation-status-prepayment'        => '2',
                'delivery-address-installment'        => 'yes',
                'activation-status-installment'       => '2',
                'eligibility-ratepay-prepayment'      => 'yes',
                'eligibility-ratepay-installment'     => 'yes',
                'interest-rate-merchant-towards-bank' => '9.8',
            ],
        ];
    }
}
