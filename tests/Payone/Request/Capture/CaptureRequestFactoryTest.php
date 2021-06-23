<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Capture;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydrator;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestParameterFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureRequestFactoryTest extends TestCase
{
    use RequestParameterFactoryTestTrait;

    public function testCorrectFullCaptureRequestParameters(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $factory = $this->getRequestParameterFactory($salesChannelContext);

        $request = $factory->getRequestParameter(
            new FinancialTransactionStruct(
                $this->getPaymentTransaction(PayoneCreditCardPaymentHandler::class),
                $salesChannelContext->getContext(),
                new RequestDataBag(['amount' => 100]),
                PayoneCreditCardPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
            )
        );

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

        $salesChannelContext = $this->getSalesChannelContext();

        $factory            = $this->getRequestParameterFactory($salesChannelContext);
        $paymentTransaction = $this->getPaymentTransaction(PayonePayolutionInstallmentPaymentHandler::class);
        $paymentTransaction->getOrder()->setLineItems($this->getLineItem(2));

        $request = $factory->getRequestParameter(
            new FinancialTransactionStruct(
                $paymentTransaction,
                $salesChannelContext->getContext(),
                $paramterBag,
                PayonePayolutionInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
            )
        );

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

        $salesChannelContext = $this->getSalesChannelContext();

        $factory            = $this->getRequestParameterFactory($salesChannelContext);
        $paymentTransaction = $this->getPaymentTransaction(PayonePayolutionInstallmentPaymentHandler::class);
        $paymentTransaction->getOrder()->setLineItems($this->getLineItem(1));

        $request = $factory->getRequestParameter(
            new FinancialTransactionStruct(
                $paymentTransaction,
                $salesChannelContext->getContext(),
                $paramterBag,
                PayonePayolutionInstallmentPaymentHandler::class,
                AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
            )
        );

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
}
