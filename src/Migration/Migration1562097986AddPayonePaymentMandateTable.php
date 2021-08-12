<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1562097986AddPayonePaymentMandateTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1562097986;
    }

    public function update(Connection $connection): void
    {
        $sql = '
            CREATE TABLE `payone_payment_mandate` (
                `id` BINARY(16) NOT NULL,

                `customer_id` BINARY(16) NOT NULL,

                `identification` VARCHAR(255) NOT NULL,
                `signature_date` datetime(3) NOT NULL,

                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),

                KEY `fk.payone_payment_mandate.customer_id` (`customer_id`),

                CONSTRAINT `fk.payone_payment_mandate.customer_id`
                    FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ';

        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);

            return;
        }

        if (method_exists($connection, 'exec')) {
            /** @noinspection PhpDeprecationInspection */
            $connection->exec($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
