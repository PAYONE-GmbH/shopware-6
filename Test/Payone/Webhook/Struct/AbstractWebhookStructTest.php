<?php

namespace PayonePayment\Test\Payone\Webhook\Struct;

use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use PHPUnit\Framework\TestCase;

class AbstractWebhookStructTest extends TestCase
{
    public function testDeserializeWillUseLowercaseForPropertyNames(): void
    {
        $data = [
            'txid' => '1234', //Property is $txId!
        ];

        $testStruct = new TransactionStatusStruct($data);

        $this->assertEquals('1234', $testStruct->txId, 'Expected txId to be set even though it was provided in lowercase!');
    }

    public function testDeserializeWithUnderscoreWillSetCorrectProperty()
    {
        $data = [
            'transaction_status' => 'MyStatus', //Property is $txId!
        ];

        $testStruct = new TransactionStatusStruct($data);

        $this->assertEquals('MyStatus', $testStruct->transactionStatus, 'Expected a property to be set even though there is an underscore character in the input data!');
    }

    public function testDeserializeWillSetAllProperties(): void
    {
        $data = [
            'txid'              => '1234',
            'reference'         => 'MyReference',
            'sequencenumber'    => 'MySequenceNumber',
            'price'             => 100,
            'failedcause'       => 'MyFailedCause',
            'errorcode'         => 'MyErrorCode',
            'reasoncode'        => 'MyReasonCode',
            'transactionstatus' => 'completed',
            'txtime'            => 12345,
            'aid'               => 'MyAID',
            'clearingtype'      => 'wlt', //Wallet
            'txaction'          => 'appointed',
        ];

        $testStruct = new TransactionStatusStruct($data);

        $result = array_change_key_case($testStruct->toArray(), CASE_LOWER);
        unset($result['extensions']); //Shopware core adds this

        $this->assertArraySubset($result, $data, false, 'Expected output data to be the same as the input data to verify the deserialization process!');
    }
}
