<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler;

use Exception;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
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

abstract class AbstractTransactionHandler
{
    /** @var EntityRepositoryInterface */
    protected $lineItemRepository;

    /** @var CaptureRequestFactory|RefundRequestFactory */
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

        $transactionError = $this->validateTransaction();

        if (!empty($transactionError)) {
            return new JsonResponse(['status' => false, 'message' => $transactionError], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        return $this->executeRequest(
            $this->requestFactory->getFullRequest(
                $this->paymentTransaction,
                $parameterBag,
                $this->context
            )
        );
    }

    public function partialRequest(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        $this->context     = $context;
        $this->transaction = $this->getTransaction($parameterBag->get('orderTransactionId'));

        $isValidTransaction = $this->validateTransaction();

        if (!empty($isValidTransaction)) {
            return new JsonResponse(['status' => false, 'message' => $isValidTransaction], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        return $this->executeRequest(
            $this->requestFactory->getPartialRequest(
                $this->paymentTransaction,
                $parameterBag,
                $this->context
            )
        );
    }

    abstract protected function getAmountCustomField(): string;

    abstract protected function getQuantityCustomField(): string;

    abstract protected function getAllowCustomField(): string;

    protected function executeRequest(array $request): JsonResponse
    {
        $requestResult = new JsonResponse(['status' => true]);

        try {
            $response = $this->client->request($request);

            $this->dataHandler->logResponse($this->paymentTransaction, $this->context, [
                'request'  => $request,
                'response' => $response,
            ]);
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

    protected function updateTransactionData(ParameterBag $parameterBag, float $captureAmount): void
    {
        $transactionData = [];
        $currency        = $this->paymentTransaction->getOrder()->getCurrency();

        if ($parameterBag->has('complete') && $parameterBag->get('complete')) {
            $transactionData[$this->getAllowCustomField()] = false;
        }

        if ($currency !== null) {
            $captureAmount = (float) number_format($captureAmount, $currency->getDecimalPrecision(), '.', '');

            if ($captureAmount) {
                $transactionData[$this->getAmountCustomField()] = $this->paymentTransaction->getCustomFields()[$this->getAmountCustomField()] + (int) ($captureAmount * (10 ** $currency->getDecimalPrecision()));
            }
        }

        $this->dataHandler->incrementSequenceNumber($this->paymentTransaction, $this->context);
        $this->dataHandler->saveTransactionData($this->paymentTransaction, $this->context, $transactionData);
    }

    protected function saveOrderLineItemData(array $orderLines, Context $context): void
    {
        if (empty($orderLines)) {
            return;
        }

        $saveData = [];

        foreach ($orderLines as $orderLine) {
            $quantity = $orderLine['quantity'];

            if (array_key_exists('customFields', $orderLine) && !empty($orderLine['customFields']) &&
                array_key_exists($this->getQuantityCustomField(), $orderLine['customFields'])) {
                $quantity = $orderLine['quantity'] + $orderLine['customFields'][$this->getQuantityCustomField()];
            }

            $saveData[$orderLine['id']] = [$this->getQuantityCustomField() => $quantity];
        }

        $this->lineItemRepository->update([$saveData], $context);
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

    protected function isSuccessResponse(JsonResponse $requestResponse): bool
    {
        /** @var false|string $requestContent */
        $requestContent = $requestResponse->getContent();

        if ($requestContent) {
            $decodedResultContent = json_decode($requestContent, true);
        }

        return !empty($decodedResultContent) && $decodedResultContent['status'];
    }

    protected function getTransaction(string $transactionId): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('paymentMethod');

        /** @var null|OrderTransactionEntity $orderTransaction */
        return $this->transactionRepository->search($criteria, $this->context)->first();
    }
}
