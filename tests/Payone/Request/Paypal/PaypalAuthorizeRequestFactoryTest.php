<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Paypal;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\PaypalAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\ShippingInformationRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PayonePayment\Test\Mock\Factory\RequestFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;

class PaypalAuthorizeRequestFactoryTest extends TestCase
{
    use RequestFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $currencyRepositoryMock           = $this->createMock(EntityRepositoryInterface::class);
        $generalTransactionRequestBuilder = $this->getGeneralTransactionRequestBuilder();
        $generalTransactionRequestBuilder->setCommonDependencies($this->createMock(RedirectHandler::class), $currencyRepositoryMock, new ConfigReaderMock([]));

        $currencyRepositoryMock->method('search')->willReturn(
            $this->getCurrencySearchResult($salesChannelContext->getContext())
        );

        //TODO: inject all builders
        $factory = new RequestParameterFactory(
            [
                new ShippingInformationRequestParameterBuilder(),
                new PaypalAuthorizeRequestParameterBuilder(),
                $this->getSystemRequestBuilder(),
                $generalTransactionRequestBuilder,
                $this->getCustomerRequestBuilder(),
            ]
        );

        $dataBag = new RequestDataBag([
        ]);

        $request = $factory->getRequestParameter(
            new PaymentTransactionStruct(
                $this->getPaymentTransaction(),
                $dataBag,
                $salesChannelContext,
                PayonePaypalPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
            )
        );

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
                'request'         => 'authorization',
                'solution_name'   => 'kellerkinder',
                'street'          => 'Some Street 1',
                'wallettype'      => 'PPE',
                'zip'             => '12345',
                'reference'       => '1',
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

        $orderCurrency = new CurrencyEntity();
        $orderCurrency->setId(Constants::CURRENCY_ID);
        $orderCurrency->setIsoCode(Constants::CURRENCY_ISO);

        $orderEntity->setCurrency($orderCurrency);

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
}
