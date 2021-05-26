<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

abstract class AbstractRequestParameterBuilder
{
    public const REQUEST_ACTION_AUTHORIZE    = 'authorization';
    public const REQUEST_ACTION_PREAUTHORIZE = 'preauthorization';

    protected RedirectHandler $redirectHandler;
    protected EntityRepositoryInterface $currencyRepository;
    protected ConfigReaderInterface $configReader;

    public function setCommonDependencies(
        RedirectHandler $redirectHandler,
        EntityRepositoryInterface $currencyRepository,
        ConfigReaderInterface $configReader
    ): void {
        $this->redirectHandler    = $redirectHandler;
        $this->currencyRepository = $currencyRepository;
        $this->configReader       = $configReader;
    }

    abstract public function getRequestParameter(
        PaymentTransaction $paymentTransaction,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        string $paymentMethod,
        string $action = ''
    ): array;

    /**
     * Returns true if builder is meant to build parameters for the given action
     */
    abstract public function supports(string $paymentMethod, string $action = ''): bool;

    protected function getConvertedAmount(float $amount, int $precision): int
    {
        return (int) round($amount * (10 ** $precision));
    }

    protected function encodeUrl(string $url): string
    {
        return $this->redirectHandler->encode($url);
    }

    protected function getOrderCurrency(OrderEntity $order, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$order->getCurrencyId()]);

        /** @var null|CurrencyEntity $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (null === $currency) {
            throw new RuntimeException('missing order currency entity');
        }

        return $currency;
    }

    protected function addNarrativeTextIfAllowed(array &$parameters, string $salesChannelId, string $prefix, string $narrativeText = ''): void
    {
        $config = $this->configReader->read($salesChannelId);

        if ($config->get(sprintf('%sProvideNarrativeText', $prefix), false) === false) {
            return;
        }

        if (empty($narrativeText)) {
            return;
        }

        $parameters['narrative_text'] = mb_substr($narrativeText, 0, 81);
    }

    /**
     * TODO: maybe move into component (referenceNumber)
     */
    protected function getReferenceNumber(PaymentTransaction $transaction, bool $generateNew = false): string
    {
        $latestReferenceNumber = $this->getLatestReferenceNumber($transaction);

        if (!empty($latestReferenceNumber) && $generateNew === false) {
            return $latestReferenceNumber;
        }

        $order       = $transaction->getOrder();
        $orderNumber = $order->getOrderNumber();
        $suffix      = $this->getReferenceSuffix($transaction->getOrder());

        return $orderNumber . $suffix;
    }

    /**
     * TODO: refactor, just taken from old request
     */
    private function getLatestReferenceNumber(PaymentTransaction $transaction): ?string
    {
        /** @var null|OrderTransactionCollection $transactions */
        $transactions = $transaction->getOrder()->getTransactions();

        if ($transactions === null) {
            return null;
        }

        $transactions = $transactions->filter(static function (OrderTransactionEntity $transaction) {
            $paymentMethod = $transaction->getPaymentMethod();

            if ($paymentMethod === null) {
                return false;
            }

            $customFields = $paymentMethod->getCustomFields();

            if (!isset($customFields[CustomFieldInstaller::IS_PAYONE])) {
                return false;
            }

            return $customFields[CustomFieldInstaller::IS_PAYONE];
        });

        if ($transactions->count() === 0) {
            return null;
        }

        $transactions->sort(static function (OrderTransactionEntity $a, OrderTransactionEntity $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        $orderTransaction = $transactions->last();

        if ($orderTransaction === null) {
            return null;
        }

        $customFields = $orderTransaction->getCustomFields();

        if (empty($customFields[CustomFieldInstaller::TRANSACTION_DATA])) {
            return null;
        }

        $payoneTransactionData = array_pop($customFields[CustomFieldInstaller::TRANSACTION_DATA]);

        if (!isset($payoneTransactionData['request'])) {
            return null;
        }

        $request = $payoneTransactionData['request'];

        return (string) $request['reference'];
    }

    private function getReferenceSuffix(OrderEntity $order): string
    {
        $transactions = $order->getTransactions();

        if ($transactions === null || $transactions->count() <= 1) {
            return '';
        }

        return sprintf('_%d', $transactions->count());
    }
}
