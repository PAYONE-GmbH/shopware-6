<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\EventListener;

use PayonePayment\Provider\PayPal\PaymentHandler\ExpressPaymentHandler;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Optional;

class PaypalExpressAddressValidationEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.address.create'  => 'disableAdditionalAddressValidation',
            'framework.validation.address.update'  => 'disableAdditionalAddressValidation',
            'framework.validation.customer.create' => 'disableBirthdayValidation',
            BuildValidationEvent::class            => 'disableConfirmPageLoaderAddressValidation',
        ];
    }

    /**
     * This additional event listener is needed because of autoloading misbehaviour.
     */
    public function disableConfirmPageLoaderAddressValidation(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $route = $request->get('_route');
        /** @var SalesChannelContext|null $salesChannelContext */
        $salesChannelContext = $request->get('sw-sales-channel-context');

        if ('frontend.checkout.confirm.page' !== $route) {
            return;
        }

        if (null === $salesChannelContext) {
            return;
        }

        if (ExpressPaymentHandler::class !== $salesChannelContext->getPaymentMethod()->getHandlerIdentifier()) {
            return;
        }

        $this->markAddressFieldsAsOptional($event->getDefinition());
    }

    public function disableAdditionalAddressValidation(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        if (!\str_contains($request->getPathInfo(), '/payone/paypal/redirect-handler')) {
            return;
        }

        $this->markAddressFieldsAsOptional($event->getDefinition());
    }

    public function disableBirthdayValidation(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        if (!\str_contains($request->getPathInfo(), '/payone/paypal/redirect-handler')) {
            return;
        }

        $definition = $event->getDefinition();

        $definition->set('birthdayDay', new Optional());
        $definition->set('birthdayMonth', new Optional());
        $definition->set('birthdayYear', new Optional());
    }

    private function markAddressFieldsAsOptional(DataValidationDefinition $definition): void
    {
        $definition->set('additionalAddressLine1', new Optional());
        $definition->set('additionalAddressLine2', new Optional());
        $definition->set('phoneNumber', new Optional());
    }
}
