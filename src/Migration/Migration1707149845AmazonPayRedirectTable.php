<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1707149845AmazonPayRedirectTable extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_707_149_845;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `payone_amazon_redirect` (
                `id` BINARY(16) NOT NULL,
                `pay_data` TEXT NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB;
        ');
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
