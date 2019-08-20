<?php

declare(strict_types=1);

namespace PayonePayment\Components\CapturePaymentHandler;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Throwable;

class CapturePaymentHandler implements CapturePaymentHandlerInterface
{
    /** @var CaptureRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    public function __construct(
        CaptureRequestFactory $requestFactory,
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
    public function captureTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransaction::fromOrderTransaction($orderTransaction);

        $request = $this->requestFactory->getRequestParameters($paymentTransaction, $context);

        try {
            $response = $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        } catch (Throwable $exception) {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $this->dataHandler->logResponse($paymentTransaction, $context, $response);
        $this->dataHandler->incrementSequenceNumber($paymentTransaction, $context);
    }
}
