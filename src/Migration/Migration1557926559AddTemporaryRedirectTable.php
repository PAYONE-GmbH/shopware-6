<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1557926559AddTemporaryRedirectTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1557926559;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
