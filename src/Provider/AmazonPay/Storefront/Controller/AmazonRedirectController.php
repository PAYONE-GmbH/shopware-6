<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[Route(defaults: [ '_routeScope' => [ 'storefront' ] ])]
class AmazonRedirectController extends StorefrontController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DecoderInterface $decoder,
    ) {
    }

    #[Route(
        path: '/payone/redirect/amazon-pay/{uuid}',
        name: 'payment.payone_redirect.amazon',
        methods: [ 'GET' ],
    )]
    public function amazonRedirect(string $uuid): Response
    {
        $data = $this->connection->fetchOne(
            'SELECT pay_data FROM payone_amazon_redirect WHERE id = ? LIMIT 1',
            [ Uuid::fromHexToBytes($uuid) ],
        );

        $payData = \is_string($data) ? $this->decoder->decode($data, JsonEncoder::FORMAT) : null;

        if (!\is_array($payData)) {
            throw $this->createNotFoundException();
        }

        return $this->render('@PayonePayment/storefront/page/checkout/redirect/amazon-pay.html.twig', [
            'amazonPayButtonConfiguration' => $payData,
        ]);
    }
}
