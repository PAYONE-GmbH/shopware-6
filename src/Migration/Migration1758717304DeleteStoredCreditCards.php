<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1758717304DeleteStoredCreditCards extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_758_717_304;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function update(Connection $connection): void
    {
        $this->deleteCreditCardData($connection);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }

    /**
     * @throws Exception
     */
    private function deleteCreditCardData(Connection $connection): void
    {
        $stmt = <<<SQL
TRUNCATE TABLE `payone_payment_card`;
SQL;

        $connection->executeStatement($stmt);
    }
}
