<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604321923FixPayoneSafeInvoiceId extends MigrationStep
{
    private $oldId = '0b532088e2da3092f9f7054ec4009d18';
    private $newId = '4e8a9d3d3c6e428887573856b38c9003';

    public function getCreationTimestamp(): int
    {
        return 1604321923;
    }

    public function update(Connection $connection): void
    {
        $connection->exec("UPDATE `payment_method` SET `id` = UNHEX('{$this->newId}') WHERE `id` = UNHEX('{$this->oldId}');");
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->exec("UPDATE `payment_method` SET `id` = UNHEX('{$this->oldId}') WHERE `id` = UNHEX('{$this->newId}');");
    }
}
