<?php declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1709142764NotificationQueue extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_709_142_764;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `payone_payment_notification_queue` (
            `id` BINARY(16) NOT NULL,
            `notification_target_id` BINARY(16) NOT NULL,
            `response_http_code` INT NOT NULL,
            `message` LONGTEXT NULL,
            `last_execution_time` DATETIME(3),
            `next_execution_time` DATETIME(3) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
