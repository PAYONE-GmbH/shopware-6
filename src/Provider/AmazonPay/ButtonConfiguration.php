<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay;

use PayonePayment\Components\ConfigReader\ConfigReader;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

readonly class ButtonConfiguration
{
    private const DEFAULT_PUBLIC_KEY = 'AE5E5B7B2SAERURYEH6DKDAZ';

    private Serializer $serializer;

    public function __construct(
        private ConfigReader $configReader,
        private EntityRepository $languageRepository,
    ) {
        $this->serializer = new Serializer(encoders: [ new JsonEncoder() ]);
    }

    public function getButtonConfiguration(
        SalesChannelContext $context,
        string $location,
        array $addPayData,
        bool $isExpress,
        float|null $totalAmount = null,
        string|null $currencyIso = null,
    ): ArrayStruct {
        $config      = $this->configReader->read($context->getSalesChannelId());
        $payloadJson = $addPayData['payload'];
        $payload     = $this->serializer->decode((string) $payloadJson, JsonEncoder::FORMAT);

        if (null === $totalAmount) {
            $totalAmount = (float) $payload['paymentDetails']['totalOrderAmount']['amount'];
        }

        if (null === $currencyIso) {
            $currencyIso = $payload['paymentDetails']['totalOrderAmount']['currencyCode'];
        }

        return new ArrayStruct([
            'sandbox'                     => 'test' === $config->get('transactionMode'),
            'merchantId'                  => $config->get(\sprintf(
                '%sAmazonMerchantId',
                $isExpress ? 'amazonPayExpress' : 'amazonPay',
            )),
            'publicKeyId'                 => $addPayData['publickeyid'] ?? self::DEFAULT_PUBLIC_KEY,
            'ledgerCurrency'              => $currencyIso,
            'checkoutLanguage'            => $this->getLanguageIso($context),
            'productType'                 => 'PayAndShip',
            'placement'                   => $location,
            'buttonColor'                 => $config->get('amazonPayExpressButtonColor' . $location, 'Gold'),
            'estimatedOrderAmount'        => [
                'amount'       => $totalAmount,
                'currencyCode' => $currencyIso,
            ],
            'createCheckoutSessionConfig' => [
                'payloadJSON' => $payloadJson,
                'signature'   => $addPayData['signature'],
            ],
        ]);
    }

    private function getLanguageIso(SalesChannelContext $context): string
    {
        $criteria = new Criteria([$context->getLanguageId()]);

        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        // Amazon does have a region restricted validation of the locale. So we use the currency to match the region
        // because the currency is also restricted to the region.
        // reference: https://developer.amazon.com/de/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#function-parameters
        $currencyCode = $context->getCurrency()->getIsoCode();

        if ('EUR' === $currencyCode || 'GBP' === $currencyCode) {
            $locale = $language?->getLocale()
                ? $language->getLocale()->getCode()
                : throw new \RuntimeException('missing language')
            ;

            return match (\explode('-', (string) $locale)[0]) {
                'de'    => 'de_DE',
                'fr'    => 'fr_FR',
                'it'    => 'it_IT',
                'es'    => 'es_ES',
                default => 'en_GB'
            };
        }

        if ('USD' === $currencyCode) {
            return 'en_US';
        }

        if ('JPY' === $currencyCode) {
            return 'ja_JP';
        }

        throw new \RuntimeException('disallowed currency/region: ' . $currencyCode);
    }
}
