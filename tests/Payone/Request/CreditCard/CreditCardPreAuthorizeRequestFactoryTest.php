<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\CreditCard;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestParameterFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CreditCardPreAuthorizeRequestFactoryTest extends TestCase
{
    use RequestParameterFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $factory = $this->getRequestParameterFactory($salesChannelContext);

        $request = $factory->getRequestParameter(
            new PaymentTransactionStruct(
                $this->getPaymentTransaction(),
                new RequestDataBag(['pseudoCardPan' => 'my-pan']),
                $salesChannelContext,
                PayoneCreditCardPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE
            )
        );

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'city'            => 'Some City',
                'clearingtype'    => 'cc',
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
                'pseudocardpan'   => 'my-pan',
                'reference'       => '1',
                'request'         => 'preauthorization',
                'solution_name'   => 'kellerkinder',
                'street'          => 'Some Street 1',
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
        $orderEntity->setTransactions(new OrderTransactionCollection([]));

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
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
