<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1561379069AddPayonePaymentCardTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1561379069;
    }

    public function update(Connection $connection): void
    {
        $connection->exec('            
            CREATE TABLE `payone_payment_card` (
                `id` BINARY(16) NOT NULL,
                
                `customer_id` BINARY(16) NOT NULL,
                
                `pseudo_card_pan` VARCHAR(255) NOT NULL,
                `truncated_card_pan` VARCHAR(255) NOT NULL,
                
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                
                PRIMARY KEY (`id`),
                
                KEY `fk.payone_payment_card.customer_id` (`customer_id`),
                
                CONSTRAINT `fk.payone_payment_card.customer_id` 
                    FOREIGN KEY (`customer_id`) 
                    REFERENCES `customer` (`id`) 
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
