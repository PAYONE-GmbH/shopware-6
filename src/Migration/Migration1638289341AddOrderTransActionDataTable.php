<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1638289341AddOrderTransActionDataTable extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1638289341;
    }

    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `payone_payment_order_transaction_data` (
            `id` BINARY(16) NOT NULL,
            `order_transaction_id` BINARY(16) NOT NULL,
            `transaction_id` VARCHAR(255) NOT NULL,
            `transaction_data` JSON NULL,
            `sequence_number` INT(11) NULL,
            `transaction_state` VARCHAR(255) NULL,
            `user_id` VARCHAR(255) NULL,
            `last_request` VARCHAR(255) NULL,
            `allow_capture` TINYINT(1) NULL DEFAULT \'0\',
            `captured_amount` INT(11) NULL DEFAULT \'0\',
            `allow_refund` TINYINT(1) NULL DEFAULT \'0\',
            `refunded_amount` INT(11) NULL DEFAULT \'0\',
            `mandate_identification` VARCHAR(255) NULL,
            `authorization_type` VARCHAR(255) NULL,
            `work_order_id` VARCHAR(255) NULL,
            `clearing_reference` VARCHAR(255) NULL,
            `clearing_type` VARCHAR(255) NULL,
            `financing_type` VARCHAR(255) NULL,
            `capture_mode` VARCHAR(255) NULL,
            `clearing_bank_account` JSON NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `json.payone_payment_order_transaction_data.transaction_data` CHECK (JSON_VALID(`transaction_data`)),
            CONSTRAINT `json.payone_payment_order_transaction_data.clearing_bank_account` CHECK (JSON_VALID(`clearing_bank_account`))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } elseif (method_exists($connection, 'exec')) {
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
