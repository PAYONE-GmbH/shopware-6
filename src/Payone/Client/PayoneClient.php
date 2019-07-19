<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PayoneClient implements PayoneClientInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws PayoneRequestException
     */
    public function request(array $parameters, bool $json = true): array
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'cache-control: no-cache',
        ]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curl, CURLOPT_URL, 'https://api.pay1.de/post-gateway/');

        $response = curl_exec($curl);
        $errno    = curl_errno($curl);

        if ($errno !== CURLE_OK) {
            throw new RuntimeException('curl client error: ' . $errno);
        }

        if (empty($response)) {
            throw new RuntimeException('empty payone response');
        }

        if (!$json) {
            $response = [
                'status' => 'success',
                'data'   => $response,
            ];
        } else {
            $response = json_decode($response, true);
        }

        $this->logger->debug('payone request', [
            'parameters' => $parameters,
            'response'   => $response,
        ]);

        if (empty($response)) {
            throw new PayoneRequestException('payone returned a empty response', $parameters, $response);
        }

        $response = array_change_key_case($response, CASE_LOWER);

        ksort($response, SORT_NATURAL | SORT_FLAG_CASE);

        if ($response['status'] === 'ERROR') {
            throw new PayoneRequestException('payone returned a error', $parameters, $response);
        }

        return $response;
    }
}
