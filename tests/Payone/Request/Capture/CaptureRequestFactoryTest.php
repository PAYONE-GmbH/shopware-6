<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Capture;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\Payone\Request\Capture\CaptureRequest;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Payone\Struct\PaymentTransaction;
use PayonePayment\Test\Mock\Components\ConfigReaderMock;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;

class CaptureRequestFactoryTest extends TestCase
{
    private const CURRENCY_ID           = '9d185b6a82224319a326a0aed4f80d0a';
    private const ORDER_ID              = 'c23b44f2778240c7ad09bee356004503';
    private const ORDER_TRANSACTION_ID  = '4c8a04d0ae374bdbac305d717cdaf9c6';
    private const PAYONE_TRANSACTION_ID = 'test-transaction-id';

    public function testCorrectRequestParameters()
    {
        $factory = new CaptureRequestFactory($this->getCaptureRequest(), $this->getSystemRequest());

        $request = $factory->getRequestParameters($this->getPaymentTransaction(), Context::createDefaultContext());

        $this->assertEquals(
            [
                'aid'            => '',
                'amount'         => 10000,
                'api_version'    => '3.10',
                'currency'       => 'EUR',
                'encoding'       => 'UTF-8',
                'key'            => '',
                'mid'            => '',
                'mode'           => '',
                'portalid'       => '',
                'request'        => 'capture',
                'sequencenumber' => 1,
                'txid'           => 'test-transaction-id',
            ],
            $request
        );
    }

    protected function getSystemRequest(): SystemRequest
    {
        return new SystemRequest(new ConfigReaderMock());
    }

    protected function getPaymentTransaction(): PaymentTransaction
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(self::ORDER_TRANSACTION_ID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(self::ORDER_ID);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(self::CURRENCY_ID);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $orderTransactionEntity->setOrder($orderEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => self::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity);
    }

    private function getCaptureRequest(): CaptureRequest
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyEntity     = new CurrencyEntity();
        $currencyEntity->setId(self::CURRENCY_ID);
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

        return new CaptureRequest($currencyRepository);
    }
}
