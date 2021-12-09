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
            `order_transaction_version_id` BINARY(16) NOT NULL,
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
            PRIMARY KEY (`id`, `order_transaction_version_id`),
            CONSTRAINT `json.payone_payment_order_transaction_data.transaction_data` CHECK (JSON_VALID(`transaction_data`)),
            CONSTRAINT `json.payone_payment_order_transaction_data.clearing_bank_account` CHECK (JSON_VALID(`clearing_bank_account`))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } elseif (method_exists($connection, 'exec')) {
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }

        //TODO: SELECT id as order_transaction_id, version_id as order_transaction_version_id,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_transaction_id')) as transaction_id,
        //JSON_EXTRACT(custom_fields, '$.payone_transaction_data') as transaction_data,
        //CAST(JSON_EXTRACT(custom_fields, '$.payone_sequence_number') as SIGNED) as sequence_number,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_transaction_state')) as transaction_state,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_user_id')) as user_id,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_last_request')) as last_request,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_allow_capture')) as allow_capture,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_allow_refund')) as allow_refund,
        //IFNULL(CAST(JSON_EXTRACT(custom_fields, '$.payone_captured_amount') as SIGNED), 0) as captured_amount,
        //IFNULL(CAST(JSON_EXTRACT(custom_fields, '$.payone_refunded_amount') as SIGNED), 0) as refunded_amount,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_mandate_identification')) as mandate_identification,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_authorization_type')) as authorization_type,
        //REPLACE(TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_work_order_id')), 'null', NULL) as work_order_id,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_clearing_reference')) as clearing_reference,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_clearing_type')) as clearing_type,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_financing_type')) as financing_type,
        //TRIM(BOTH '"' FROM JSON_EXTRACT(custom_fields, '$.payone_capture_mode')) as capture_mode,
        //JSON_EXTRACT(custom_fields, '$.payone_clearing_bank_account') as clearing_bank_account,
        //NOW()
        //FROM order_transaction WHERE custom_fields IS NOT NULL AND JSON_CONTAINS_PATH(custom_fields, 'one', '$.payone_transaction_id') = 1;
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
