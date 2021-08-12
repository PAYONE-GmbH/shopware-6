<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580996279AddRedirectTableCreatedDate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580996279;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `payone_payment_redirect` ADD `created_at` datetime(3) NULL;';

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
