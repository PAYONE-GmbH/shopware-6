<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1695663842AddWebhookLogTable extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1_695_663_842;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `payone_payment_webhook_log` (
            `id` BINARY(16) NOT NULL,
            `order_version_id` BINARY(16) NOT NULL,
            `order_id` BINARY(16) NOT NULL,
            `transaction_id` VARCHAR(255) NOT NULL,
            `transaction_state` VARCHAR(255) NOT NULL,
            `sequence_number` INT(11) NOT NULL,
            `clearing_type` VARCHAR(255) NOT NULL,
            `webhook_details` JSON NOT NULL,
            `webhook_date_time` DATETIME(3) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`, `order_version_id`),
            CONSTRAINT `json.payone_payment_webhook_log.webhook_details` CHECK (JSON_VALID(`webhook_details`))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
