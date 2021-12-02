<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Capture;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\DataAbstractionLayer\Aggregate\PayonePaymentOrderTransactionDataEntity;
use PayonePayment\DataAbstractionLayer\Extension\PayonePaymentOrderTransactionExtension;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureTransactionHandler extends AbstractTransactionHandler implements CaptureTransactionHandlerInterface
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
    public function capture(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        [$requestResponse, $payoneResponse] = $this->handleRequest($parameterBag, AbstractRequestParameterBuilder::REQUEST_ACTION_CAPTURE, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float) $parameterBag->get('amount'));
        $this->updateClearingBankAccountData($payoneResponse);
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $this->paymentTransaction->getCustomFields();
        $clearingType          = $payoneTransactionData->getClearingBankAccount();

        // Filter payment methods that do not allow changing transaction status at this point
        if ($clearingType !== 'vor') {
            // Update the transaction status if PAYONE capture request was approved
            $this->updateTransactionStatus($parameterBag, $context);
        }

        return $requestResponse;
    }

    protected function updateTransactionStatus(ParameterBag $parameterBag, Context $context): void
    {
        $transitionName = StateMachineTransitionActions::ACTION_PAID_PARTIALLY;

        if ($parameterBag->get('complete')) {
            $transitionName = StateMachineTransitionActions::ACTION_PAID;
        }

        $this->transactionStatusService->transitionByName(
            $context,
            $this->paymentTransaction->getOrderTransaction()->getId(),
            $transitionName,
            $parameterBag->all()
        );
    }

    protected function getAmount(OrderTransactionEntity $transaction): int
    {
        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $transaction->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        return (int) $payoneTransactionData->getCapturedAmount();
    }

    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::CAPTURED_QUANTITY;
    }

    protected function getAllowPropertyName(): string
    {
        return 'allowCapture';
    }

    /**
     * Updates transaction custom fields that contain clearing bank account data.
     * Payment methods like invoice or secure invoice get these data through the response of a capture request.
     * These clearing data is used during invoice generation.
     *
     * @param array $payoneResponse Response of the PAYONE capture request
     */
    private function updateClearingBankAccountData(array $payoneResponse): void
    {
        $currentClearingBankAccountData = [];
        /** @var PayonePaymentOrderTransactionDataEntity $payoneTransactionData */
        $payoneTransactionData = $this->paymentTransaction->getOrderTransaction()->getExtension(PayonePaymentOrderTransactionExtension::NAME);

        if (null !== $payoneTransactionData->getClearingBankAccount()) {
            $currentClearingBankAccountData = $payoneTransactionData->getClearingBankAccount();
        }
        $newClearingBankAccountData = $payoneResponse['clearing']['BankAccount'] ?? null;

        if (!empty($newClearingBankAccountData)) {
            $this->dataHandler->saveTransactionData($this->paymentTransaction, $this->context,
                [
                    'id'                  => $payoneTransactionData->getId(),
                    'clearingBankAccount' => array_merge(
                        $currentClearingBankAccountData,
                        $newClearingBankAccountData
                    ),
                ]
            );
        }
    }
}
