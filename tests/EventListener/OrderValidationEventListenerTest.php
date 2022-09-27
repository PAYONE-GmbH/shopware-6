<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\PlatformRequest;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @covers \PayonePayment\EventListener\OrderValidationEventListener
 */
class OrderValidationEventListenerTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItAddsValidationDefinitions(): void
    {
        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $dataBag = new RequestDataBag();

        $event = new BuildValidationEvent(new DataValidationDefinition(), $dataBag, $salesChannelContext->getContext());

        $requestStack = $this->getRequestStack($dataBag, [
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $salesChannelContext,
        ]);

        $definitions = [
            'iban' => [new NotBlank(), new Iban()],
            'birthday' => [new NotBlank(), new Birthday(['value' => (new \DateTime())->modify('-18 years')->setTime(0, 0)])],
            'sub' => new DataValidationDefinition(),
        ];

        $paymentHandler = $this->createMock(AbstractPayonePaymentHandler::class);
        $paymentHandler->expects(static::once())->method('getValidationDefinitions')->willReturn($definitions);

        $paymentHandlerRegistry = $this->createMock(PaymentHandlerRegistry::class);
        $paymentHandlerRegistry->expects(static::once())->method('getHandler')->willReturn($paymentHandler);

        $listener = new OrderValidationEventListener($requestStack, $paymentHandlerRegistry);
        $listener->validateOrderData($event);

        $properties = $event->getDefinition()->getProperties();
        $subDefinitions = $event->getDefinition()->getSubDefinitions();

        static::assertArrayHasKey('iban', $properties);
        static::assertArrayHasKey('birthday', $properties);
        static::assertArrayHasKey('sub', $subDefinitions);
        static::assertSame($definitions['iban'], $properties['iban']);
        static::assertSame($definitions['birthday'], $properties['birthday']);
        static::assertSame($definitions['sub'], $subDefinitions['sub']);
    }
}
