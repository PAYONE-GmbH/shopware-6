<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639050658MigrateOrderTransactionDataFromCustomFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1639050658;
    }

    public function update(Connection $connection): void
    {
        $sql = 'INSERT INTO `payone_payment_order_transaction_data`
                (id, order_transaction_id, order_transaction_version_id, transaction_id, transaction_data, sequence_number, transaction_state, user_id, last_request,
                allow_capture, allow_refund, captured_amount, refunded_amount, mandate_identification, authorization_type, work_order_id,
                clearing_reference, clearing_type, financing_type, capture_mode, clearing_bank_account, additional_data, created_at)
                SELECT UNHEX(LOWER(CONCAT(
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\'),
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\'),
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\'),
                    \'4\',
                    LPAD(HEX(FLOOR(RAND() * 0x0fff)), 3, \'0\'),
                    HEX(FLOOR(RAND() * 4 + 8)),
                    LPAD(HEX(FLOOR(RAND() * 0x0fff)), 3, \'0\'),
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\'),
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\'),
                    LPAD(HEX(FLOOR(RAND() * 0xffff)), 4, \'0\')))) as id,
                    id as order_transaction_id,
                    version_id as order_transaction_version_id,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_transaction_id\')) as transaction_id,
                    JSON_EXTRACT(custom_fields, \'$.payone_transaction_data\') as transaction_data,
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_sequence_number\')) as SIGNED) as sequence_number,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_transaction_state\')) as transaction_state,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_user_id\')) as user_id,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_last_request\')) as last_request,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_allow_capture\')) IN (\'true\', 1) as allow_capture,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_allow_refund\')) IN (\'true\', 1) as allow_refund,
                    IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_captured_amount\')) as SIGNED), 0) as captured_amount,
                    IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_refunded_amount\')) as SIGNED), 0) as refunded_amount,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_mandate_identification\')) as mandate_identification,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_authorization_type\')) as authorization_type,
                    REPLACE(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_work_order_id\')), \'null\', NULL) as work_order_id,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_clearing_reference\')) as clearing_reference,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_clearing_type\')) as clearing_type,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_financing_type\')) as financing_type,
                    JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_capture_mode\')) as capture_mode,
                    JSON_EXTRACT(custom_fields, \'$.payone_clearing_bank_account\') as clearing_bank_account,
                    JSON_OBJECT(\'used_ratepay_shop_id\', JSON_UNQUOTE(JSON_EXTRACT(custom_fields, \'$.payone_used_ratepay_shop_id\'))) as additional_data,
                    NOW()
                    FROM order_transaction WHERE custom_fields IS NOT NULL AND JSON_CONTAINS_PATH(custom_fields, \'one\', \'$.payone_transaction_id\') = 1;
';

        try {
            $connection->beginTransaction();

            if (method_exists($connection, 'executeStatement')) {
                $connection->executeStatement($sql);
            } elseif (method_exists($connection, 'exec')) {
                /** @noinspection PhpDeprecationInspection */
                $connection->exec($sql);
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw new \RuntimeException('An error occurred while migrating payone custom field values');
        }

        //** added where clause reduce columns that will be updated */
        $sql = 'UPDATE order_transaction SET custom_fields = JSON_REMOVE(custom_fields,
            \'$.payone_transaction_id\',
            \'$.payone_sequence_number\',
            \'$.payone_transaction_data\',
            \'$.payone_transaction_state\',
            \'$.payone_last_request\',
            \'$.payone_authorization_type\',
            \'$.payone_user_id\',
            \'$.payone_allow_capture\',
            \'$.payone_allow_refund\',
            \'$.payone_captured_amount\',
            \'$.payone_refunded_amount\',
            \'$.payone_mandate_identification\',
            \'$.payone_clearing_reference\',
            \'$.payone_work_order_id\',
            \'$.payone_clearing_type\',
            \'$.payone_financing_type\',
            \'$.payone_capture_mode\',
            \'$.payone_clearing_bank_account\',
            \'$.payone_used_ratepay_shop_id\'
        )
        WHERE custom_fields IS NOT NULL AND JSON_CONTAINS_PATH(custom_fields, \'one\', \'$.payone_transaction_id\') = 1;';

        try {
            $connection->beginTransaction();

            if (method_exists($connection, 'executeStatement')) {
                $connection->executeStatement($sql);
            } elseif (method_exists($connection, 'exec')) {
                /** @noinspection PhpDeprecationInspection */
                $connection->exec($sql);
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw new \RuntimeException('An error occurred while unsetting payone custom field values');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
