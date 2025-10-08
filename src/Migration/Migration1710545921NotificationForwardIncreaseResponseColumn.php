<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1710545921NotificationForwardIncreaseResponseColumn extends MigrationStep
{
    use InheritanceUpdaterTrait;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_710_545_921;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `payone_payment_notification_forward` CHANGE `response` `response` LONGTEXT DEFAULT NULL;';

        $connection->executeStatement($sql);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
