<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1718642595CreditCardAddCardHolder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_718_642_595;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            <<<'SQL'
            ALTER TABLE `payone_payment_card`
                ADD `card_holder` VARCHAR(255) NOT NULL AFTER `customer_id`;
            SQL
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
