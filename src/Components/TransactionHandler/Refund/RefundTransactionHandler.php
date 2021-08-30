<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Refund;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class RefundTransactionHandler extends AbstractTransactionHandler implements RefundTransactionHandlerInterface
{
    /** @var TransactionStatusServiceInterface */
    private $transactionStatusService;

    public function __construct(
        RequestParameterFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        TransactionStatusServiceInterface $transactionStatusService,
        EntityRepositoryInterface $transactionRepository,
        EntityRepositoryInterface $lineItemRepository,
        CurrencyPrecisionInterface $currencyPrecision
    ) {
        $this->requestFactory           = $requestFactory;
        $this->client                   = $client;
        $this->dataHandler              = $dataHandler;
        $this->transactionStatusService = $transactionStatusService;
        $this->transactionRepository    = $transactionRepository;
        $this->lineItemRepository       = $lineItemRepository;
        $this->currencyPrecision        = $currencyPrecision;
    }

    /**
     * {@inheritdoc}
     */
    public function refund(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        [$requestResponse,] = $this->handleRequest($parameterBag, AbstractRequestParameterBuilder::REQUEST_ACTION_REFUND, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float) $parameterBag->get('amount'));
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        $transitionName = StateMachineTransitionActions::ACTION_REFUND_PARTIALLY;

        if ($parameterBag->get('complete')) {
            $transitionName = StateMachineTransitionActions::ACTION_REFUND;
        }

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            $transitionName,
            $parameterBag->all()
        );

        return $requestResponse;
    }

    protected function getAmountCustomField(): string
    {
        return CustomFieldInstaller::REFUNDED_AMOUNT;
    }

    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::REFUNDED_QUANTITY;
    }

    protected function getAllowCustomField(): string
    {
        return CustomFieldInstaller::ALLOW_REFUND;
    }
}
