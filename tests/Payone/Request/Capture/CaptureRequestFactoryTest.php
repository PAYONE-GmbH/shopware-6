<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Capture;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DependencyInjection\Factory\RequestBuilderFactory;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Components\RequestBuilder\CreditCardRequestBuilder;
use PayonePayment\Components\RequestBuilder\PayolutionInstallmentRequestBuilder;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\Payone\Request\Capture\CaptureRequest;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
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
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureRequestFactoryTest extends TestCase
{
    use RequestFactoryTestTrait;

    public function testCorrectFullCaptureRequestParameters(): void
    {
        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new RequestBuilderFactory([]), new NullLogger());

        $request = $factory->getRequest($this->getPaymentTransaction(), new ParameterBag(['amount' => 100]), Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    public function testCorrectPartialCaptureRequestParameters(): void
    {
        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new RequestBuilderFactory([]), new NullLogger());

        $request = $factory->getRequest($this->getPaymentTransaction(), new ParameterBag(['amount' => 100]), Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    public function testCorrectFullCaptureLineItemRequestParameters(): void
    {
        $paramterBag = new ParameterBag([
            'amount'     => 100,
            'orderLines' => [
                [
                    'id'       => Constants::LINE_ITEM_ID . '0',
                    'quantity' => Constants::LINE_ITEM_QUANTITY,
                ],
                [
                    'id'       => Constants::LINE_ITEM_ID . '1',
                    'quantity' => Constants::LINE_ITEM_QUANTITY,
                ],
            ],
        ]);

        $paymentTransaction = $this->getPaymentTransaction(2);
        $orderTransaction   = $paymentTransaction->getOrderTransaction();
        $orderTransaction->setPaymentMethodId(PayonePayolutionInstallment::UUID);
        $paymentTransaction->assign(['orderTransaction' => $orderTransaction]);

        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new RequestBuilderFactory([
            PayoneCreditCard::UUID            => new CreditCardRequestBuilder(new LineItemHydrator()),
            PayonePayolutionInstallment::UUID => new PayolutionInstallmentRequestBuilder(new LineItemHydrator()),
        ]), new NullLogger());

        $request = $factory->getRequest($paymentTransaction, $paramterBag, Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
                'it[1]'           => LineItemHydrator::TYPE_GOODS,
                'id[1]'           => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]'           => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]'           => Constants::LINE_ITEM_QUANTITY,
                'de[1]'           => Constants::LINE_ITEM_LABEL,
                'va[1]'           => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'it[2]'           => LineItemHydrator::TYPE_GOODS,
                'id[2]'           => Constants::LINE_ITEM_IDENTIFIER,
                'pr[2]'           => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[2]'           => Constants::LINE_ITEM_QUANTITY,
                'de[2]'           => Constants::LINE_ITEM_LABEL,
                'va[2]'           => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    public function testCorrectPartialCaptureLineItemRequestParameters(): void
    {
        $paramterBag = new ParameterBag();

        $paramterBag->add([
            'amount'     => 100,
            'orderLines' => [
                [
                    'id'       => Constants::LINE_ITEM_ID . '0',
                    'quantity' => Constants::LINE_ITEM_QUANTITY,
                ],
            ],
        ]);

        $paymentTransaction = $this->getPaymentTransaction();
        $orderTransaction   = $paymentTransaction->getOrderTransaction();
        $orderTransaction->setPaymentMethodId(PayonePayolutionInstallment::UUID);
        $paymentTransaction->assign(['orderTransation' => $orderTransaction]);

        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new RequestBuilderFactory([
            PayoneCreditCard::UUID            => new CreditCardRequestBuilder(new LineItemHydrator()),
            PayonePayolutionInstallment::UUID => new PayolutionInstallmentRequestBuilder(new LineItemHydrator()),
        ]), new NullLogger());

        $request = $factory->getRequest($paymentTransaction, $paramterBag, Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
                'it[1]'           => LineItemHydrator::TYPE_GOODS,
                'id[1]'           => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]'           => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]'           => Constants::LINE_ITEM_QUANTITY,
                'de[1]'           => Constants::LINE_ITEM_LABEL,
                'va[1]'           => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    protected function getPaymentTransaction(int $lineItemAmount = 1): PaymentTransaction
    {
        $currency = new CurrencyEntity();

        if (method_exists($currency, 'setDecimalPrecision')) {
            $currency->setDecimalPrecision(Constants::CURRENCY_DECIMAL_PRECISION);
        } else {
            $currency->setItemRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, true));
            $currency->setTotalRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, true));
        }

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setOrderNumber(Constants::ORDER_NUMBER);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderEntity->setLineItems($this->getLineItem($lineItemAmount));
        $orderEntity->setCurrency($currency);

        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);
        $orderTransactionEntity->setPaymentMethodId(PayoneCreditCard::UUID);
        $orderTransactionEntity->setOrder($orderEntity);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity, $orderEntity);
    }

    protected function getLineItem(int $amount): OrderLineItemCollection
    {
        $lineItemTaxRules = new TaxRule(Constants::CURRENCY_TAX_RATE);

        $taxRuleCollection = new TaxRuleCollection();
        $taxRuleCollection->add($lineItemTaxRules);

        $lineItemtax = new CalculatedTax(
            Constants::LINE_ITEM_UNIT_PRICE + (Constants::LINE_ITEM_UNIT_PRICE / 100 * Constants::CURRENCY_TAX_RATE),
            Constants::CURRENCY_TAX_RATE,
            Constants::LINE_ITEM_UNIT_PRICE
        );

        $calculatedTaxCollection = new CalculatedTaxCollection();
        $calculatedTaxCollection->add($lineItemtax);

        $lineItemPrice = new CalculatedPrice(
            Constants::LINE_ITEM_UNIT_PRICE,
            Constants::LINE_ITEM_UNIT_PRICE * Constants::LINE_ITEM_QUANTITY,
            $calculatedTaxCollection,
            $taxRuleCollection,
            Constants::LINE_ITEM_QUANTITY
        );

        $lineItemCollection = new OrderLineItemCollection();

        for ($i = 0; $i < $amount; ++$i) {
            $lineItem = new OrderLineItemEntity();
            $lineItem->setId(Constants::LINE_ITEM_ID . $i);
            $lineItem->setType(Constants::LINE_ITEM_TYPE);
            $lineItem->setIdentifier(Constants::LINE_ITEM_IDENTIFIER);
            $lineItem->setUnitPrice(Constants::LINE_ITEM_UNIT_PRICE);
            $lineItem->setPrice($lineItemPrice);
            $lineItem->setLabel(Constants::LINE_ITEM_LABEL);
            $lineItem->setQuantity(Constants::LINE_ITEM_QUANTITY);

            $lineItemCollection->add($lineItem);
        }

        return $lineItemCollection;
    }

    private function getCaptureRequest(): CaptureRequest
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyEntity     = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode('EUR');

        if (method_exists($currencyEntity, 'setDecimalPrecision')) {
            $currencyEntity->setDecimalPrecision(2);
        } else {
            $currencyEntity->setItemRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, true));
            $currencyEntity->setTotalRounding(new CashRoundingConfig(Constants::CURRENCY_DECIMAL_PRECISION, 1, true));
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

        return new CaptureRequest($currencyRepository);
    }
}
