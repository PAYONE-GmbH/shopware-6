<?php

declare(strict_types=1);

namespace PayonePayment\Components\CapturePaymentHandler;

use DateTime;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class CapturePaymentHandler implements CapturePaymentHandlerInterface
{
    /** @var CaptureRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $repository;

    public function __construct(
        CaptureRequestFactory $requestFactory,
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
     * TOOD: only paypal and creditcard is tested.
     */
    public function captureTransaction(OrderTransactionEntity $orderTransaction, Context $context): void
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
        $customFields[CustomFieldInstaller::TRANSACTION_STATE]      = 'captured';

        $data = [
            'id'           => $orderTransaction->getId(),
            'customFields' => $customFields,
        ];

        $this->repository->update([$data], $context);
    }
}
