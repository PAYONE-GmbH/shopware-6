<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1695663841AddOrderActionLogTable extends MigrationStep
{
    use InheritanceUpdaterTrait;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_695_663_841;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `payone_payment_order_action_log` (
            `id` BINARY(16) NOT NULL,
            `order_version_id` BINARY(16) NOT NULL,
            `order_id` BINARY(16) NOT NULL,
            `transaction_id` VARCHAR(255) NOT NULL,
            `reference_number` VARCHAR(255) NOT NULL,
            `request` VARCHAR(255) NOT NULL,
            `response` VARCHAR(255) NOT NULL,
            `amount` INT(11) NOT NULL,
            `mode` VARCHAR(255) NOT NULL,
            `merchant_id` VARCHAR(255) NOT NULL,
            `portal_id` VARCHAR(255) NOT NULL,
            `request_details` JSON NOT NULL,
            `response_details` JSON NOT NULL,
            `request_date_time` DATETIME(3) NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`, `order_version_id`),
            CONSTRAINT `json.payone_payment_order_action_log.request_details` CHECK (JSON_VALID(`request_details`)),
            CONSTRAINT `json.payone_payment_order_action_log.response_details` CHECK (JSON_VALID(`response_details`))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $connection->executeStatement($sql);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
