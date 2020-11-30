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

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    public function handleRequest(ParameterBag $parameterBag, Context $context)
    {
        $this->context = $context;
        $transaction   = $this->getTransaction($parameterBag->get('orderTransactionId', ''));

        if (null === $transaction) {
            return [
                new JsonResponse([
                    'status'  => false,
                    'message' => 'payone-payment.error.transaction.notFound',
                ], Response::HTTP_BAD_REQUEST),
                null,
            ];
        }

        if (null === $transaction->getOrder()) {
            return [
                new JsonResponse([
                    'status'  => false,
                    'message' => 'payone-payment.error.transaction.orderNotFound',
                ], Response::HTTP_BAD_REQUEST),
                null,
            ];
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($transaction, $transaction->getOrder());

        return $this->executeRequest(
            $this->requestFactory->getRequest(
                $this->paymentTransaction,
                $parameterBag,
                $this->context
            )
        );
    }

    abstract protected function getAmountCustomField(): string;

    abstract protected function getQuantityCustomField(): string;

    abstract protected function getAllowCustomField(): string;

    protected function executeRequest(array $request)
    {
        try {
            $response = $this->client->request($request);

            $this->dataHandler->logResponse($this->paymentTransaction, $this->context, [
                'request'  => $request,
                'response' => $response,
            ]);

            return [
                new JsonResponse(['status' => true]),
                $response,
            ];
        } catch (PayoneRequestException $exception) {
            return [
                new JsonResponse([
                    'status'  => false,
                    'message' => $exception->getResponse()['error']['ErrorMessage'],
                    'code'    => $exception->getResponse()['error']['ErrorCode'],
                ], Response::HTTP_BAD_REQUEST),
                null,
            ];
        } catch (Exception $exception) {
            return [
                new JsonResponse([
                    'status'  => false,
                    'message' => $exception->getMessage(),
                    'code'    => 0,
                ], Response::HTTP_BAD_REQUEST),
                null,
            ];
        }
    }

    protected function updateTransactionData(ParameterBag $parameterBag, float $captureAmount): void
    {
        $transactionData = [];
        $customFields    = $this->paymentTransaction->getCustomFields();
        $currency        = $this->paymentTransaction->getOrder()->getCurrency();

        if ($parameterBag->has('complete') && $parameterBag->get('complete')) {
            $transactionData[$this->getAllowCustomField()] = false;
        }

        if ($currency !== null) {
            $currentCaptureAmount  = (int) round($captureAmount * (10 ** $currency->getDecimalPrecision()));
            $alreadyCapturedAmount = $customFields[$this->getAmountCustomField()] ?? 0;

            if ($captureAmount) {
                $transactionData[$this->getAmountCustomField()] = $alreadyCapturedAmount + $currentCaptureAmount;
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

            $saveData[] = [
                'id'                            => $orderLine['id'],
                $this->getQuantityCustomField() => $quantity,
            ];
        }

        $this->lineItemRepository->update($saveData, $context);
    }

    protected function validateTransaction(OrderTransactionEntity $transaction): string
    {
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
