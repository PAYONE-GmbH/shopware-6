<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Refund;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\PaymentHandler\PaymentHandlerGroups;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\System\Currency\CurrencyEntity;

class RefundRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    public function __construct(private readonly CurrencyPrecisionInterface $currencyPrecision)
    {
    }

    /**
     * @param FinancialTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $totalAmount = $arguments->getRequestData()->get('amount');
        $order = $arguments->getPaymentTransaction()->getOrder();
        /** @var PayonePaymentOrderTransactionDataEntity|null $transactionData */
        $transactionData = $arguments->getPaymentTransaction()->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if ($transactionData === null) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($totalAmount === null) {
            $totalAmount = $order->getAmountTotal();
        }

        if (empty($transactionData->getTransactionId())) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($transactionData->getSequenceNumber() === null) {
            throw $this->orderNotFoundException($order->getId());
        }

        if ($transactionData->getSequenceNumber() < 0) {
            throw $this->orderNotFoundException($order->getId());
        }

        //TODO: fix set refunded amount

        /** @var CurrencyEntity $currency */
        $currency = $order->getCurrency();

        $parameters = [
            'request' => self::REQUEST_ACTION_DEBIT,
            'txid' => $transactionData->getTransactionId(),
            'sequencenumber' => $transactionData->getSequenceNumber() + 1,
            'amount' => -1 * $this->currencyPrecision->getRoundedTotalAmount((float) $totalAmount, $currency),
            'currency' => $currency->getIsoCode(),
        ];

        if ($arguments->getPaymentMethod() === PayoneDebitPaymentHandler::class) {
            $transactions = $transactionData->getTransactionData();

            if ($transactions) {
                $firstTransaction = reset($transactions);

                if (\array_key_exists('request', $firstTransaction) && \array_key_exists('iban', $firstTransaction['request'])) {
                    $parameters['iban'] = $firstTransaction['request']['iban'];
                }
            }
        }

        if (\in_array($arguments->getPaymentMethod(), PaymentHandlerGroups::RATEPAY, true)) {
            $parameters['settleaccount'] = 'yes';
            $parameters['add_paydata[shop_id]'] = $transactionData->getAdditionalData()['used_ratepay_shop_id'] ?? null;
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof FinancialTransactionStruct)) {
            return false;
        }

        return $arguments->getAction() === self::REQUEST_ACTION_REFUND;
    }
}
