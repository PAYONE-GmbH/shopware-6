<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1709633086AddOrderTransactionIdIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_709_633_086;
    }

    public function update(Connection $connection): void
    {
        // add index for order-transaction-id
        $sql = 'ALTER TABLE `payone_payment_order_transaction_data` ADD INDEX(`order_transaction_id`);';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
