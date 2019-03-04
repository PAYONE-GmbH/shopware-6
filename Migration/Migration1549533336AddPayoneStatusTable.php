<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1549533336AddPayoneStatusTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1549533336;
    }

    public function update(Connection $connection): void
    {
        $connection->exec('
            CREATE TABLE IF NOT EXISTS payone_payment_status (
                `id` binary(16) NOT NULL PRIMARY KEY,
                
                `order_transaction_id` binary(16) DEFAULT NULL,
                
                `sequence_number` INT(2) NOT NULL,
                `reference` varchar(255) NOT NULL,
                `action` varchar(255),
                `clearing_type` varchar(3),
                `price` float,
                
                `created_at` datetime(3) NOT NULL,
                `updated_at` datetime(3),
                    
                CONSTRAINT `fk.payone_payment_status.order_transaction_id`
                    FOREIGN KEY (order_transaction_id) 
                    REFERENCES `order_transaction` (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
                    
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
