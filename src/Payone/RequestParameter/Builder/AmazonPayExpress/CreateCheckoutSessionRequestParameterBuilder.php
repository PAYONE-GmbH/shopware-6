<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\AmazonPayExpress;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\GenericExpressCheckout\Struct\CreateExpressCheckoutSessionStruct;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CreateCheckoutSessionRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(
        RequestBuilderServiceAccessor $serviceAccessor,
        private readonly EntityRepository $salesChannelRepository,
        private readonly ConfigReaderInterface $configReader
    ) {
        parent::__construct($serviceAccessor);
    }

    /**
     * @param CreateExpressCheckoutSessionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $requestParameters = array_merge(parent::getRequestParameter($arguments), [
            'request' => self::REQUEST_ACTION_GENERIC_PAYMENT,
            'add_paydata[action]' => 'createCheckoutSessionPayload',
            'add_paydata[addressRestrictions_type]' => 'Allowed',
        ]);

        foreach ($this->getCountryCodes($arguments->getSalesChannelContext()) as $index => $countryCode) {
            $requestParameters['add_paydata[addressRestrictions_country_' . $index . ']'] = $countryCode;
        }

        $config = $this->configReader->read($arguments->getSalesChannelContext()->getSalesChannelId());
        $configPrefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$arguments->getPaymentMethod()];

        $specialRestrictions = [];
        if ($config->get($configPrefix . 'RestrictPOBoxes') === true) {
            $specialRestrictions[] = 'RestrictPOBoxes';
        }

        if ($config->get($configPrefix . 'RestrictPackstations') === true) {
            $specialRestrictions[] = 'RestrictPackstations';
        }

        if ($specialRestrictions !== []) {
            $requestParameters['add_paydata[specialRestrictions]'] = implode(',', $specialRestrictions);
        }

        return $requestParameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof CreateExpressCheckoutSessionStruct;
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

        return array_map(static fn (CountryEntity $country) => $country->getIso(), $salesChannel->getCountries()->getElements());
    }
}
