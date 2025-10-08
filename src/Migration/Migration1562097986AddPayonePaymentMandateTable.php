<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1562097986AddPayonePaymentMandateTable extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_562_097_986;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        // PAYOSWXP-114: has been removed.
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
