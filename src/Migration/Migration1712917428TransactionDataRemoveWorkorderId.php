<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1712917428TransactionDataRemoveWorkorderId extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1_712_917_428;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        if ($this->columnExists($connection, 'payone_payment_order_transaction_data', 'work_order_id')) {
            $connection->executeStatement('ALTER TABLE payone_payment_order_transaction_data DROP COLUMN work_order_id');
        }
    }
}
