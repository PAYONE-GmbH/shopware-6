<?php

namespace PayonePayment\Test\PayoneBundle\Webhook\Struct;

use PayonePayment\Bundle\PayoneBundle\Webhook\Struct\TransactionStatusStruct;
use PHPUnit\Framework\TestCase;

class AbstractWebhookStructTest extends TestCase
{
    public function testDeserializeWillUseLowercaseForPropertyNames(): void
    {
        $data = [
            'txid' => '1234', //Property is $txId!
        ];

        $testStruct = new TransactionStatusStruct($data);

        $this->assertEquals('1234', $testStruct->txId, 'Expected txId to be set eventhough it was provided in lowercase!');
    }

    public function testDeserializeWillSetAllProperties(): void
    {
        $data = [
            'txid' => '1234',
            'reference' => 'MyReference',
            'sequencenumber' => 'MySequenceNumber',
            'price' => 100,
            'receivable' => 150,
            'balance' => 200,
            'failedcause' => 'MyFailedCause',
            'errorcode' => 'MyErrorCode',
            'reasoncode' => 'MyReasonCode'
        ];

        $testStruct = new TransactionStatusStruct($data);

        $result = array_change_key_case($testStruct->toArray(), CASE_LOWER);
        unset($result['extensions']); //Shopware core adds this

        $this->assertArraySubset($result, $data, false, 'Expected output data to be the same as the input data to verify the deserialization process!');
    }
}
