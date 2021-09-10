<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

class GeneralTransactionRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var EntityRepositoryInterface */
    protected $currencyRepository;

    /** @var CurrencyPrecisionInterface */
    protected $currencyPrecision;

    /** @var CartHasherInterface */
    private $cartHasher;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        CartHasherInterface $cartHasher,
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $currencyRepository,
        CurrencyPrecisionInterface $currencyPrecision
    ) {
        $this->cartHasher         = $cartHasher;
        $this->configReader       = $configReader;
        $this->currencyRepository = $currencyRepository;
        $this->currencyPrecision  = $currencyPrecision;
    }

    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        /** @var PaymentTransactionStruct $arguments */
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $requestData         = $arguments->getRequestData();
        $paymentMethod       = $arguments->getPaymentMethod();
        $currency            = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());

        $parameters = [
            'amount'      => $this->currencyPrecision->getRoundedTotalAmount($paymentTransaction->getOrder()->getAmountTotal(), $currency),
            'currency'    => $currency->getIsoCode(),
            'reference'   => $this->getReferenceNumber($paymentTransaction, true),
            'workorderid' => $this->getWorkOrderId($paymentTransaction, $requestData, $salesChannelContext),
        ];

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod],
            (string) $paymentTransaction->getOrder()->getOrderNumber()
        );

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return $arguments instanceof PaymentTransactionStruct;
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

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        ParameterBag $dataBag,
        SalesChannelContext $context
    ): ?string {
        $cartHash = $dataBag->get('carthash');

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $dataBag->get('workorder');
    }

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

            return $customFields[CustomFieldInstaller::IS_PAYONE] ?? false;
        });

        if ($transactions->count() === 0) {
            return null;
        }

        $transactions->sort(static function (OrderTransactionEntity $a, OrderTransactionEntity $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $transactions->last();

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
