<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class Migration1759135906FixInvoicingConfigurationKeys extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_759_135_906;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $stmt = <<<'SQL'
SELECT * FROM `system_config` WHERE `configuration_key` LIKE 'PayonePayment.settings.%Invoicing%';
SQL;

        $result = $connection->executeQuery($stmt);

        foreach ($result->fetchAllAssociative() as $row) {
            $columns = \array_keys($row);

            $row['id']                = Uuid::randomBytes();
            $row['configuration_key'] = \str_replace(
                'Invoicing',
                'Invoice',
                $row['configuration_key'],
            );

            $stmt = \sprintf(
                'INSERT INTO `system_config` (%s) VALUES (%s);',
                '`' . \implode('`, `', $columns) . '`',
                ':' . \implode(', :', $columns),
            );

            $connection->executeStatement($stmt, $row);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
DELETE FROM `system_config` WHERE `configuration_key` LIKE 'PayonePayment.settings.%Invoicing%';
SQL);
    }
}
