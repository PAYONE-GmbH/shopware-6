<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use Exception;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;

class RefundPaymentHandler implements RefundPaymentHandlerInterface
{
    /** @var RefundRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    public function __construct(
        RefundRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        OrderTransactionStateHandler $stateHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->dataHandler    = $dataHandler;
        $this->stateHandler   = $stateHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransaction);

        $request = $this->requestFactory->getRequestParameters($paymentTransaction, $context);

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        } catch (Exception $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $data = [
            CustomFieldInstaller::TRANSACTION_STATE => 'refunded',
            CustomFieldInstaller::ALLOW_REFUND      => false,
        ];

        $this->dataHandler->logResponse($paymentTransaction, $context, $response);
        $this->dataHandler->incrementSequenceNumber($paymentTransaction, $context);
        $this->dataHandler->saveTransactionData($paymentTransaction, $context, $data);

        $this->stateHandler->refund($paymentTransaction->getOrderTransaction()->getId(), $context);
    }
}
