<?php

declare(strict_types = 1);

namespace PayonePayment\Components\CapturePaymentHandler;

use Exception;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class CapturePaymentHandler implements CapturePaymentHandlerInterface
{
    /** @var CaptureRequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var TransactionDataHandlerInterface */
    private $dataHandler;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var EntityRepositoryInterface */
    private $orderLineItemRepository;

    /** @var Context */
    protected $context;

    /** @var OrderTransactionEntity */
    protected $transaction;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    public function __construct(
        CaptureRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        EntityRepositoryInterface $transactionRepository,
        EntityRepositoryInterface $orderLineItemRepository
    ) {
        $this->requestFactory          = $requestFactory;
        $this->client                  = $client;
        $this->dataHandler             = $dataHandler;
        $this->transactionRepository   = $transactionRepository;
        $this->orderLineItemRepository = $orderLineItemRepository;
    }

    public function fullCapture(string $transactionId, Context $context): JsonResponse
    {
        $this->context     = $context;
        $this->transaction = $this->getTransaction($transactionId);

        if (empty($this->transaction)) {
            return new JsonResponse(
                ['status' => false, 'message' => 'no order transaction found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (empty($this->transaction->getOrder())) {
            return new JsonResponse(['status' => false, 'message' => 'no order found'], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        $requestResponse =  $this->executeRequest(
            $this->requestFactory->getFullRequest(
                $this->paymentTransaction,
                $this->context
            )
        );

        if(!$this->isValidRequestResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->postRequestHandling($this->transaction->getAmount()->getTotalPrice());

        return $requestResponse;
    }

    public function partialCapture(
        ParameterBag $parameterBag,
        Context $context
    ): JsonResponse {
        $this->context     = $context;
        $this->transaction = $this->getTransaction($parameterBag->get('orderTransactionId'));

        if (empty($this->transaction)) {
            return new JsonResponse(
                ['status' => false, 'message' => 'no order transaction found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (empty($this->transaction->getOrder())) {
            return new JsonResponse(['status' => false, 'message' => 'no order found'], Response::HTTP_NOT_FOUND);
        }

        $this->paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        $requestResponse = $this->executeRequest(
            $this->requestFactory->getPartialRequest(
                (float)$parameterBag->get('captureAmount'),
                $this->paymentTransaction,
                $this->context
            )
        );

        if(!$this->isValidRequestResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->postRequestHandling((float)$parameterBag->get('captureAmount'));
        $this->orderLineHandling($parameterBag->get('orderLines'));

        return $requestResponse;
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

    protected function executeRequest(array $request): JsonResponse
    {
        $requestResult      = new JsonResponse(['status' => true]);
        $paymentTransaction = PaymentTransaction::fromOrderTransaction($this->transaction);

        try {
            $response = $this->client->request($request);
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

        $this->dataHandler->logResponse($paymentTransaction, $this->context, compact('request', 'response'));

        return $requestResult;
    }

    protected function postRequestHandling(float $captureAmount): void
    {
        $currency = $this->paymentTransaction->getOrder()->getCurrency();

        if (!empty($currency)) {
            $data = [CustomFieldInstaller::CAPTURED_AMOUNT => (int)($captureAmount * (10 ** $currency->getDecimalPrecision()))];
        }

        $this->dataHandler->incrementSequenceNumber($this->paymentTransaction, $this->context);
        $this->dataHandler->saveTransactionData($this->paymentTransaction, $this->context, !empty($data) ? $data : []);
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
                    CustomFieldInstaller::CAPTURED_QUANTITY => $orderLine['quantity'],
                ],
            ];

            $this->orderLineItemRepository->update([$customFieldData], $this->context);
        }
    }

    protected function isValidRequestResponse(JsonResponse $requestResponse): bool
    {
        /** @var false|string $requestContent */
        $requestContent = $requestResponse->getContent();

        if($requestContent) {
            $decodedResultContent = json_decode($requestContent, true);
        }

        return !(empty($decodedResultContent) || $decodedResultContent['status']);
    }
}
