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

        /** @var false|string $response */
        $response = curl_exec($curl);
        $errno    = curl_errno($curl);

        if ($errno !== CURLE_OK) {
            throw new RuntimeException('curl client error: ' . $errno);
        }

        if (empty($response)) {
            throw new RuntimeException('empty payone response');
        }

        $data = json_decode($response, true);

        // Payone returns a JSON on file requests instead of a HTTP error. Only if the response should not be a JSON
        // and is in fact not a JSON, we can return the raw response as it is most likely a file.
        if (!$json && json_last_error() !== JSON_ERROR_NONE) {
            $data = [
                'status' => 'success',
                'data'   => $response,
            ];
        }

        $this->logger->debug('payone request', [
            'parameters' => $parameters,
            'response'   => $data,
        ]);

        if (empty($data)) {
            throw new PayoneRequestException('payone returned a empty response', $parameters, $data);
        }

        $response = array_change_key_case($data, CASE_LOWER);

        ksort($response, SORT_NATURAL | SORT_FLAG_CASE);

        if ($response['status'] === 'ERROR') {
            throw new PayoneRequestException('payone returned a error', $parameters, $response);
        }

        return $response;
    }
}
