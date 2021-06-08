<?php

declare(strict_types=1);

namespace PayonePayment\Components\TransactionHandler\Capture;

use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionHandler\AbstractTransactionHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
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
        CaptureRequestFactory $requestFactory,
        PayoneClientInterface $client,
        TransactionDataHandlerInterface $dataHandler,
        TransactionStatusServiceInterface $transactionStatusService,
        EntityRepositoryInterface $transactionRepository,
        EntityRepositoryInterface $lineItemRepository
    ) {
        $this->requestFactory           = $requestFactory;
        $this->client                   = $client;
        $this->dataHandler              = $dataHandler;
        $this->transactionStatusService = $transactionStatusService;
        $this->transactionRepository    = $transactionRepository;
        $this->lineItemRepository       = $lineItemRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function capture(ParameterBag $parameterBag, Context $context): JsonResponse
    {
        [$requestResponse, $payoneResponse] = $this->handleRequest($parameterBag, $context);

        if (!$this->isSuccessResponse($requestResponse)) {
            return $requestResponse;
        }

        $this->updateTransactionData($parameterBag, (float) $parameterBag->get('amount'));
        $this->updateClearingBankAccountData($payoneResponse);
        $this->saveOrderLineItemData($parameterBag->get('orderLines', []), $context);

        $customFields = $this->paymentTransaction->getCustomFields();
        $clearingType = strtolower($customFields[$this->getClearingTypeCustomField()] ?? '');

        // Filter payment methods that do not allow changing transaction status at this point
        if (!in_array($clearingType, ['vor'], true)) {
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
            $transitionName
        );
    }

    protected function getAmountCustomField(): string
    {
        return CustomFieldInstaller::CAPTURED_AMOUNT;
    }

    protected function getQuantityCustomField(): string
    {
        return CustomFieldInstaller::CAPTURED_QUANTITY;
    }

    protected function getAllowCustomField(): string
    {
        return CustomFieldInstaller::ALLOW_CAPTURE;
    }

    protected function getClearingTypeCustomField(): string
    {
        return CustomFieldInstaller::CLEARING_TYPE;
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
        $customFields = $this->paymentTransaction->getCustomFields();

        if (array_key_exists(CustomFieldInstaller::CLEARING_BANK_ACCOUNT, $customFields) === false) {
            $customFields[CustomFieldInstaller::CLEARING_BANK_ACCOUNT] = [];
        }

        $currentClearingBankAccountData = $customFields[CustomFieldInstaller::CLEARING_BANK_ACCOUNT];
        $newClearingBankAccountData     = $payoneResponse['clearing']['BankAccount'] ?? null;

        if (!empty($newClearingBankAccountData)) {
            $this->dataHandler->saveTransactionData($this->paymentTransaction, $this->context, [
                CustomFieldInstaller::CLEARING_BANK_ACCOUNT => array_merge(
                    $currentClearingBankAccountData,
                    $newClearingBankAccountData
                ),
            ]);
        }
    }
}
