<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1557926559AddTemporaryRedirectTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1557926559;
    }

    public function update(Connection $connection): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS payone_payment_redirect (
                `id` binary(16) NOT NULL PRIMARY KEY,
                `hash` text NOT NULL,
                `url` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);

            return;
        }

        if (method_exists($connection, 'exec')) {
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
