<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1718635783DeleteSavedDebitMandates extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_718_635_783;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS payone_payment_mandate');
    }
}
