<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1637576753FixForeignKeyConstraints extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_637_576_753;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE payone_payment_card DROP FOREIGN KEY `fk.payone_payment_card.customer_id`;';

        $connection->executeStatement($sql);

        $sql = 'ALTER TABLE payone_payment_card ADD CONSTRAINT `fk.payone_payment_card.customer_id` FOREIGN KEY (`customer_id`)
                REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;';

        $connection->executeStatement($sql);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
