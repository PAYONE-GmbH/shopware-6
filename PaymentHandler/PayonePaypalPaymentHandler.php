<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequest;
use PayonePayment\Payone\Request\RequestFactory;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

class PayonePaypalPaymentHandler implements PaymentHandlerInterface
{
    /** @var RequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var Router */
    private $router;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(RequestFactory $requestFactory, PayoneClientInterface $client, EntityRepositoryInterface $transactionRepository, Router $router, LoggerInterface $logger)
    {
        $this->requestFactory        = $requestFactory;
        $this->client                = $client;
        $this->transactionRepository = $transactionRepository;
        $this->router                = $router;
        $this->logger                = $logger;
    }

    public function pay(PaymentTransactionStruct $transaction, Context $context): ?RedirectResponse
    {
        $response = $this->authorizePayment($transaction, $context);

        $this->savePayoneResponseData($transaction, $context, $response);

        if (!empty($response['Status']) && $response['Status'] === 'REDIRECT') {
            return new RedirectResponse($response['RedirectUrl']);
        }

        // TODO: For other payment Methods this path is valid, for paypal this should not happen so handle errors
        // TODO: if not an error and the transaction is actually approved, redirect to the return url and finish the payment
        // TODO: a status call should arrive

        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        $data = [
            'id'                      => $transactionId,
            'orderTransactionStateId' => Defaults::ORDER_TRANSACTION_COMPLETED,
        ];

        $this->transactionRepository->update([$data], $context);
    }

    private function authorizePayment(PaymentTransactionStruct $transaction, Context $context): array
    {
        $postFields = $this->requestFactory->generateRequest($transaction, $context, PaypalAuthorizeRequest::class);

        return $this->client->request($postFields);
    }

    /**
     * TODO: move data to a seperate entity instead of the order_transaction
     *
     * @param PaymentTransactionStruct $transaction
     * @param Context                  $context
     * @param $response
     */
    private function savePayoneResponseData(PaymentTransactionStruct $transaction, Context $context, $response): void
    {
        $criteria = new Criteria([$transaction->getTransactionId()]);
        $criteria->addAssociation('order_transaction.order');

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->transactionRepository->search($criteria, $context)->first();

        $data = [
            'id'      => $transaction->getTransactionId(),
            'details' => array_merge(
                [
                    $response['TxId'] => [
                        'TxId'   => $response['TxId'],
                        'UserId' => $response['UserId'],
                        'paypal' => [
                            'token' => $response['AddPayData']['token'],
                        ],
                    ],
                ],
                (array) $orderTransaction->getDetails()
            ),
        ];

        $this->transactionRepository->update([$data], $context);
    }
}
