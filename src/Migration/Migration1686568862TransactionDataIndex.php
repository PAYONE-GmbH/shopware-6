<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1686568862TransactionDataIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1686568862;
    }

    public function update(Connection $connection): void
    {
        // add index for transaction-id
        $sql = 'ALTER TABLE `payone_payment_order_transaction_data` ADD INDEX(`transaction_id`);';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
