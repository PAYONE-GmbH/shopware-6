<?php

declare(strict_types=1);

namespace PayonePayment\Components\RedirectHandler;

use Doctrine\DBAL\Connection;
use LogicException;
use RuntimeException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectHandler
{
    /** @var Connection */
    private $connection;

    /** @var RouterInterface */
    private $router;

    public function __construct(Connection $connection, RouterInterface $router)
    {
        $this->connection = $connection;
        $this->router     = $router;
    }

    public function encode(string $url): string
    {
        $secret = getenv('APP_SECRET');

        if (empty($secret)) {
            throw new LogicException('empty app secret');
        }

        $hash = base64_encode(hash_hmac('sha256', $url, $secret));

        $this->connection->insert('payone_payment_redirect', [
            'id'         => Uuid::randomBytes(),
            'hash'       => $hash,
            'url'        => $url,
            'created_at' => date(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $params = [
            'hash' => $hash,
        ];

        return $this->router->generate('payone_redirect', $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function decode(string $hash): string
    {
        $query = 'SELECT url FROM payone_payment_redirect WHERE hash = ?';

        if (method_exists($this->connection, 'fetchOne')) {
            $url = $this->connection->fetchOne($query, [$hash]);
        } elseif (method_exists($this->connection, 'fetchColumn')) {
            /** @noinspection PhpDeprecationInspection */
            $url = $this->connection->fetchColumn($query, [$hash]);
        }

        if (empty($url)) {
            throw new RuntimeException('no matching url for hash found');
        }

        return (string) $url;
    }
}
