<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1548170939AddPayonePaymentConfigTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1548170939;
    }

    public function update(Connection $connection): void
    {
        $connection->exec('
            CREATE TABLE IF NOT EXISTS payone_payment_config (
                `id` binary(16) NOT NULL PRIMARY KEY,
                
                `sales_channel_id` binary(16) DEFAULT NULL,
                
                `config_key` varchar(255) NOT NULL,
                `config_value` varchar(255) NOT NULL,
                
                `created_at` datetime(3) NOT NULL,
                `updated_at` datetime(3),
                    
                CONSTRAINT `fk.payone_payment_config.sales_channel_id`
                    FOREIGN KEY (sales_channel_id) 
                    REFERENCES `sales_channel` (id)
                    ON DELETE CASCADE ON UPDATE CASCADE
                    
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
