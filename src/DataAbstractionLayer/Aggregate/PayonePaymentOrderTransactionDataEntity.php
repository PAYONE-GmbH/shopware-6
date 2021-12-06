<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Aggregate;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentOrderTransactionDataEntity extends Entity
{
    use EntityIdTrait;
    /** @var string */
    protected $transactionId;
    /** @var string */
    protected $orderTransactionId;
    /** @var OrderTransactionEntity */
    protected $orderTransaction;
    /** @var null|array */
    protected $transactionData;
    /** @var null|int */
    protected $sequenceNumber;
    /** @var null|string */
    protected $transactionState;
    /** @var null|string */
    protected $userId;
    /** @var null|string */
    protected $lastRequest;
    /** @var null|bool */
    protected $allowCapture;
    /** @var null|int */
    protected $capturedAmount;
    /** @var null|bool */
    protected $allowRefund;
    /** @var null|int */
    protected $refundedAmount;
    /** @var null|string */
    protected $mandateIdentification;
    /** @var null|string */
    protected $authorizationType;
    /** @var null|string */
    protected $workOrderId;
    /** @var null|string */
    protected $clearingReference;
    /** @var null|string */
    protected $clearingType;
    /** @var null|string */
    protected $financingType;
    /** @var null|string */
    protected $captureMode;
    /** @var null|array */
    protected $clearingBankAccount;

    public function setTransactionId(string $value): void
    {
        $this->transactionId = $value;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->orderTransactionId = $orderTransactionId;
    }

    public function getOrderTransaction(): OrderTransactionEntity
    {
        return $this->orderTransaction;
    }

    public function setOrderTransaction(OrderTransactionEntity $orderTransaction): void
    {
        $this->orderTransaction = $orderTransaction;
    }

    public function setTransactionData(?array $value): void
    {
        $this->transactionData = $value;
    }

    public function getTransactionData(): ?array
    {
        return $this->transactionData;
    }

    public function setSequenceNumber(?int $value): void
    {
        $this->sequenceNumber = $value;
    }

    public function getSequenceNumber(): ?int
    {
        return $this->sequenceNumber;
    }

    public function setTransactionState(?string $value): void
    {
        $this->transactionState = $value;
    }

    public function getTransactionState(): ?string
    {
        return $this->transactionState;
    }

    public function setUserId(?string $value): void
    {
        $this->userId = $value;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setLastRequest(?string $value): void
    {
        $this->lastRequest = $value;
    }

    public function getLastRequest(): ?string
    {
        return $this->lastRequest;
    }

    public function setAllowCapture(?bool $value): void
    {
        $this->allowCapture = $value;
    }

    public function getAllowCapture(): ?bool
    {
        return $this->allowCapture;
    }

    public function setCapturedAmount(?int $value): void
    {
        $this->capturedAmount = $value;
    }

    public function getCapturedAmount(): ?int
    {
        return $this->capturedAmount;
    }

    public function setAllowRefund(?bool $value): void
    {
        $this->allowRefund = $value;
    }

    public function getAllowRefund(): ?bool
    {
        return $this->allowRefund;
    }

    public function setRefundedAmount(?int $value): void
    {
        $this->refundedAmount = $value;
    }

    public function getRefundedAmount(): ?int
    {
        return $this->refundedAmount;
    }

    public function setMandateIdentification(?string $value): void
    {
        $this->mandateIdentification = $value;
    }

    public function getMandateIdentification(): ?string
    {
        return $this->mandateIdentification;
    }

    public function setAuthorizationType(?string $value): void
    {
        $this->authorizationType = $value;
    }

    public function getAuthorizationType(): ?string
    {
        return $this->authorizationType;
    }

    public function setWorkOrderId(?string $value): void
    {
        $this->workOrderId = $value;
    }

    public function getWorkOrderId(): ?string
    {
        return $this->workOrderId;
    }

    public function setClearingReference(?string $value): void
    {
        $this->clearingReference = $value;
    }

    public function getClearingReference(): ?string
    {
        return $this->clearingReference;
    }

    public function setClearingType(?string $value): void
    {
        $this->clearingType = $value;
    }

    public function getClearingType(): ?string
    {
        return $this->clearingType;
    }

    public function setFinancingType(?string $value): void
    {
        $this->financingType = $value;
    }

    public function getFinancingType(): ?string
    {
        return $this->financingType;
    }

    public function setCaptureMode(?string $value): void
    {
        $this->captureMode = $value;
    }

    public function getCaptureMode(): ?string
    {
        return $this->captureMode;
    }

    public function setClearingBankAccount(?array $value): void
    {
        $this->clearingBankAccount = $value;
    }

    public function getClearingBankAccount(): ?array
    {
        return $this->clearingBankAccount;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                    => $this->id,
            'transactionData'       => $this->transactionData,
            'sequenceNumber'        => $this->sequenceNumber,
            'transactionState'      => $this->transactionState,
            'userId'                => $this->userId,
            'lastRequest'           => $this->lastRequest,
            'allowCapture'          => $this->allowCapture,
            'capturedAmount'        => $this->capturedAmount,
            'allowRefund'           => $this->allowRefund,
            'refundedAmount'        => $this->refundedAmount,
            'mandateIdentification' => $this->mandateIdentification,
            'authorizationType'     => $this->authorizationType,
            'workOrderId'           => $this->workOrderId,
            'clearingReference'     => $this->clearingReference,
            'clearingType'          => $this->clearingType,
            'financingType'         => $this->financingType,
            'captureMode'           => $this->captureMode,
            'clearingBankAccount'   => $this->clearingBankAccount,
        ];
    }
}
