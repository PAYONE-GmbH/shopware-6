<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\PaymentMethod\PayonePaysafeInvoicing;
use PayonePayment\Payone\Request\CreditCardCheck\CreditCardCheckRequestFactory;
use PayonePayment\Storefront\Struct\CheckoutConfirmPaymentData;
use PayonePayment\Storefront\Struct\PaypalExpressCartData;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderValidationListener implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.order.create' => 'validateOrderData'
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

        if (!$this->isPayonePaysafeInvoicingPaymentMethod($context)) {
            return;
        }

        $event->getDefinition()->add(
            'paysafeInvoicingConsent',
            new NotBlank()
        );

        $comparisonDate = $this->getMinimumDate();

        $event->getDefinition()->add(
            'paysafeBirthdayDay',
            new NotBlank(),
            new GreaterThanOrEqual([
                'value' => (int) $comparisonDate->format('j')
            ])
        );

        $event->getDefinition()->add(
            'paysafeBirthdayMonth',
            new NotBlank(),
            new GreaterThanOrEqual([
                'value' => (int) $comparisonDate->format('n')
            ])
        );

        $event->getDefinition()->add(
            'paysafeBirthdayYear',
            new NotBlank(),
            new GreaterThanOrEqual([
                'value' => (int) $comparisonDate->format('Y')
            ])
        );
    }

    private function getMinimumDate(): DateTimeInterface
    {
        return (new DateTime())->modify('-17 years');
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
