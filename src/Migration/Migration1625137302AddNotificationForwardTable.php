<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1625137302AddNotificationForwardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1625137302;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `payone_payment_notification_forward` (
                `id` BINARY(16) NOT NULL,
                `notification_target_id` BINARY(16) NULL,
                `ip` VARCHAR(255) NULL,
                `txaction` VARCHAR(255) NULL,
                `response` VARCHAR(255) NULL,
                `transaction_id` BINARY(16) NOT NULL,
                `content` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

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
