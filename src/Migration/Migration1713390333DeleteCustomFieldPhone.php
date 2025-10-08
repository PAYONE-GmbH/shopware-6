<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1713390333DeleteCustomFieldPhone extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_713_390_333;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DELETE FROM custom_field WHERE name = "payone_customer_phone_number";');
        $connection->executeStatement('DELETE FROM custom_field_set WHERE name = "customer_payone_payment";');
    }
}
