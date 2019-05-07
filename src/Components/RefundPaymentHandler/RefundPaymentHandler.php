<?php

declare(strict_types=1);

namespace PayonePayment\Components\RefundPaymentHandler;

use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Refund\RefundRequest;
use PayonePayment\Payone\Request\RequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class RefundPaymentHandler implements RefundPaymentHandlerInterface
{
    /** @var RequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $repository;

    public function __construct(
        RequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $repository
    ) {
        $this->requestFactory = $requestFactory;
        $this->client         = $client;
        $this->repository     = $repository;
    }

    public function refundTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
    {
        $paymentTransaction = PaymentTransactionStruct::fromOrderTransaction($orderTransaction);

        $request = $this->requestFactory->generateRequest(
            $paymentTransaction,
            $context,
            RefundRequest::class
        );

        $response = $this->client->request($request);

        if (empty($response['status']) || $response['status'] === 'ERROR') {
            throw new InvalidOrderException($orderTransaction->getOrderId());
        }

        $customFields = $orderTransaction->getCustomFields() ?? [];

        ++$customFields[CustomFieldInstaller::SEQUENCE_NUMBER];

        $data = [
            'id'           => $orderTransaction->getId(),
            'customFields' => $customFields,
        ];

        $this->repository->update([$data], $context);
    }
}
