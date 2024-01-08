<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\OrderActionLog;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PayonePaymentOrderActionLogEntity extends Entity
{
    use EntityIdTrait;

    protected ?OrderEntity $order = null;

    protected string $orderId;

    protected string $transactionId;

    protected string $referenceNumber;

    protected string $request;

    protected string $response;

    protected int $amount;

    protected string $mode;

    protected string $merchantId;

    protected string $portalId;

    protected array $requestDetails;

    protected array $responseDetails;

    protected \DateTimeInterface $requestDateTime;

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getPortalId(): string
    {
        return $this->portalId;
    }

    public function getRequestDetails(): array
    {
        return $this->requestDetails;
    }

    public function getResponseDetails(): array
    {
        return $this->responseDetails;
    }

    public function getRequestDateTime(): \DateTimeInterface
    {
        return $this->requestDateTime;
    }
}
