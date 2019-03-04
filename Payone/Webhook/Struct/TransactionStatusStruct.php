<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Struct;

class TransactionStatusStruct extends AbstractWebhookStruct
{
    /** @var int */
    public $txId;

    /** @var int */
    public $sequenceNumber;

    /** @var string */
    public $txAction;

    /** @var string */
    public $transactionStatus;

    /** @var int */
    public $aid;

    /** @var string */
    public $clearingType;

    /** @var int */
    public $txTime;

    /** @var string */
    public $reference;

    /** @var float */
    public $price;

    /** @var string */
    public $failedCause;

    /** @var string */
    public $errorCode;

    /** @var string */
    public $reasonCode;

    public function __construct(array $data)
    {
        $this->fromArray($data);
    }
}
