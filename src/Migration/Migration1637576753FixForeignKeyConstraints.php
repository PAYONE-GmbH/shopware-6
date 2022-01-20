<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1637576753FixForeignKeyConstraints extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1637576753;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE payone_payment_card DROP FOREIGN KEY `fk.payone_payment_card.customer_id`;';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } elseif (method_exists($connection, 'exec')) {
            /** method exec() is deprecated and will be removed in future doctrine releases */
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }

        $sql = 'ALTER TABLE payone_payment_card ADD CONSTRAINT `fk.payone_payment_card.customer_id` FOREIGN KEY (`customer_id`)
                REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } elseif (method_exists($connection, 'exec')) {
            /** method exec() is deprecated and will be removed in future doctrine releases */
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
