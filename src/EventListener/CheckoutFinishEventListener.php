<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Entity\Mandate\PayonePaymentMandateEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Installer\PaymentMethodInstaller;
use PayonePayment\Storefront\Struct\CheckoutFinishPaymentData;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutFinishEventListener implements EventSubscriberInterface
{
    private readonly EntityRepository $mandateRepository;

    private readonly EntityRepository $orderTransactionRepository;

    public function __construct(
        EntityRepository $mandateRepository,
        EntityRepository $orderTransactionRepository
    ) {
        $this->mandateRepository = $mandateRepository;
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
        $context = $salesChannelContext->getContext();

        if (!$this->isPayonePayment($salesChannelContext->getPaymentMethod())) {
            return;
        }

        $mandateIdentification = $this->getMandateIdentification(
            $event->getPage()->getOrder(),
            $context
        );

        if ($mandateIdentification === null) {
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
        return $mandateIdentification !== null;
    }

    private function isPayonePayment(PaymentMethodEntity $paymentMethod): bool
    {
        return \in_array($paymentMethod->getId(), PaymentMethodInstaller::PAYMENT_METHOD_IDS, true);
    }

    private function getMandate(string $mandateIdentification, Context $context): ?PayonePaymentMandateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('identification', $mandateIdentification));

        return $this->mandateRepository->search($criteria, $context)->first();
    }

    private function getMandateIdentification(OrderEntity $order, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $order->getId()));

        $transactions = $this->orderTransactionRepository->search($criteria, $context);

        /** @var OrderTransactionEntity $transaction */
        foreach ($transactions as $transaction) {
            /** @var PayonePaymentOrderTransactionDataEntity|null $payoneTransactionData */
            $payoneTransactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

            if ($payoneTransactionData !== null && !empty($payoneTransactionData->getMandateIdentification())) {
                return $payoneTransactionData->getMandateIdentification();
            }
        }

        return null;
    }
}
