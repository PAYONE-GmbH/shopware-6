<?php

declare(strict_types=1);

namespace PayonePayment\Components\PaymentHandler;

use Exception;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Payone\Request\Refund\RefundRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property TransactionDataHandler $dataHandler
 * @property PayoneClientInterface $client
 * @property OrderTransactionEntity $transaction
 * @property PaymentTransaction $paymentTransaction
 * @property Context $context
 * @property CaptureRequestFactory|RefundRequestFactory $requestFactory
 * @property EntityRepositoryInterface $transactionRepository
 * @property EntityRepositoryInterface $orderLineItemRepository
 */
abstract class AbstractPaymentHandler
{
    /** @var EntityRepositoryInterface */
    protected $orderLineItemRepository;

    /** @var CaptureRequestFactory */
    protected $requestFactory;

    /** @var PayoneClientInterface */
    protected $client;

    /** @var TransactionDataHandlerInterface */
    protected $dataHandler;

    /** @var EntityRepositoryInterface */
    protected $transactionRepository;

    /** @var Context */
    protected $context;

    /** @var OrderTransactionEntity */
    protected $transaction;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    public function fullRequest(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $this->context     = $context;
        $this->transaction = $this->getTransaction($parameterBag->get('orderTransactionId'));

        $isValidTransaction = $this->validateTransaction();

        if(!empty($isValidTransaction)) {
            return new JsonResponse(['status' => false, 'message' => $isValidTransaction], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        return $this->executeRequest(
            $this->requestFactory->getFullRequest(
                $this->paymentTransaction,
                $this->context
            )
        );
    }

    public function partialRequest(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $this->context     = $context;
        $this->transaction = $this->getTransaction($parameterBag->get('orderTransactionId'));

        $isValidTransaction = $this->validateTransaction();

        if(!empty($isValidTransaction)) {
            return new JsonResponse(['status' => false, 'message' => $isValidTransaction], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        return $this->executeRequest(
            $this->requestFactory->getPartialRequest(
                (float)$parameterBag->get('amount'),
                $this->paymentTransaction,
                $this->context
            )
        );
    }

    protected function executeRequest(array $request): JsonResponse
    {
        $requestResult = new JsonResponse(['status' => true]);

        try {
            $response = $this->client->request($request);

            $this->dataHandler->logResponse($this->paymentTransaction, $this->context, compact('request', 'response'));
        } catch (PayoneRequestException $exception) {
            $requestResult = new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getResponse()['error']['ErrorMessage'],
                    'code'    => $exception->getResponse()['error']['ErrorCode'],
                ],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Exception $exception) {
            $requestResult = new JsonResponse(
                [
                    'status'  => false,
                    'message' => $exception->getMessage(),
                    'code'    => 0,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $requestResult;
    }

    protected function postRequestHandling(float $captureAmount): void
    {
        $transactionData = [];
        $currency = $this->paymentTransaction->getOrder()->getCurrency();

        if ($currency !== null) {
            $captureAmount = (float)number_format($captureAmount, $currency->getDecimalPrecision(), '.', '');

            if ($captureAmount) {
                $transactionData[self::AMOUNT_CUSTOM_FIELD] = (int)($captureAmount * (10 ** $currency->getDecimalPrecision()));
            }
        }

        $this->dataHandler->incrementSequenceNumber($this->paymentTransaction, $this->context);
        $this->dataHandler->saveTransactionData($this->paymentTransaction, $this->context, $transactionData);
    }

    protected function orderLineHandling(array $orderLines): void
    {
        if (empty($orderLines)) {
            return;
        }

        foreach ($orderLines as $orderLine) {
            $customFieldData = [
                'id'           => $orderLine['id'],
                'customFields' => [
                    self::QUANTITY_CUSTOM_FIELD => $orderLine['quantity'],
                ],
            ];

            $this->orderLineItemRepository->update([$customFieldData], $this->context);
        }
    }

    protected function validateTransaction(): string
    {
        if (empty($this->transaction)) {
            return 'no order transaction found';
        }

        if (empty($this->transaction->getOrder())) {
            return 'no order found';
        }

        return '';
    }

    protected function isValidRequestResponse(JsonResponse $requestResponse): bool
    {
        /** @var false|string $requestContent */
        $requestContent = $requestResponse->getContent();

        if($requestContent) {
            $decodedResultContent = json_decode($requestContent, true);
        }

        return !empty($decodedResultContent) && $decodedResultContent['status'];
    }

    protected function getTransaction(string $transactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.line_item');
        $criteria->addAssociation('paymentMethod');

        /** @var null|OrderTransactionEntity $orderTransaction */
        return $this->transactionRepository->search($criteria, $this->context)->first();
    }
}
