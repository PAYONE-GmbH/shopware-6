<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Refund;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class RefundRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsCorrectFullRefundParameters(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder    = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'        => AbstractRequestParameterBuilder::REQUEST_ACTION_DEBIT,
                'amount'         => -10000,
                'currency'       => 'EUR',
                'sequencenumber' => 1,
                'txid'           => 'test-transaction-id',
            ],
            $parameters
        );
    }

    public function testItThrowsExceptionOnMissingTransactionId(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder      = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        unset($customFields[CustomFieldInstaller::TRANSACTION_ID]);
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItThrowsExceptionOnMissingSequenceNumber(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneCreditCardPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder      = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::SEQUENCE_NUMBER] = null;
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $this->expectException(InvalidOrderException::class);
        $builder->getRequestParameter($struct);
    }

    public function testItAddsCorrectRefundParametersForDebit(): void
    {
        $dataBag = new RequestDataBag([
            'amount' => 100,
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayoneDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder      = $this->getContainer()->get(RefundRequestParameterBuilder::class);
        $customFields = $struct->getPaymentTransaction()->getCustomFields();

        $customFields[CustomFieldInstaller::TRANSACTION_DATA] = [
            [
                'request' => [
                    'iban' => 'DE61500105178278794285',
                ],
            ],
        ];
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $parameters = $builder->getRequestParameter($struct);

        static::assertSame('DE61500105178278794285', $parameters['iban']);

        $customFields[CustomFieldInstaller::TRANSACTION_DATA] = [[]];
        $struct->getPaymentTransaction()->setCustomFields($customFields);
        $parameters = $builder->getRequestParameter($struct);

        static::assertArrayNotHasKey('iban', $parameters);
    }
}
