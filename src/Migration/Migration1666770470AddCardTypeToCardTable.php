<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1666770470AddCardTypeToCardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_666_770_470;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `payone_payment_card` ADD `card_type` VARCHAR(255) NOT NULL;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
