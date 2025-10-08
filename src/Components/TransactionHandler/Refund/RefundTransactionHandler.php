<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Refund;

use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\DataHandler\OrderActionLogDataHandler;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Service\CurrencyPrecisionService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class RefundTransactionHandler extends AbstractTransactionHandler implements RefundTransactionHandlerInterface
{
    public function __construct(
        RequestParameterFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandler $transactionDataHandler,
        OrderActionLogDataHandler $orderActionLogDataHandler,
        private readonly TransactionStatusServiceInterface $transactionStatusService,
        EntityRepository $transactionRepository,
        EntityRepository $lineItemRepository,
        CurrencyPrecisionService $currencyPrecision,
    ) {
        $this->requestFactory            = $requestFactory;
        $this->client                    = $client;
        $this->transactionDataHandler    = $transactionDataHandler;
        $this->orderActionLogDataHandler = $orderActionLogDataHandler;
        $this->transactionRepository     = $transactionRepository;
        $this->lineItemRepository        = $lineItemRepository;
        $this->currencyPrecision         = $currencyPrecision;
    }

    #[\Override]
    public function refund(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        [ $requestResponse ] = $this->handleRequest($parameterBag, RequestActionEnum::REFUND->value, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float) $parameterBag->get('amount'));
        $this->saveOrderLineItemData($parameterBag->all('orderLines'), $context);

        $transitionName = StateMachineTransitionActions::ACTION_REFUND_PARTIALLY;

        if ($parameterBag->get('complete')) {
            $transitionName = StateMachineTransitionActions::ACTION_REFUND;
        }

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            $transitionName,
            $parameterBag->all(),
        );

        return $requestResponse;
    }

    #[\Override]
    protected function getAmount(OrderTransactionEntity $transaction): int
    {
        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        return (int) $payoneTransactionData->getRefundedAmount();
    }

    #[\Override]
    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::REFUNDED_QUANTITY;
    }

    #[\Override]
    protected function getAllowPropertyName(): string
    {
        return 'allowRefund';
    }

    #[\Override]
    protected function getAmountPropertyName(): string
    {
        return 'refundedAmount';
    }
}
