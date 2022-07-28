<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Debit;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestParameterFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class DebitAuthorizeRequestFactoryTest extends TestCase
{
    use RequestParameterFactoryTestTrait;

    public function testCorrectRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();
        $dataBag             = new RequestDataBag([
            'iban'         => '',
            'bic'          => '',
            'accountOwner' => '',
        ]);

        $factory = $this->getRequestParameterFactory($salesChannelContext);

        $request = $factory->getRequestParameter(
            new PaymentTransactionStruct(
                $this->getPaymentTransaction(),
                $dataBag,
                $salesChannelContext,
                PayoneDebitPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
            )
        );

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
}
