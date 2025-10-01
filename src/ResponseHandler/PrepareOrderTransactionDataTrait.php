<?php

declare(strict_types=1);

namespace PayonePayment\ResponseHandler;

trait PrepareOrderTransactionDataTrait
{
    protected function prepareOrderTransactionData(array $request, array $response, array $fields = []): array
    {
        $key = (new \DateTime())->format(\DATE_ATOM);

        $basics = [
            'authorizationType' => $request['request'],
            'lastRequest'       => $request['request'],
            'transactionId'     => (string) $response['txid'],
            'sequenceNumber'    => -1,
            'userId'            => $response['userid'],
            'transactionState'  => $response['status'],
            'transactionData'   => [
                $key => [
                    'request'  => $request,
                    'response' => $response,
                ],
            ],
        ];

        if ([] === $fields) {
            return $basics;
        }

        return \array_merge($basics, $fields);
    }
}
