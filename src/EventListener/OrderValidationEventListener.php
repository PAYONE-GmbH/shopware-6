<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderValidationEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly PaymentHandlerRegistry $paymentHandlerRegistry
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.order.create' => 'validateOrderData',
        ];
    }

    public function validateOrderData(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        $salesChannelContext = $this->getSalesChannelContextFromRequest($request);
        $paymentMethodId = $salesChannelContext->getPaymentMethod()->getId();
        $paymentHandler = $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethodId);

        if ($paymentHandler instanceof AbstractPayonePaymentHandler) {
            $validationDefinitions = $paymentHandler->getValidationDefinitions($salesChannelContext);

            if ($validationDefinitions !== []) {
                $this->addSubConstraints($event->getDefinition(), $validationDefinitions);
            }
        }
    }

    private function getSalesChannelContextFromRequest(Request $request): SalesChannelContext
    {
        return $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }

    private function addSubConstraints(DataValidationDefinition $parent, array $children): void
    {
        foreach ($children as $key => $constraints) {
            if ($constraints instanceof DataValidationDefinition) {
                $parent->addSub($key, $constraints);
            } else {
                $parent->add($key, ...$constraints);
            }
        }
    }
}
