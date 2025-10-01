<?php

declare(strict_types=1);

namespace PayonePayment\HttpClient;

use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Serializer;

class PayoneApiClient implements PayoneApiClientInterface
{
    private string $endpoint = 'https://api.pay1.de/post-gateway/';

    private readonly Serializer $serializer;

    public function __construct(
        private readonly Psr18Client $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger,
    ) {
        $this->serializer = new Serializer([], [ new JsonEncoder() ]);
    }

    public function request(array $parameters, bool $expectsJson = true): array
    {
        $bodyStream = $this->streamFactory->createStream(http_build_query($parameters));
        $request    = $this->requestFactory
            ->createRequest('POST', $this->endpoint)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Cache-Control', 'no-cache')
            ->withBody($bodyStream)
        ;

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new \RuntimeException('curl client error: ' . $e->getCode());
        }

        $body = (string) $response->getBody();

        if ('' === $body) {
            throw new \RuntimeException('empty payone response');
        }

        $data = null;

        // TODO: We should check if it's always required to decode the response
        try {
            $data = $this->serializer->decode($body, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException) {
            // Payone returns a JSON on file requests instead of a HTTP error. Only if the response should not be a JSON
            // and is in fact not a JSON, we can return the raw response as it is most likely a file.

            if (!$expectsJson) {
                $data = [
                    'status' => 'success',
                    'data'   => $body,
                ];
            }
        }

        $this->logger->debug('payone request', [
            'parameters' => $parameters,
            'response'   => $data,
        ]);

        if (empty($data)) {
            throw new PayoneRequestException('payone returned an empty response', $parameters, []);
        }

        $normalized = \array_change_key_case($data, \CASE_LOWER);

        \ksort($normalized, \SORT_NATURAL | \SORT_FLAG_CASE);

        if ('ERROR' === \strtoupper($normalized['status'] ?? '')) {
            throw new PayoneRequestException('payone returned an error', $parameters, $normalized);
        }

        return $normalized;
    }
}
