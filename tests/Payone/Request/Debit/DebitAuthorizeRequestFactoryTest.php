<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Debit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\ConfigReader\ConfigReader;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequest;
use PayonePayment\Payone\Request\Debit\DebitAuthorizeRequestFactory;
use PayonePayment\Struct\Configuration;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class DebitAuthorizeRequestFactoryTest extends TestCase
{
    use RequestFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $factory = new DebitAuthorizeRequestFactory($this->getDebitAuthorizeRequest(), $this->getCustomerRequest(), $this->getSystemRequest());

        $salesChannelContext = $this->getSalesChannelContext();

        $dataBag = new RequestDataBag([
            'iban'         => '',
            'bic'          => '',
            'accountOwner' => '',
        ]);
        $request = $factory->getRequestParameters($this->getPaymentTransaction(), $dataBag, $salesChannelContext);

        Assert::assertArraySubset(
            [
                'aid'               => '',
                'amount'            => 10000,
                'api_version'       => '3.10',
                'bankaccountholder' => '',
                'bic'               => '',
                'city'              => 'Some City',
                'clearingtype'      => 'elv',
                'currency'          => 'EUR',
                'email'             => 'first.last@example.com',
                'encoding'          => 'UTF-8',
                'firstname'         => 'First',
                'iban'              => '',
                'integrator_name'   => 'shopware6',
                'key'               => '',
                'language'          => 'de',
                'lastname'          => 'Last',
                'mid'               => '',
                'mode'              => '',
                'portalid'          => '',
                'reference'         => '1',
                'request'           => 'authorization',
                'solution_name'     => 'kellerkinder',
                'street'            => 'Some Street 1',
                'zip'               => '12345',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    protected function getPaymentTransaction(): PaymentTransaction
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setOrderNumber('1');
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setTransactions(new OrderTransactionCollection([]));

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneDebitPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    private function getDebitAuthorizeRequest(): DebitAuthorizeRequest
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyEntity     = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode('EUR');

        if (method_exists($currencyEntity, 'setDecimalPrecision')) {
            $currencyEntity->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currencyEntity->setItemRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );

            $currencyEntity->setTotalRounding(
                new CashRoundingConfig(
                    Constants::CURRENCY_DECIMAL_PRECISION,
                    Constants::ROUNDING_INTERVAL,
                    true)
            );
        }

        try {
            $entitySearchResult = new EntitySearchResult(
                CurrencyEntity::class,
                1,
                new EntityCollection([$currencyEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $entitySearchResult = new EntitySearchResult(1, new EntityCollection([$currencyEntity]), null, new Criteria(), Context::createDefaultContext());
        }

        $currencyRepository->method('search')->willReturn($entitySearchResult);

        $configReader = $this->createMock(ConfigReader::class);
        $configReader->method('read')->willReturn(
            new Configuration([
                sprintf('%sProvideNarrativeText', ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD) => false,
            ])
        );

        return new DebitAuthorizeRequest($currencyRepository, $configReader);
    }
}
