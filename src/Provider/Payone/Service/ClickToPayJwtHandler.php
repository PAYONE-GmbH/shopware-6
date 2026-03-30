<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Service;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Provider\Payone\Dto\ClickToPayJwtDto;
use PayonePayment\Provider\Payone\Exception\ClickToPay\ClickToPayTokenExpiredException;
use PayonePayment\Provider\Payone\PaymentHandler\ClickToPayPaymentHandler;
use PayonePayment\Provider\Payone\RequestParameter\ClickToPayCheckRequestDto;
use PayonePayment\Provider\Payone\RequestParameter\RequestEnricher;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ClickToPayJwtHandler
{
    private Serializer $serializer;

    private const SESSION_KEY = 'payoneClickToPayJwt';

    public function __construct(
        private RequestEnricher $requestEnricher,
        private RequestParameterEnricherChain $requestEnricherChain,
        private ClickToPayPaymentHandler $paymentHandler,
        private PayoneClientInterface $payoneClient,
    ) {
        $this->serializer = new Serializer(
            [ new ObjectNormalizer(), new DateTimeNormalizer(), new DateTimeZoneNormalizer() ],
            [ new JsonEncoder() ],
        );
    }

    public function getJwt(SessionInterface|null $session, SalesChannelContext $salesChannelContext): ClickToPayJwtDto
    {
        try {
            if (!$session) {
                throw new SessionNotFoundException();
            }

            $sessionJwt = $session->get(self::SESSION_KEY);

            if (null === $sessionJwt) {
                throw new SessionNotFoundException();
            }

            /** @var ClickToPayJwtDto $jwtDto */
            $jwtDto = $this->serializer->deserialize(
                (string) $sessionJwt,
                ClickToPayJwtDto::class,
                JsonEncoder::FORMAT
            );

            $now = new \DateTimeImmutable('+3 minute', new \DateTimeZone('UTC'));

            if ($now > $jwtDto->expirationDate) {
                throw new ClickToPayTokenExpiredException();
            }
        } catch (NotNormalizableValueException|PartialDenormalizationException|ClickToPayTokenExpiredException|SessionNotFoundException $e) {
            $cardRequest = $this->requestEnricher->enrich(
                new ClickToPayCheckRequestDto(
                    $salesChannelContext,
                    $this->paymentHandler,
                    false,
                ),
                $this->requestEnricherChain,
            );

            $parameters = $cardRequest->all();
            $response   = $this->payoneClient->request($parameters);

            $jwtDto = $this->serializer->denormalize([
                'creationDate'   => $response['creationdate'],
                'expirationDate' => $response['expirationdate'],
                'status'         => $response['status'],
                'token'          => $response['token'],
            ], ClickToPayJwtDto::class);

            $serlializedJwtDto = $this->serializer->encode($jwtDto, JsonEncoder::FORMAT);
            $session?->set(self::SESSION_KEY, $serlializedJwtDto);
        }

        return $jwtDto;
    }
}
