<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler;

use Exception;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\FinancialTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
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

    /** @var RequestParameterFactory */
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

    /** @var CurrencyPrecisionInterface */
    protected $currencyPrecision;

    public function handleRequest(ParameterBag $parameterBag, string $action, Context $context): array
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

        /** @var PaymentMethodEntity $paymentMethod */
        $paymentMethod            = $transaction->getPaymentMethod();
        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($transaction, $transaction->getOrder());

        return $this->executeRequest(
            $this->requestFactory->getRequestParameter(
                new FinancialTransactionStruct(
                    $this->paymentTransaction,
                    $context,
                    $parameterBag,
                    $paymentMethod->getHandlerIdentifier(),
                    $action
                )
            )
        );
    }

    abstract protected function getAmountCustomField(): string;

    abstract protected function getQuantityCustomField(): string;

    abstract protected function getAllowCustomField(): string;

    protected function executeRequest(array $request): array
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
            $currentCaptureAmount  = $this->currencyPrecision->getRoundedTotalAmount($captureAmount, $currency);
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

        return $this->transactionRepository->search($criteria, $this->context)->first();
    }
}
