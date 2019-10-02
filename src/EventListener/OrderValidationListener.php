<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use DateTime;
use DateTimeInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\PaymentMethod\PayonePaysafeInvoicing;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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

        if (null === $request) {
            return;
        }

        // TODO: can be removed when https://github.com/shopware/platform/pull/226 is merged
        $context = $this->getContextFromRequest($request);

        if ($this->isPayonePaysafeInvoicingPaymentMethod($context)) {
            $event->getDefinition()->add(
                'paysafeInvoicingConsent',
                new NotBlank()
            );

            $event->getDefinition()->add(
                'paysafeInvoicingBirthday',
                new Birthday(['value' => $this->getMinimumDate()])
            );
        }
    }

    private function getMinimumDate(): DateTimeInterface
    {
        return (new DateTime())->modify('-18 years')->setTime(0, 0);
    }

    private function getContextFromRequest(Request $request): SalesChannelContext
    {
        return $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }

    private function isPayonePaysafeInvoicingPaymentMethod(SalesChannelContext $context): bool
    {
        return $context->getPaymentMethod()->getId() === PayonePaysafeInvoicing::UUID;
    }
}
