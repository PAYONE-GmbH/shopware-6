<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

trait PaymentTransactionParameterBuilderTestTrait
{
    use PayoneTestBehavior;

    public function testItSupportsValidPaymentTransaction(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            $this->getValidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        TestCase::assertTrue($builder->supports($struct));
    }

    public function testItNotSupportsInvalidRequestAction(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            $this->getValidPaymentHandler(),
            $this->getInvalidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsInvalidPaymentMethod(): void
    {
        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            $this->getInvalidPaymentHandler(),
            $this->getValidRequestAction()
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    public function testItNotSupportsFinancialRequest(): void
    {
        $struct = $this->getFinancialTransactionStruct(
            new RequestDataBag([]),
            $this->getValidPaymentHandler(),
            AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE
        );

        $builder = $this->getContainer()->get($this->getParameterBuilder());

        TestCase::assertFalse($builder->supports($struct));
    }

    abstract protected function getParameterBuilder(): string;

    abstract protected function getValidPaymentHandler(): string;

    abstract protected function getValidRequestAction(): string;

    protected function getInvalidPaymentHandler(): string
    {
        if ($this->getValidPaymentHandler() === PayoneDebitPaymentHandler::class) {
            return PayoneCreditCardPaymentHandler::class;
        }

        return PayoneDebitPaymentHandler::class;
    }

    protected function getInvalidRequestAction(): string
    {
        if ($this->getValidRequestAction() === AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE) {
            return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
        }

        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }
}
