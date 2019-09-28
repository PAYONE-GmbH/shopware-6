<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\DataAbstractionLayer\Entity\Mandate\PayonePaymentMandateEntity;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Storefront\Struct\CheckoutFinishPaymentData;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutFinishEventListener implements EventSubscriberInterface
{
    /** @var EntityRepositoryInterface */
    private $mandateRepository;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    public function __construct(
        EntityRepositoryInterface $mandateRepository,
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->mandateRepository          = $mandateRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $context             = $salesChannelContext->getContext();

        if (!$this->isPayonePayment($salesChannelContext->getPaymentMethod())) {
            return;
        }

        $mandateIdentification = $this->getMandateIdentification(
            $event->getPage()->getOrder(),
            $context
        );

        if (null === $mandateIdentification) {
            return;
        }

        if (!$this->hasDirectDebitPayment($mandateIdentification)) {
            return;
        }

        $payoneData = new CheckoutFinishPaymentData();

        $payoneData->assign([
            'mandate' => $this->getMandate($mandateIdentification, $context),
        ]);

        $event->getPage()->addExtension(CheckoutFinishPaymentData::EXTENSION_NAME, $payoneData);
    }

    protected function hasDirectDebitPayment(?string $mandateIdentification): bool
    {
        return null !== $mandateIdentification;
    }

    private function isPayonePayment(PaymentMethodEntity $paymentMethod): bool
    {
        $customFields = $paymentMethod->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::IS_PAYONE])) {
            return false;
        }

        if (!$customFields[CustomFieldInstaller::IS_PAYONE]) {
            return false;
        }

        return true;
    }

    private function getMandate(string $mandateIdentification, Context $context): ?PayonePaymentMandateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('identification', $mandateIdentification));

        /** @var null|PayonePaymentMandateEntity $mandate */
        return $this->mandateRepository->search($criteria, $context)->first();
    }

    private function getMandateIdentification(OrderEntity $order, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $order->getId()));

        /** @var OrderTransactionEntity[] $transactions */
        $transactions = $this->orderTransactionRepository->search($criteria, $context);

        foreach ($transactions as $transaction) {
            $customFields = $transaction->getCustomFields();

            if (!empty($customFields[CustomFieldInstaller::MANDATE_IDENTIFICATION])) {
                return $customFields[CustomFieldInstaller::MANDATE_IDENTIFICATION];
            }
        }

        return null;
    }
}
