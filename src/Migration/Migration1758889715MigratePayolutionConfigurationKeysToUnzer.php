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
class Migration1758889715MigratePayolutionConfigurationKeysToUnzer extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1_758_889_715;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function update(Connection $connection): void
    {
        $stmt = <<<'SQL'
SELECT * FROM `system_config` WHERE `configuration_key` LIKE 'PayonePayment.settings.payolution%';
SQL;

        $result = $connection->executeQuery($stmt);

        foreach ($result->fetchAllAssociative() as $row) {
            $columns = \array_keys($row);

            $row['id']                = Uuid::randomBytes();
            $row['configuration_key'] = \str_replace(
                'PayonePayment.settings.payolution',
                'PayonePayment.settings.unzer',
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

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(<<<'SQL'
DELETE FROM `system_config` WHERE `configuration_key` LIKE 'PayonePayment.settings.payolution%';
SQL);
    }
}
