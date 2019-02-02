<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Client;

use LogicException;
use Psr\Log\LoggerInterface;

class PayoneClient implements PayoneClientInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function request(array $parameters): array
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
        $info     = curl_getinfo($curl);
        $errno    = curl_errno($curl);

        // TODO: Handle error codes/status and curl errors

        if (empty($response)) {
            throw new LogicException('empty payone response');
        }

        $response = json_decode($response, true);

        $this->logger->debug('payone request', [
            'parameters' => $parameters,
            'response'   => $response,
        ]);

        if (empty($response)) {
            throw new LogicException('payone returned a malformed json');
        }

        if ($response['Status'] === 'ERROR') {
            throw new LogicException('payone responded with ERROR');
        }

        return $response;
    }
}
