<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\Exception\NoLongerSupportedPaymentMethodException;
use PayonePayment\PaymentMethod\NoLongerSupportedPaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWriteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class PreventNoLongerSupportedPaymentMetdhodActivation implements EventSubscriberInterface
{
    public function __construct(
        private PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWriteEvent::class => 'onEntityWrite',
        ];
    }

    public function onEntityWrite(EntityWriteEvent $event): void
    {
        $commands = $event->getCommandsForEntity(PaymentMethodDefinition::ENTITY_NAME);

        foreach ($commands as $command) {
            if (!$command instanceof UpdateCommand) {
                continue;
            }

            $payload = $command->getPayload();

            if (
                !isset($payload['active'])
                || 0 === $payload['active']
                || false === $payload['active']
            ) {
                continue;
            }

            $id = $command->getDecodedPrimaryKey()['id'];

            if (!$this->paymentMethodRegistry->hasId($id)) {
                continue;
            }

            $paymentMethod = $this->paymentMethodRegistry->getById($id);

            if ($paymentMethod instanceof NoLongerSupportedPaymentMethodInterface) {
                throw new NoLongerSupportedPaymentMethodException($paymentMethod->getName());
            }
        }
    }
}
