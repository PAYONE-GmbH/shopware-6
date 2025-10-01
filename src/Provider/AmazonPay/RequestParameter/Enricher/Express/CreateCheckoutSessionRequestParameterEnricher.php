<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\RequestParameter\Enricher\Express;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\AmazonPay\Enum\AmazonPayMetaEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class CreateCheckoutSessionRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        private EntityRepository $salesChannelRepository,
        private ConfigReaderInterface $configReader,
    ) {
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestParameters = [
            'clearingtype'                          => PayoneClearingEnum::WALLET->value,
            'wallettype'                            => AmazonPayMetaEnum::WALLET_TYPE->value,
            'add_paydata[platform_id]'              => AmazonPayMetaEnum::PLATFORM_ID->value,
            'request'                               => RequestActionEnum::GENERIC_PAYMENT->value,
            'add_paydata[action]'                   => 'createCheckoutSessionPayload',
            'add_paydata[addressRestrictions_type]' => 'Allowed',
        ];

        foreach ($this->getCountryCodes($arguments->salesChannelContext) as $index => $countryCode) {
            $requestParameters['add_paydata[addressRestrictions_country_' . $index . ']'] = $countryCode;
        }

        $config       = $this->configReader->read($arguments->salesChannelContext->getSalesChannelId());
        $configPrefix = 'amazonPayExpress';

        $specialRestrictions = [];

        if (true === $config->get($configPrefix . 'RestrictPOBoxes')) {
            $specialRestrictions[] = 'RestrictPOBoxes';
        }

        if (true === $config->get($configPrefix . 'RestrictPackstations')) {
            $specialRestrictions[] = 'RestrictPackstations';
        }

        if ([] !== $specialRestrictions) {
            $requestParameters['add_paydata[specialRestrictions]'] = \implode(',', $specialRestrictions);
        }

        return $requestParameters;
    }

    private function getCountryCodes(SalesChannelContext $context): array
    {
        $criteria = new Criteria([$context->getSalesChannelId()]);
        $criteria->addAssociation('countries');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context->getContext())->first();
        if (!$salesChannel || !$salesChannel->getCountries()) {
            return []; // should never occur
        }

        return \array_map(
            static fn (CountryEntity $country) => $country->getIso(),
            $salesChannel->getCountries()->getElements(),
        );
    }
}
