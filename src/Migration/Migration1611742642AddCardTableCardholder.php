<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1611742642AddCardTableCardholder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1611742642;
    }

    public function update(Connection $connection): void
    {
        // Remove all stored customer credit cards to force recreation.
        // This is basically a trade-off between data correctness and a small amount
        // of customers affected by this operation.
        $connection->exec('DELETE FROM `payone_payment_card`');

        // Add cordholder field to credit card table
        $connection->exec('ALTER TABLE `payone_payment_card` ADD `cardholder` VARCHAR(50) NOT NULL AFTER `pseudo_card_pan`');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
