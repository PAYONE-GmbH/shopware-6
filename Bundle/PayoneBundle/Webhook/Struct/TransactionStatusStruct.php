<?php

declare(strict_types=1);

namespace PayonePayment\Bundle\PayoneBundle\Webhook\Struct;

class TransactionStatusStruct extends AbstractWebhookStruct
{
    public $txId;

    public $reference;

    public $sequenceNumber;

    public $price;

    public $receivable;

    public $balance;

    public $failedCause;

    public $errorCode;

    public $reasonCode;

    public function __construct(array $data)
    {
        $this->fromArray($data);
    }
}
