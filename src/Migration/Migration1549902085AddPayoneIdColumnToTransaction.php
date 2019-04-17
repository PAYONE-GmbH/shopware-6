<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1549902085AddPayoneIdColumnToTransaction extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1549902085;
    }

    public function update(Connection $connection): void
    {
        $connection->exec('
              ALTER TABLE order_transaction
              ADD payone_transaction_id INT
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
