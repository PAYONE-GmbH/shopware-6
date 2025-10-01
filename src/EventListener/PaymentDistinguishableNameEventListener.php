<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use Shopware\Core\Checkout\Payment\PaymentEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentDistinguishableNameEventListener implements EventSubscriberInterface
{
    /**
     * @var array<string, string>|null
     */
    private array|null $localeChain = null;

    public function __construct(
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
        private readonly EntityRepository $languageRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => 'updateDistinguishablePaymentName',
        ];
    }

    public function updateDistinguishablePaymentName(EntityLoadedEvent $event): void
    {
        $context = $event->getContext();

        if (!$context->getSource() instanceof AdminApiSource) {
            return;
        }

        /** @var PaymentMethodEntity $payment */
        foreach ($event->getEntities() as $payment) {
            // Technical name is nullable <6.7.0
            $technicalName = $payment->getTechnicalName();

            if (null === $technicalName) {
                continue;
            }

            /** @var PaymentMethodInterface $payonePaymentMethod */
            $payonePaymentMethod = $this->paymentMethodRegistry->get($technicalName);

            if (null === $payonePaymentMethod) {
                continue;
            }

            $labels = $payonePaymentMethod->getAdministrationLabel();

            if (null === $labels) {
                continue;
            }

            $localeChain = $this->getLocaleChain($context->getLanguageIdChain(), $context);

            if (\is_string($labels)) {
                $labels = [ 'en-GB' => $labels ];
            }

            $label = null;

            foreach ($localeChain as $locale) {
                if (isset($labels[$locale])) {
                    $label = $labels[$locale];

                    break;
                }
            }

            if (null === $label && !isset($labels['en-GB'])) {
                return;
            }

            $label = \str_replace(
                $payment->getName(),
                $label ?? $labels['en-GB'],
                $payment->getDistinguishableName(),
            );

            $payment->setDistinguishableName($label);
            $payment->addTranslated('distinguishableName', $label);
        }
    }

    private function getLocaleChain(array $languageIds, Context $context): array
    {
        if (null !== $this->localeChain) {
            return $this->localeChain;
        }

        $chain    = \array_fill_keys($languageIds, null);
        $entities = $this->languageRepository->search(
            (new Criteria($languageIds))->addAssociation('locale'),
            $context,
        );

        /** @var LanguageEntity $entity */
        foreach ($entities as $entity) {
            $id         = $entity->getId();
            $chain[$id] = $entity->getLocale()?->getCode();
        }

        return $this->localeChain = \array_filter($chain);
    }
}
