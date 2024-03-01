<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1709142760AlterNotificationTargetTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_709_142_760;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `payone_payment_notification_target`
                ADD `resend_notification` TINYINT(1) NULL DEFAULT \'0\' AFTER `password`,
                ADD `resend_notification_time` JSON NULL AFTER `resend_notification`,
                ADD `resend_notification_status` JSON NULL AFTER `resend_notification_time`;';

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
