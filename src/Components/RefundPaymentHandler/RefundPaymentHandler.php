<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class RefundPaymentHandler implements RefundPaymentHandlerInterface
{
    /** @var RefundRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $repository;

    public function __construct(
        RefundRequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $repository
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->repository     = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * TODO: Sofort needs additional fields when refunding a transaction. It might be nessessary to have a refund transaction
     * TODO: request per payment method.
     * TODO: Sofort Error: IBAN not valid. Please verify your data.
     */
    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromOrderTransaction($orderTransaction);

        $request = $this->requestFactory->getRequestParameters(
            $paymentTransaction,
            $context
        );

        $response = $this->client->request($request);

        if (empty($response['status']) || $response['status'] === 'ERROR') {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $customFields = $orderTransaction->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];

        $key = (new DateTime())->format(DATE_ATOM);

        $customFields[CustomFieldInstaller::TRANSACTION_DATA][$key] = $response;
        $customFields[CustomFieldInstaller::TRANSACTION_STATE]      = 'refunded';

        $data = [
            'id'           => $orderTransaction->getId(),
            'customFields' => $customFields,
        ];

        $this->repository->update([$data], $context);
    }
}
