<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentOrderTransactionDataEntity extends Entity
{
    use EntityIdTrait;

    protected string $transactionId;

    protected string $orderTransactionId;

    protected ?OrderTransactionEntity $orderTransaction = null;

    protected ?array $transactionData = null;

    protected ?int $sequenceNumber = null;

    protected ?string $transactionState = null;

    protected ?string $userId = null;

    protected ?string $lastRequest = null;

    protected ?bool $allowCapture = null;

    protected ?int $capturedAmount = null;

    protected ?bool $allowRefund = null;

    protected ?int $refundedAmount = null;

    protected ?string $mandateIdentification = null;

    protected ?string $authorizationType = null;

    protected ?string $workOrderId = null;

    protected ?string $clearingReference = null;

    protected ?string $clearingType = null;

    protected ?string $financingType = null;

    protected ?string $captureMode = null;

    protected ?array $clearingBankAccount = null;

    protected ?array $additionalData = null;

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function getOrderTransaction(): ?OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function getTransactionData(): ?array
    {
        return $this->transactionData;
    }

    public function getSequenceNumber(): ?int
    {
        return $this->sequenceNumber;
    }

    public function getTransactionState(): ?string
    {
        return $this->transactionState;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getLastRequest(): ?string
    {
        return $this->lastRequest;
    }

    public function getAllowCapture(): ?bool
    {
        return $this->allowCapture;
    }

    public function getCapturedAmount(): ?int
    {
        return $this->capturedAmount;
    }

    public function getAllowRefund(): ?bool
    {
        return $this->allowRefund;
    }

    public function getRefundedAmount(): ?int
    {
        return $this->refundedAmount;
    }

    public function getMandateIdentification(): ?string
    {
        return $this->mandateIdentification;
    }

    public function getAuthorizationType(): ?string
    {
        return $this->authorizationType;
    }

    public function getWorkOrderId(): ?string
    {
        return $this->workOrderId;
    }

    public function getClearingReference(): ?string
    {
        return $this->clearingReference;
    }

    public function getClearingType(): ?string
    {
        return $this->clearingType;
    }

    public function getFinancingType(): ?string
    {
        return $this->financingType;
    }

    public function getCaptureMode(): ?string
    {
        return $this->captureMode;
    }

    public function getClearingBankAccount(): ?array
    {
        return $this->clearingBankAccount;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData ?? [];
    }
}
