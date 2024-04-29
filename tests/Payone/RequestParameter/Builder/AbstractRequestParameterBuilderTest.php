<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\RequestConstants;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

/**
 * @covers \PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder
 */
class AbstractRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfRuntimeExceptionIsThrownOnMissingPhone(): void
    {
        // this test should never be required in a real-case because the payment-handler should hold a constraint for validation the required phone-number
        $handler = $this->getApplyPhoneBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing phone number');
        $handler->getRequestParameter($struct);
    }

    public function testIfPhoneNumberGotPickedFromRequest(): void
    {
        $handler = $this->getApplyPhoneBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::PHONE => '123456789',
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $billingAddressId = $struct->getPaymentTransaction()->getOrder()->getBillingAddressId();

        /** @var EntityRepository $repo */
        $addressRepository = $this->getContainer()->get('order_address.repository');
        $addressRepository->update([[
            'id' => $billingAddressId,
            'phoneNumber' => null, // make sure, that order-address-phoneNumber is not set
        ]], Context::createDefaultContext());

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('telephonenumber', $result);
        static::assertEquals('123456789', $result['telephonenumber']);
    }

    public function testIfPhoneNumberGotPickedFromOrderAddress(): void
    {
        $handler = $this->getApplyPhoneBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $billingAddressId = $struct->getPaymentTransaction()->getOrder()->getBillingAddressId();

        /** @var EntityRepository $repo */
        $addressRepository = $this->getContainer()->get('order_address.repository');
        $addressRepository->update([[
            'id' => $billingAddressId,
            'phoneNumber' => '9999999',
        ]], Context::createDefaultContext());

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('telephonenumber', $result);
        static::assertEquals('9999999', $result['telephonenumber']);
    }

    public function testIfPhoneNumberFromRequestIsMorePrioritizedThanPhoneNumberFromAddress(): void
    {
        $handler = $this->getApplyPhoneBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::PHONE => '123456789',
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $billingAddressId = $struct->getPaymentTransaction()->getOrder()->getBillingAddressId();

        /** @var EntityRepository $repo */
        $addressRepository = $this->getContainer()->get('order_address.repository');
        $addressRepository->update([[
            'id' => $billingAddressId,
            'phoneNumber' => '9999999',
        ]], Context::createDefaultContext());

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('telephonenumber', $result);
        static::assertEquals('123456789', $result['telephonenumber']);
    }

    public function testIfRuntimeExceptionIsThrownOnMissingBirthday(): void
    {
        // this test should never be required in a real-case because the payment-handler should hold a constraint for validation the required phone-number
        $handler = $this->getApplyBirthdayBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing birthday');
        $handler->getRequestParameter($struct);
    }

    public function testIfRuntimeExceptionIsThrownOnInvalidBirthday(): void
    {
        // this test should never be required in a real-case because the payment-handler should hold a constraint for validation the required phone-number
        $handler = $this->getApplyBirthdayBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::BIRTHDAY => 'invalid-value',
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing birthday');
        $handler->getRequestParameter($struct);
    }

    public function testIfBirthdayGotPickedFromRequest(): void
    {
        $handler = $this->getApplyBirthdayBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::BIRTHDAY => '2000-04-20',
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $customerId = $struct->getPaymentTransaction()->getOrder()->getOrderCustomer()->getCustomerId();

        /** @var EntityRepository $repo */
        $customerRepository = $this->getContainer()->get('customer.repository');
        $customerRepository->update([[
            'id' => $customerId,
            'birthday' => null, // make sure, that customer birthday is not set
        ]], Context::createDefaultContext());

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('birthday', $result);
        static::assertEquals('20000420', $result['birthday']);
    }

    public function testIfBirthdayGotPickedFromCustomer(): void
    {
        $handler = $this->getApplyBirthdayBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $struct->getPaymentTransaction()
            ->getOrder()
            ->getOrderCustomer()
            ->getCustomer()
            ->setBirthday(\DateTime::createFromFormat('Y-m-d', '1954-01-01'));

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('birthday', $result);
        static::assertEquals('19540101', $result['birthday']);
    }

    public function testIfBirthdayFromRequestIsMorePrioritizedThanBirthdayFromCustomer(): void
    {
        $handler = $this->getApplyBirthdayBuilder();

        $struct = $this->getPaymentTransactionStruct(
            new RequestDataBag([
                RequestConstants::BIRTHDAY => '2000-04-20',
            ]),
            PayoneSecureInvoicePaymentHandler::class,
            ''
        );

        $customerId = $struct->getPaymentTransaction()->getOrder()->getOrderCustomer()->getCustomerId();

        $struct->getPaymentTransaction()
            ->getOrder()
            ->getOrderCustomer()
            ->getCustomer()
            ->setBirthday(\DateTime::createFromFormat('Y-m-d', '1954-01-01'));

        $result = $handler->getRequestParameter($struct);
        static::assertArrayHasKey('birthday', $result);
        static::assertEquals('20000420', $result['birthday']);
    }

    private function getApplyBirthdayBuilder(): AbstractRequestParameterBuilder
    {
        return new class($this->getContainer()->get(RequestBuilderServiceAccessor::class)) extends AbstractRequestParameterBuilder {
            /**
             * @param PaymentTransactionStruct $arguments
             */
            public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
            {
                $parameters = [];

                $this->applyBirthdayParameter(
                    $arguments->getPaymentTransaction()->getOrder(),
                    $parameters,
                    $arguments->getRequestData(),
                    $arguments->getSalesChannelContext()->getContext()
                );

                return $parameters;
            }

            public function supports(AbstractRequestParameterStruct $arguments): bool
            {
                return $arguments instanceof PaymentTransactionStruct;
            }
        };
    }

    private function getApplyPhoneBuilder(): AbstractRequestParameterBuilder
    {
        return new class($this->getContainer()->get(RequestBuilderServiceAccessor::class)) extends AbstractRequestParameterBuilder {
            /**
             * @param PaymentTransactionStruct $arguments
             */
            public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
            {
                $parameters = [];

                $this->applyPhoneParameter(
                    $arguments->getPaymentTransaction()->getOrder(),
                    $parameters,
                    $arguments->getRequestData(),
                    $arguments->getSalesChannelContext()->getContext()
                );

                return $parameters;
            }

            public function supports(AbstractRequestParameterStruct $arguments): bool
            {
                return $arguments instanceof PaymentTransactionStruct;
            }
        };
    }
}
