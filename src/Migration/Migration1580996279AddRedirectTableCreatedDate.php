<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580996279AddRedirectTableCreatedDate extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_580_996_279;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `payone_payment_redirect` ADD `created_at` datetime(3) NULL;';

        $connection->executeStatement($sql);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
