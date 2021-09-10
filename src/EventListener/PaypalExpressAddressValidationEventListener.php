<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Optional;

class PaypalExpressAddressValidationEventListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
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
        /** @var null|SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->get('sw-sales-channel-context');

        if (null === $route || $route !== 'frontend.checkout.confirm.page') {
            return;
        }

        if (null === $salesChannelContext) {
            return;
        }

        if ($salesChannelContext->getPaymentMethod()->getHandlerIdentifier() !== PayonePaypalExpressPaymentHandler::class) {
            return;
        }

        $this->markAddressFieldsAsOptional($event->getDefinition());
    }

    public function disableAdditionalAddressValidation(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        if (\mb_strpos($request->getPathInfo(), '/payone/paypal/redirect-handler') === false) {
            return;
        }

        $this->markAddressFieldsAsOptional($event->getDefinition());
    }

    public function disableBirthdayValidation(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        if (\mb_strpos($request->getPathInfo(), '/payone/paypal/redirect-handler') === false) {
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
