<?php

declare(strict_types=1);

namespace PayonePayment\Components\RedirectHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RouterInterface $router,
        private readonly string $appSecret = ''
    ) {
    }

    public function encode(string $url): string
    {
        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        $hash = base64_encode(hash_hmac('sha256', $url, $this->appSecret));

        $this->connection->insert('payone_payment_redirect', [
            'id' => Uuid::randomBytes(),
            'hash' => $hash,
            'url' => $url,
            'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $params = [
            'hash' => $hash,
        ];

        return $this->router->generate('payment.payone_redirect', $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function decode(string $hash): string
    {
        $query = 'SELECT url FROM payone_payment_redirect WHERE hash = ?';

        $url = $this->connection->fetchOne($query, [$hash]);

        if (empty($url)) {
            throw new \RuntimeException('no matching url for hash found');
        }

        return (string) $url;
    }

    public function cleanup(): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM payone_payment_redirect WHERE created_at < ?',
            [new \DateTime('-7 day')],
            [Types::DATETIME_MUTABLE]
        );
    }
}
