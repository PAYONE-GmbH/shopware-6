<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use PayonePayment\Service\CartHasherService;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class GeneralTransactionRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        protected RequestBuilderServiceAccessor $serviceAccessor,
        protected CartHasherService $cartHasher,
        protected ConfigReaderInterface $configReader,
        protected PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $paymentTransaction  = $arguments->paymentTransaction;
        $salesChannelContext = $arguments->salesChannelContext;
        $requestData         = $arguments->requestData;
        $paymentHandler      = $arguments->paymentHandler;
        $currency            = $this->getOrderCurrency($paymentTransaction->order, $salesChannelContext->getContext());

        $paymentTransaction2 = PaymentTransaction::fromOrderTransaction(
            $paymentTransaction->orderTransaction,
            $paymentTransaction->order,
        );

        $parameters = [
            'amount'      => $this->serviceAccessor->currencyPrecision->getRoundedTotalAmount(
                $paymentTransaction->order->getAmountTotal(),
                $currency,
            ),

            'currency'    => $currency->getIsoCode(),
            'reference'   => $this->getReferenceNumber($paymentTransaction2, true),
            'workorderid' => $this->getWorkOrderId($paymentTransaction2, $requestData, $salesChannelContext),
        ];

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannel()->getId(),
            $paymentHandler->getConfigKeyPrefix(),
            (string) $paymentTransaction->order->getOrderNumber(),
        );

        return $parameters;
    }

    protected function addNarrativeTextIfAllowed(
        array &$parameters,
        string $salesChannelId,
        string $prefix,
        string $narrativeText = '',
    ): void {
        $config = $this->configReader->read($salesChannelId);

        if (false === $config->get(\sprintf('%sProvideNarrativeText', $prefix), false)) {
            return;
        }

        if ('' === $narrativeText) {
            return;
        }

        $parameters['narrative_text'] = \mb_substr($narrativeText, 0, 81);
    }

    protected function getOrderCurrency(OrderEntity|null $order, Context $context): CurrencyEntity
    {
        if (null !== $order && null !== $order->getCurrency()) {
            return $order->getCurrency();
        }

        $currencyId = $context->getCurrencyId();

        if (null !== $order) {
            $currencyId = $order->getCurrencyId();
        }

        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyEntity|null $currency */
        $currency = $this->serviceAccessor->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new \RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    protected function getReferenceNumber(PaymentTransaction $transaction, bool $generateNew = false): string
    {
        $latestReferenceNumber = $this->getLatestReferenceNumber($transaction);

        if (!empty($latestReferenceNumber) && false === $generateNew) {
            return $latestReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($transaction->getOrder());

        return $orderNumber . $suffix;
    }

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        ParameterBag $dataBag,
        SalesChannelContext $context,
    ): string|null {
        $cartHash = $dataBag->get(RequestConstantsEnum::CART_HASH->value);

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $dataBag->get(RequestConstantsEnum::WORK_ORDER_ID->value);
    }

    private function getLatestReferenceNumber(PaymentTransaction $transaction): ?string
    {
        /** @var OrderTransactionCollection|null $transactions */
        $transactions = $transaction->getOrder()->getTransactions();

        if (null === $transactions) {
            return null;
        }

        /**
         * @TODO: Switch this statement to a loop to increase performance
         */
        $transactions = $transactions->filter(function (OrderTransactionEntity $transaction) {
            $paymentMethod = $transaction->getPaymentMethod();

            if (null === $paymentMethod) {
                return null;
            }

            if (!$this->paymentMethodRegistry->hasId($paymentMethod->getId())) {
                return null;
            }
        });

        if (0 === $transactions->count()) {
            return null;
        }

        $transactions->sort(
            static fn (OrderTransactionEntity $a, OrderTransactionEntity $b)
                => $a->getCreatedAt() <=> $b->getCreatedAt(),
        );

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $transactions->last();

        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $orderTransaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if (null === $transactionData || [] === $transactionData->getTransactionData()) {
            return null;
        }

        $transactionDataHistory = $transactionData->getTransactionData();
        $payoneTransactionData  = \array_pop($transactionDataHistory);

        if (!isset($payoneTransactionData['request'])) {
            return null;
        }

        $request = $payoneTransactionData['request'];

        return (string) $request['reference'];
    }

    private function getReferenceSuffix(OrderEntity $order): string
    {
        $transactions = $order->getTransactions();

        if (null === $transactions || $transactions->count() <= 1) {
            return '';
        }

        return sprintf('.%d', $transactions->count());
    }
}
