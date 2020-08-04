<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Paypal;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\CartHasher\CartHasher;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequest;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaypalAuthorizeRequestFactoryTest extends TestCase
{
    use RequestFactoryTestTrait;

    public function testCorrectRequestParameters()
    {
        $factory = new PaypalAuthorizeRequestFactory(
            $this->getPaypalAuthorizeRequest(),
            $this->getCustomerRequest(),
            $this->getSystemRequest(),
            new CartHasher()
        );

        $salesChannelContext = $this->getSalesChannelContext();

        $dataBag = new RequestDataBag([
        ]);
        $request = $factory->getRequestParameters($this->getPaymentTransaction(), $dataBag, $salesChannelContext);

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'city'            => 'Some City',
                'clearingtype'    => 'wlt',
                'currency'        => 'EUR',
                'email'           => 'first.last@example.com',
                'encoding'        => 'UTF-8',
                'firstname'       => 'First',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'language'        => 'de',
                'lastname'        => 'Last',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'reference'       => '1_0',
                'request'         => 'authorization',
                'solution_name'   => 'kellerkinder',
                'street'          => 'Some Street 1',
                'wallettype'      => 'PPE',
                'zip'             => '12345',
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

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayonePaypalPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        $paymentTransactionStruct = new AsyncPaymentTransactionStruct($orderTransactionEntity, $orderEntity, 'test-url');

        return PaymentTransaction::fromAsyncPaymentTransactionStruct($paymentTransactionStruct, $orderEntity);
    }

    private function getPaypalAuthorizeRequest(): PaypalAuthorizeRequest
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyEntity     = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode('EUR');
        $currencyEntity->setDecimalPrecision(2);
        $currencyRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$currencyEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        return new PaypalAuthorizeRequest($this->createMock(RedirectHandler::class), $currencyRepository);
    }
}
