<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Payone\RequestParameter\Struct\Traits\DeterminationTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\TransactionTrait;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;

class FinancialTransactionStruct extends Struct
{
    use DeterminationTrait;
    use TransactionTrait;

    /** @var Context */
    protected $context;

    /** @var array */
    protected $customFields;

    /** @var null|float */
    protected $totalAmount;

    /** @var bool */
    protected $completed = false;

    public function __construct(
        PaymentTransaction $paymentTransaction,
        Context $context,
        array $customFields,
        ?float $totalAmount = null,
        string $paymentMethod,
        string $action,
        bool $completed = false
    )
    {
        $this->paymentTransaction = $paymentTransaction;
        $this->context            = $context;
        $this->customFields       = $customFields;
        $this->totalAmount        = $totalAmount;
        $this->paymentMethod = $paymentMethod;
        $this->action = $action;
        $this->completed          = $completed;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }
}
