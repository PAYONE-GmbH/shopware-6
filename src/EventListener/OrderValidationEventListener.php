<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use DateTime;
use DateTimeInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\PaymentMethod\PayonePayolutionInstallment;
use PayonePayment\PaymentMethod\PayonePayolutionInvoicing;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationEventListener implements EventSubscriberInterface
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

        if ($this->isPayonePayolutionPaymentMethod($context)) {
            $event->getDefinition()->add(
                'payolutionConsent',
                new NotBlank()
            );

            $event->getDefinition()->add(
                'payolutionBirthday',
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

    private function isPayonePayolutionPaymentMethod(SalesChannelContext $context): bool
    {
        $paymentMethods = [
            PayonePayolutionInstallment::UUID,
            PayonePayolutionInvoicing::UUID,
        ];

        return in_array($context->getPaymentMethod()->getId(), $paymentMethods, true);
    }
}
