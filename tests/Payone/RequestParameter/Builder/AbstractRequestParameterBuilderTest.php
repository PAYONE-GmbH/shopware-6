<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AbstractRequestParameterBuilderTest extends TestCase
{
    use PayoneTestBehavior;

    public function testIfRuntimeExceptionIsThrownOnInvalidPhone(): void
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
                'payonePhone' => '123456789',
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

        $updatedEntity = $addressRepository->search(new Criteria([$billingAddressId]), Context::createDefaultContext())->first();
        static::assertInstanceOf(OrderAddressEntity::class, $updatedEntity);
        static::assertEquals('123456789', $updatedEntity->getPhoneNumber(), 'the phone-number within the order-address should be the same as in the request.');
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
                'payonePhone' => '123456789',
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

        $updatedEntity = $addressRepository->search(new Criteria([$billingAddressId]), Context::createDefaultContext())->first();
        static::assertInstanceOf(OrderAddressEntity::class, $updatedEntity);
        static::assertEquals('123456789', $updatedEntity->getPhoneNumber(), 'the phone-number within the order-address should be the same as in the request.');
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
