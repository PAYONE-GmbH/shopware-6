<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1686568861TransactionDataTxIdLength extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_686_568_861;
    }

    public function update(Connection $connection): void
    {
        // reduce max length of transaction-id
        $sql = 'ALTER TABLE `payone_payment_order_transaction_data` CHANGE `transaction_id` `transaction_id` VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
