<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\Constants;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\OrderLinesRequestParameterBuilder
 */
class OrderLinesRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    /**
     * @dataProvider getValidPaymentHandler
     * @testdox It supports payment handler $paymentHandler
     */
    public function testItSupportsValidPaymentMethods($paymentHandler): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new ParameterBag(),
            $paymentHandler,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        static::assertTrue($builder->supports($struct));
    }

    /**
     * @dataProvider getInvalidPaymentHandler
     * @testdox It not supports payment handler $paymentHandler
     */
    public function testItNotSupportsInvalidPaymentMethods($paymentHandler): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new ParameterBag(),
            $paymentHandler,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsPaymentRequests(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayonePayolutionDebitPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        static::assertFalse($builder->supports($struct));
    }

    public function testItAddsCorrectOrderLineParametersForFullCapture(): void
    {
        $dataBag = new ParameterBag();

        $dataBag->add([
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

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        $struct->getPaymentTransaction()->getOrder()->setLineItems($this->getOrderLineItem(2));
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'it[1]' => LineItemHydrator::TYPE_GOODS,
                'id[1]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]' => Constants::LINE_ITEM_QUANTITY,
                'de[1]' => Constants::LINE_ITEM_LABEL,
                'va[1]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'it[2]' => LineItemHydrator::TYPE_GOODS,
                'id[2]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[2]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[2]' => Constants::LINE_ITEM_QUANTITY,
                'de[2]' => Constants::LINE_ITEM_LABEL,
                'va[2]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $parameters
        );
    }

    public function testItAddsCorrectOrderLineParametersForPartialCapture(): void
    {
        $dataBag = new ParameterBag();

        $dataBag->add([
            'amount'     => 100,
            'orderLines' => [
                [
                    'id'       => Constants::LINE_ITEM_ID . '0',
                    'quantity' => Constants::LINE_ITEM_QUANTITY,
                ],
            ],
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        $struct->getPaymentTransaction()->getOrder()->setLineItems($this->getOrderLineItem(2));
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'it[1]' => LineItemHydrator::TYPE_GOODS,
                'id[1]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]' => Constants::LINE_ITEM_QUANTITY,
                'de[1]' => Constants::LINE_ITEM_LABEL,
                'va[1]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $parameters
        );

        static::assertArrayNotHasKey('it[2]', $parameters);
    }

    public function testItAddsCorrectOrderLineParametersForFullRefund(): void
    {
        $dataBag = new ParameterBag();

        $dataBag->add([
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

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        $struct->getPaymentTransaction()->getOrder()->setLineItems($this->getOrderLineItem(2));
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'it[1]' => LineItemHydrator::TYPE_GOODS,
                'id[1]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]' => Constants::LINE_ITEM_QUANTITY,
                'de[1]' => Constants::LINE_ITEM_LABEL,
                'va[1]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'it[2]' => LineItemHydrator::TYPE_GOODS,
                'id[2]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[2]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[2]' => Constants::LINE_ITEM_QUANTITY,
                'de[2]' => Constants::LINE_ITEM_LABEL,
                'va[2]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $parameters
        );
    }

    public function testItAddsCorrectOrderLineParametersForPartialRefund(): void
    {
        $dataBag = new ParameterBag();

        $dataBag->add([
            'amount'     => 100,
            'orderLines' => [
                [
                    'id'       => Constants::LINE_ITEM_ID . '0',
                    'quantity' => Constants::LINE_ITEM_QUANTITY,
                ],
            ],
        ]);

        $struct = $this->getFinancialTransactionStruct(
            $dataBag,
            PayonePayolutionInstallmentPaymentHandler::class,
            AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND
        );

        $builder = $this->getContainer()->get(OrderLinesRequestParameterBuilder::class);

        $struct->getPaymentTransaction()->getOrder()->setLineItems($this->getOrderLineItem(2));
        $parameters = $builder->getRequestParameter($struct);

        Assert::assertArraySubset(
            [
                'it[1]' => LineItemHydrator::TYPE_GOODS,
                'id[1]' => Constants::LINE_ITEM_IDENTIFIER,
                'pr[1]' => (int) (Constants::LINE_ITEM_UNIT_PRICE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
                'no[1]' => Constants::LINE_ITEM_QUANTITY,
                'de[1]' => Constants::LINE_ITEM_LABEL,
                'va[1]' => (int) (Constants::CURRENCY_TAX_RATE * (10 ** Constants::CURRENCY_DECIMAL_PRECISION)),
            ],
            $parameters
        );

        static::assertArrayNotHasKey('it[2]', $parameters);
    }

    public function getValidPaymentHandler(): array
    {
        return [
            [PayonePayolutionDebitPaymentHandler::class],
            [PayonePayolutionInstallmentPaymentHandler::class],
            [PayonePayolutionInvoicingPaymentHandler::class],
            [PayoneSecureInvoicePaymentHandler::class],
            [PayoneOpenInvoicePaymentHandler::class],
            [PayoneBancontactPaymentHandler::class],
            [PayoneRatepayDebitPaymentHandler::class],
            [PayoneRatepayInstallmentPaymentHandler::class],
            [PayoneRatepayInvoicingPaymentHandler::class],
        ];
    }

    public function getInvalidPaymentHandler(): array
    {
        return [
            [PayoneDebitPaymentHandler::class],
            [PayoneCreditCardPaymentHandler::class],
            [PayonePaypalPaymentHandler::class],
            // ...
        ];
    }
}
