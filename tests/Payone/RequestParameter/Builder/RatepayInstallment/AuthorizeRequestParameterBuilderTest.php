<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\TestCaseBase\ConfigurationHelper;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AuthorizeRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;
    use ConfigurationHelper;

    public function testItAddsCorrectAuthorizeParametersWithIban(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'tx-limit-installment-min' => '10',
            ]
        );

        $dataBag = new RequestDataBag([
            'ratepayIban'                  => 'DE81500105177147426471',
            'ratepayPhone'                 => '0123456789',
            'ratepayBirthday'              => '2000-01-01',
            'ratepayInstallmentAmount'     => '100',
            'ratepayInstallmentNumber'     => '24',
            'ratepayLastInstallmentAmount' => '101',
            'ratepayInterestRate'          => '10',
            'ratepayTotalAmount'           => '1000',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayInstallmentPaymentHandler::class);
        $builder    = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => AuthorizeRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'clearingtype'                               => AuthorizeRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'iban'                                       => 'DE81500105177147426471',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[shop_id]'                       => 88880103,
                'add_paydata[installment_amount]'            => 10000,
                'add_paydata[installment_number]'            => 24,
                'add_paydata[last_installment_amount]'       => 10100,
                'add_paydata[interest_rate]'                 => 1000,
                'add_paydata[amount]'                        => 100000,
                'add_paydata[debit_paytype]'                 => 'DIRECT-DEBIT',
                'telephonenumber'                            => '0123456789',
                'birthday'                                   => '20000101',
                'it[1]'                                      => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }

    public function testItAddsCorrectAuthorizeParametersWithoutIban(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'tx-limit-installment-min' => '10',
            ]
        );

        $dataBag = new RequestDataBag([
            'ratepayPhone'                 => '0123456789',
            'ratepayBirthday'              => '2000-01-01',
            'ratepayInstallmentAmount'     => '100',
            'ratepayInstallmentNumber'     => '24',
            'ratepayLastInstallmentAmount' => '101',
            'ratepayInterestRate'          => '10',
            'ratepayTotalAmount'           => '1000',
        ]);

        $struct     = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayInstallmentPaymentHandler::class);
        $builder    = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);
        $parameters = $builder->getRequestParameter($struct);

        static::assertArrayNotHasKey('iban', $parameters);
        static::assertSame('BANK-TRANSFER', $parameters['add_paydata[debit_paytype]']);
    }

    public function testItThrowsExceptionOnMissingPhoneNumber(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'tx-limit-installment-min' => '10',
            ]
        );

        $dataBag = new RequestDataBag([
            'ratepayIban'                  => 'DE81500105177147426471',
            'ratepayBirthday'              => '2000-01-01',
            'ratepayInstallmentAmount'     => '100',
            'ratepayInstallmentNumber'     => '24',
            'ratepayLastInstallmentAmount' => '101',
            'ratepayInterestRate'          => '10',
            'ratepayTotalAmount'           => '1000',
        ]);

        $struct  = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayInstallmentPaymentHandler::class);
        $builder = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing phone number');

        $builder->getRequestParameter($struct);
    }

    public function testItAddsCorrectAuthorizeParametersWithSavedPhoneNumber(): void
    {
        $this->setValidRatepayProfiles(
            $this->getContainer(),
            PayoneRatepayInstallmentPaymentHandler::class,
            [
                'tx-limit-installment-min' => '10',
            ]
        );

        $dataBag = new RequestDataBag([
            'ratepayIban'                  => 'DE81500105177147426471',
            'ratepayBirthday'              => '2000-01-01',
            'ratepayInstallmentAmount'     => '100',
            'ratepayInstallmentNumber'     => '24',
            'ratepayLastInstallmentAmount' => '101',
            'ratepayInterestRate'          => '10',
            'ratepayTotalAmount'           => '1000',
        ]);

        $struct  = $this->getPaymentTransactionStruct($dataBag, PayoneRatepayInstallmentPaymentHandler::class);
        $builder = $this->getContainer()->get(AuthorizeRequestParameterBuilder::class);

        // Save phone number on customer custom fields
        $this->getContainer()->get('customer.repository')->update([
            [
                'id'           => $struct->getPaymentTransaction()->getOrder()->getOrderCustomer()->getCustomerId(),
                'customFields' => [
                    CustomFieldInstaller::CUSTOMER_PHONE_NUMBER => '0123456789',
                ],
            ],
        ], $struct->getSalesChannelContext()->getContext());

        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'request'                                    => AuthorizeRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE,
                'clearingtype'                               => AuthorizeRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
                'iban'                                       => 'DE81500105177147426471',
                'add_paydata[customer_allow_credit_inquiry]' => 'yes',
                'add_paydata[shop_id]'                       => 88880103,
                'add_paydata[installment_amount]'            => 10000,
                'add_paydata[installment_number]'            => 24,
                'add_paydata[last_installment_amount]'       => 10100,
                'add_paydata[interest_rate]'                 => 1000,
                'add_paydata[amount]'                        => 100000,
                'add_paydata[debit_paytype]'                 => 'DIRECT-DEBIT',
                'telephonenumber'                            => '0123456789',
                'birthday'                                   => '20000101',
                'it[1]'                                      => LineItemHydrator::TYPE_GOODS,
            ],
            $parameters
        );
    }
}